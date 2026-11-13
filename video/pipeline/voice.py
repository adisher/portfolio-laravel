"""Speak a reel's script in the owner's cloned voice and build the render props.

Usage: python voice.py [--dummy]

Reads video/public/render/draft.json (from fetch.py) and:
  1. picks the liveliest 12 s of the voice recording as the clone reference
     (Chatterbox reads 10 s, and copies the energy of what it is given);
  2. rewrites each line with the owner's pronunciation list, so tricky words
     are always said the same way;
  3. speaks each scene up to 3 times with Chatterbox (MIT, CPU) and keeps the
     clearest take, judged by Whisper (faster-whisper) listening back;
  4. times every caption word from Whisper's word timestamps, mapped back to
     the real spelling (falls back to an estimate if a word can't be matched);
  5. writes public/render/voice.wav, props.json and meta.json.
--dummy skips both models and writes silence of the estimated length, so the
rest of the pipeline can be tested anywhere.
"""
import difflib
import json
import pathlib
import re
import subprocess
import sys

import numpy as np
import soundfile as sf

RENDER = pathlib.Path(__file__).resolve().parent.parent / "public" / "render"
REFERENCE_INFO: dict = {}  # filled by reference_clip(), sent back in the report
SR = 24000            # Chatterbox's output rate
PACE = 2.5            # words per second, same as config/social.php
BREATH = 0.35         # pause after each scene's line
LEAD = 0.15           # pause before each scene's line
TAKES = 4             # attempts per scene
GOOD_ENOUGH = 0.93    # take score that stops further takes: needs every word heard as written
TRUST_TIMING = 0.75   # below this match, captions use even timing instead of Whisper's
# Pace is measured from the first to the last word of a line, so it excludes
# pauses: natural speech runs about 3 to 4 words a second on that measure.
# (2.95 was too strict and slowed almost every take, which sounded accented.)
TARGET_PACE = 3.5     # words per second a rushed take is slowed towards
MAX_PACE = 3.9        # faster than this counts as rushed
MAX_SLOWDOWN = 0.95   # never slow a take below 92% speed (more starts to sound stretched)
FADE_IN = 0.015       # seconds; avoids clicks where a line starts
FADE_OUT = 0.08       # seconds; lets the last word decay instead of stopping dead
ROOM_TONE = 0.0008    # ~-62 dBFS noise bed so pauses never drop to dead digital silence
DOMAIN = re.compile(r"^([a-z0-9-]+)\.(to|io|com|dev|pro|ai|app|net|org|co|sh|so|gg|me|tv)$", re.I)
DEFAULT_VOICE = {"exaggeration": 0.55, "cfg_weight": 0.5, "temperature": 0.8, "pronunciations": [], "seed": 1}


# ── Text ────────────────────────────────────────────────────────────────────

def words_of(text: str) -> list[str]:
    return [w for w in re.split(r"\s+", (text or "").strip()) if w]


def norm(word: str) -> str:
    return re.sub(r"[^a-z0-9]", "", word.lower())


ONES = "zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen".split()
TENS = "_ _ twenty thirty forty fifty sixty seventy eighty ninety".split()


def int_words(n: int) -> str:
    if n < 20:
        return ONES[n]
    if n < 100:
        return TENS[n // 10] + ("" if n % 10 == 0 else "-" + ONES[n % 10])
    if n < 1000:
        return ONES[n // 100] + " hundred" + ("" if n % 100 == 0 else " " + int_words(n % 100))
    for size, name in ((10**12, "trillion"), (10**9, "billion"), (10**6, "million"), (1000, "thousand")):
        if n >= size:
            return int_words(n // size) + " " + name + ("" if n % size == 0 else " " + int_words(n % size))
    return str(n)


def number_words(token: str) -> str:
    """How a written number is said: "5.6" -> "five point six", "3,000" ->
    "three thousand", "40%" -> "forty percent", "2026" -> "twenty twenty-six".
    A dot between digits is always "point"; between letters it is "dot"."""
    percent = token.endswith("%")
    digits = token.rstrip("%")
    whole, _, frac = digits.partition(".")
    plain = whole.replace(",", "")
    if not plain.isdigit() or (frac and not frac.isdigit()):
        return token
    n = int(plain)
    if not frac and not percent and "," not in whole and len(plain) == 4 and 1100 <= n <= 2099 and n % 1000 >= 10:
        said = int_words(n // 100) + " " + (int_words(n % 100) if n % 100 >= 10 else "oh " + ONES[n % 100])
    else:
        said = int_words(n)
    if frac:
        said += " point " + " ".join(ONES[int(c)] for c in frac)
    return said + (" percent" if percent else "")


MONEY = re.compile(r"\$(\d[\d,]*(?:\.\d+)?)(k|m|bn|b|million|billion|trillion)?", re.I)
SCALE = {"k": "thousand", "m": "million", "b": "billion", "bn": "billion"}
# Units written straight after a number ("24h", "300ms", "1.5MB", "10x").
UNITS = {
    "ms": ("millisecond", "milliseconds"), "h": ("hour", "hours"), "hr": ("hour", "hours"), "hrs": ("hour", "hours"),
    "min": ("minute", "minutes"), "mins": ("minute", "minutes"), "sec": ("second", "seconds"), "secs": ("second", "seconds"),
    "kb": ("kilobyte", "kilobytes"), "mb": ("megabyte", "megabytes"), "gb": ("gigabyte", "gigabytes"), "tb": ("terabyte", "terabytes"),
    "k": ("thousand", "thousand"), "x": ("times", "times"), "fps": ("frame per second", "frames per second"),
    "px": ("pixel", "pixels"), "mbps": ("megabit per second", "megabits per second"), "gbps": ("gigabit per second", "gigabits per second"),
}
WITH_UNIT = re.compile(r"(\d[\d,]*(?:\.\d+)?)(" + "|".join(sorted(UNITS, key=len, reverse=True)) + r")", re.I)
VERSION = re.compile(r"(v)?(\d+(?:\.\d+)*)")  # lower-case v only: "V8" is a name
ID = re.compile(r"([A-Za-z]{2,})-(\d{4})-(\d{4,})")  # CVE-2026-109618, GHSA-style ids


def digits_words(digits: str) -> str:
    return " ".join(ONES[int(c)] for c in digits)


def speak_numbers(core: str) -> str | None:
    """Say every number inside a word, and split it from letters around it:
    "GPT-5.6" -> "GPT five point six", "$75" -> "seventy-five dollars",
    "$1.5M" -> "one point five million dollars", "24h" -> "twenty-four hours",
    "v2.1.0" -> "version two point one point zero", "CVE-2026-109618" ->
    "CVE twenty twenty-six one zero nine six one eight". None when there is
    no number."""
    if not re.search(r"\d", core):
        return None
    if m := ID.fullmatch(core):
        return f"{m.group(1)} {number_words(m.group(2))} {digits_words(m.group(3))}"
    if m := VERSION.fullmatch(core):
        parts = m.group(2).split(".")
        # A "v" or two or more dots means a version: every part is a whole
        # number ("3.10" after a v is "three point ten").
        if m.group(1) or len(parts) > 2:
            return ("version " if m.group(1) else "") + " point ".join(int_words(int(p)) if len(p) < 6 else digits_words(p) for p in parts)
    if m := WITH_UNIT.fullmatch(core):
        singular, plural = UNITS[m.group(2).lower()]
        return number_words(m.group(1)) + " " + (singular if m.group(1) == "1" else plural)
    core = MONEY.sub(lambda m: " " + number_words(m.group(1)) + (" " + SCALE.get(m.group(2).lower(), m.group(2).lower()) if m.group(2) else "") + " dollars ", core)
    # A long run of digits with no commas is an id, read digit by digit.
    said = re.sub(r"\d[\d,]*(?:\.\d+)?%?", lambda m: " " + (digits_words(m.group(0)) if re.fullmatch(r"\d{6,}", m.group(0)) else number_words(m.group(0).rstrip(","))) + " ", core)
    said = re.sub(r"(?<=\s)-|-(?=\s)", " ", said)
    return " ".join(said.split())


# Said the same way in every reel; an entry in a pronunciation list wins.
BUILT_IN = [("vs", "versus"), ("e.g.", "for example"), ("i.e.", "that is"), ("etc.", "et cetera")]
SPLIT = re.compile(r"^([\"'“‘(\[{]*)(.*?)([.,!?;:\"'”’)\]}]*)$")


def split_token(word: str) -> tuple[str, str, str]:
    """Leading punctuation, the word itself, trailing punctuation. Symbols
    that belong to a name stay with it: "C#", "C++", ".NET", "$75", "40%"."""
    lead, core, trail = SPLIT.match(word).groups()
    if not core:  # all punctuation ("..."): keep it as is
        return "", word, ""
    return lead, core, trail


def is_acronym(word: str) -> bool:
    """All capitals, at least two letters ("US", "AI", "GPT"): matched with
    case, so an entry for "US" never touches the word "us"."""
    letters = re.sub(r"[^A-Za-z]", "", word)
    return len(letters) >= 2 and letters.isupper()


class Lexicon:
    """A pronunciation list, looked up by word (or run of words)."""

    def __init__(self, pairs: list):
        self.entries: dict[tuple, list[tuple[tuple | None, str]]] = {}
        for k, v in list(BUILT_IN) + list(pairs or []):
            cores = tuple(split_token(w)[1] for w in words_of(k))
            v = (v or "").strip()
            if not cores or not all(cores) or not v:
                continue
            exact = cores if any(is_acronym(c) for c in cores) else None
            key = tuple(c.lower() for c in cores)
            # A later entry for the same word replaces an earlier one.
            self.entries[key] = [e for e in self.entries.get(key, []) if e[0] != exact] + [(exact, v)]
        self.longest = max((len(k) for k in self.entries), default=1)

    def find(self, cores: tuple) -> str | None:
        options = self.entries.get(tuple(c.lower() for c in cores), [])
        # An acronym entry matched with case beats a plain one for the same word.
        return next((say for exact, say in options if exact == cores), None) \
            or next((say for exact, say in options if exact is None), None)


def say_word(core: str, lex: Lexicon) -> str | None:
    """How one word with no entry of its own is said, or None to leave it."""
    if (m := re.fullmatch(r"(.+?)(['’]s)", core)) and (say := lex.find((m.group(1),))):
        return say + m.group(2)  # "GPT's" uses the entry for "GPT"
    if re.search(r"[A-Za-z].*[-/]|[-/].*[A-Za-z]", core) and not ID.fullmatch(core):
        # A compound ("GPT-5.6-Cyber", "Cache-Control"): entries apply to each part.
        parts = [p for p in re.split(r"[-/]", core) if p]
        said = [lex.find((p,)) for p in parts]
        if any(said):
            return " ".join(s or speak_numbers(p) or p for p, s in zip(parts, said))
    if m := re.fullmatch(r"([A-Za-z]+)\.js", core, re.I):
        return f"{m.group(1)} J S"  # "Next.js" -> "Next J S", never "next dot j s"
    if d := DOMAIN.match(core):
        # "dev.to" is otherwise read as one odd word ("deev-to").
        return f"{d.group(1)} dot {d.group(2)}"
    return speak_numbers(core)


def pronounce(text: str, pairs: list | Lexicon) -> tuple[str, list[tuple[str, int, int]]]:
    """Apply "word = how to say it" pairs to one line.

    Returns the text to speak and, for each original word, the range of spoken
    words it became, so captions can show the real spelling with real timing.
    Matching ignores surrounding punctuation, and ignores case except for
    acronyms; an entry may span several words ("Muse Glimmer"), longest match
    first, and applies inside compounds ("GPT" inside "GPT-5.6").
    """
    lex = pairs if isinstance(pairs, Lexicon) else Lexicon(pairs)
    words = words_of(text)
    spoken: list[str] = []
    mapping = []
    i = 0
    while i < len(words):
        for size in range(min(lex.longest, len(words) - i), 0, -1):
            group = words[i:i + size]
            split = [split_token(w) for w in group]
            lead, trail = split[0][0], split[-1][2]
            # Inside a run of words, only the outer punctuation may differ.
            if any(s[0] for s in split[1:]) or any(s[2] for s in split[:-1]):
                continue
            replacement = lex.find(tuple(s[1] for s in split))
            if not replacement and size == 1:
                replacement = say_word(split[0][1], lex)
            if replacement or size == 1:
                parts = words_of(f"{lead}{replacement}{trail}") if replacement else [group[0]]
                start = len(spoken)
                spoken.extend(parts)
                # Every original word of the group shares the spoken range.
                for w in group:
                    mapping.append((w, start, len(spoken)))
                i += size
                break
    return " ".join(spoken), mapping


NUMBER_WORDS = set(
    "zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen "
    "seventeen eighteen nineteen twenty thirty forty fifty sixty seventy eighty ninety hundred thousand "
    "million billion trillion percent point and half quarter dozen x".split()
)
# Words Whisper may write as their sound-alike ("dev dot to" heard as "dev .2").
SOUNDS_ALIKE = {"to": "two", "too": "two", "for": "four"}
FILLERS = {"uh", "um", "uhm", "ah", "er", "erm", "hmm", "hm", "mm", "huh"}


def pieces(words: list[str]) -> list[tuple[str, int]]:
    """Comparable pieces of each word, with the index of the word they came
    from. Splits hyphens and slashes ("GPT-5.6-Cyber" -> gpt, 5, 6, cyber, so a
    misheard "GPT" is caught instead of hiding inside a number), and reads a
    dot between letters as "dot" ("dev.to" and Whisper's ".to" both match
    "dev dot to")."""
    out = []
    for idx, word in enumerate(words):
        t = word.lower()
        if re.fullmatch(r"(?:[a-z]\.){2,}[a-z]?\.?[,!?;:]*", t):
            t = t.replace(".", "")  # an acronym written "G.P.T.", not a domain
        t = re.sub(r"(?<=[a-z])\.(?=[a-z])|^\.(?=[a-z0-9])", " dot ", t)
        if re.match(r"^\$\d", t):
            t = t[1:] + " dollars"
        t = re.sub(r"[-/_]", " ", t)
        t = re.sub(r"(?<=\d)\.(?=\d)", " point ", t)
        for part in t.split():
            n = SOUNDS_ALIKE.get(norm(part), norm(part))
            if n:
                out.append((n, idx))
    return out


def numberish(ps: list[tuple[str, int]]) -> list[bool]:
    """Pieces left out of the comparison: joining words that come and go
    with how a number is written ("a hundred", "one hundred and five").
    Number words themselves are compared, because both sides are said the
    same way first (see said_as), so a misspoken price is caught."""
    flags = [False] * len(ps)
    for k in range(len(ps) - 1):
        if ps[k][0] == "a" and ps[k + 1][0] in NUMBER_WORDS:
            flags[k] = True
    for k in range(1, len(ps) - 1):
        if ps[k][0] == "and" and ps[k - 1][0] in NUMBER_WORDS and ps[k + 1][0] in NUMBER_WORDS:
            flags[k] = True
    return flags


def said_as(heard: list[str]) -> tuple[list[str], list[int]]:
    """Whisper's words in the form the script is spoken in ("$75" -> "seventy-five
    dollars", "GPT-5.6" -> "GPT five point six"), with the heard word each came
    from. Without this, every number Whisper wrote as digits looked wrong, so
    numbers were left out of the check altogether."""
    # Whisper writes "dev dot to" as "dev .2": that dot is spoken, not a decimal point.
    text = [re.sub(r"^\.(?=\d)", "dot ", w) for w in heard]
    out, owner = [], []
    for k, w in enumerate(text):
        said, _ = pronounce(w, [])
        for part in words_of(said):
            out.append(part)
            owner.append(k)
    return out, owner


def join_letters(ps: list[tuple[str, int]]) -> list[tuple[str, list[int]]]:
    """Pieces with the words they came from, with runs of two or more single
    letters joined, so a spelled-out "G P T" matches Whisper's "GPT" (and
    "G. P. T.") as one piece."""
    out: list[tuple[str, list[int]]] = []
    k = 0
    while k < len(ps):
        j = k
        while j < len(ps) and len(ps[j][0]) == 1 and ps[j][0].isalpha():
            j += 1
        if j - k >= 2:
            out.append(("".join(p for p, _ in ps[k:j]), sorted({w for _, w in ps[k:j]})))
            k = j
        else:
            out.append((ps[k][0], [ps[k][1]]))
            k += 1
    return out


def compare(spoken: list[str], heard: list[str]) -> dict:
    """Line up what was meant (spoken words) with what Whisper heard.

    Returns per spoken word whether it was heard, per heard word whether it
    was an extra (a filler, a mumble, a word tacked on), the number of
    mismatched pieces either way, the overall similarity, and for each spoken
    word the heard word it matched (for caption timing)."""
    heard_said, owner = said_as(heard)
    sp = pieces(spoken)
    hp = [(p, owner[w]) for p, w in pieces(heard_said)]
    sn, hn = numberish(sp), numberish(hp)
    a = join_letters([(p, w) for (p, w), num in zip(sp, sn) if not num])
    b = join_letters([(p, w) for (p, w), num in zip(hp, hn) if not num])
    matcher = difflib.SequenceMatcher(None, [p for p, _ in a], [p for p, _ in b], autojunk=False)
    a_hit, b_hit = [False] * len(a), [False] * len(b)
    spoken_to_heard: dict[int, int] = {}
    for block in matcher.get_matching_blocks():
        for k in range(block.size):
            a_hit[block.a + k] = b_hit[block.b + k] = True
            for w in a[block.a + k][1]:
                spoken_to_heard.setdefault(w, b[block.b + k][1][0])

    spoken_ok = [True] * len(spoken)
    for (p, ws), hit in zip(a, a_hit):
        if not hit:
            for w in ws:
                spoken_ok[w] = False
    # A heard word is extra when none of it matched ("$75" heard for "seventy
    # dollars" half matches: a wrong word, but part of the line, not extra).
    # Words with nothing compared ("and" inside a number) are not extra.
    matched = [True] * len(heard)
    filler = [False] * len(heard)
    for w in {w for _, ws in b for w in ws}:
        matched[w] = False
    for (p, ws), hit in zip(b, b_hit):
        for w in ws:
            matched[w] |= hit and p not in FILLERS
            filler[w] |= p in FILLERS
    heard_extra = [f or not m for m, f in zip(matched, filler)]

    return {
        "spoken_ok": spoken_ok,
        "heard_extra": heard_extra,
        "mismatches": a_hit.count(False) + b_hit.count(False),
        "ratio": matcher.ratio() if a else 1.0,
        "spoken_to_heard": spoken_to_heard,
    }


def match_ratio(spoken: list[str], heard: list[str]) -> float:
    return compare(spoken, heard)["ratio"]


def word_flags(mapping, spoken: list[str], heard: list[str]) -> list[dict]:
    """For the review page: each original word of the line, marked ok or not
    depending on whether Whisper heard it as meant."""
    ok = compare(spoken, heard)["spoken_ok"]
    return [{"text": orig, "ok": all(ok[k] for k in range(start, end))} for orig, start, end in mapping]


def caption_words(words: list[dict]) -> list[dict]:
    """Show written forms in captions even when a line was written the way it
    is said: "G P T" -> "GPT", "dev dot to" -> "dev.to", "five point six" ->
    "5.6". Merged words keep the timing of the words they replace."""
    digit = {w: str(i) for i, w in enumerate(ONES[:10])}
    bare = lambda w: re.sub(r"[^\w]", "", w["text"])
    out: list[dict] = []
    i = 0
    while i < len(words):
        w = words[i]
        # Spelled letters: two or more single capitals in a row.
        j = i
        while j < len(words) and re.fullmatch(r"[A-Z]", bare(words[j])) and (j == i or not re.search(r"[.,!?;:]$", words[j - 1]["text"])):
            j += 1
        if j - i >= 2:
            out.append({"text": "".join(bare(x) for x in words[i:j]) + re.sub(r"^.*?([^\w]*)$", r"\1", words[j - 1]["text"]),
                        "start": words[i]["start"], "end": words[j - 1]["end"]})
            i = j
            continue
        # "dev dot to" (only with a known ending) and "five point six".
        if i + 2 < len(words):
            mid = bare(words[i + 1]).lower()
            left, right = bare(w), bare(words[i + 2])
            tail = re.sub(r"^.*?([^\w]*)$", r"\1", words[i + 2]["text"])
            joined = None
            if mid == "dot" and left and DOMAIN.match(f"{left}.{right}"):
                joined = f"{left}.{right}"
            elif mid == "point" and left.lower() in digit and right.lower() in digit:
                joined = f"{digit[left.lower()]}.{digit[right.lower()]}"
            if joined:
                out.append({"text": joined + tail, "start": w["start"], "end": words[i + 2]["end"]})
                i += 3
                continue
        out.append(w)
        i += 1
    return out


def time_words(mapping, spoken: list[str], heard: list[tuple[str, float, float]], speech_seconds: float, match: float = 1.0) -> list[dict]:
    """Time each original word for the captions. Spoken words are matched to
    Whisper's words in order; an original word spans the times of the spoken
    words it became. Words Whisper missed share the gaps around them.

    Whisper's times are only trusted when the take matched the script well,
    and only while they move forward; otherwise words are spread evenly over
    the speech, which looks calm rather than jumpy."""
    spoken_times: list[tuple[float, float] | None] = [None] * len(spoken)
    if match >= TRUST_TIMING:
        last_end = 0.0
        pairs = compare(spoken, [w for w, _, _ in heard])["spoken_to_heard"]
        for k in sorted(pairs):
            _, s, e = heard[pairs[k]]
            if s + 0.02 < last_end or e <= s:
                continue  # out of order or empty: let the gap filler time it
            spoken_times[k] = (s, max(e, s + 0.08))
            last_end = e

    out = []
    for orig, start, end in mapping:
        known = [t for t in spoken_times[start:end] if t]
        out.append({"text": orig, "start": known[0][0] if known else None, "end": known[-1][1] if known else None})

    # Fill words Whisper did not match: share the gap between the previous
    # word's end and the next timed word's start, weighted by word length.
    i = 0
    while i < len(out):
        if out[i]["start"] is not None:
            i += 1
            continue
        j = i
        while j < len(out) and out[j]["start"] is None:
            j += 1
        gap_start = out[i - 1]["end"] if i > 0 else 0.0
        gap_end = out[j]["start"] if j < len(out) else speech_seconds
        if gap_end <= gap_start:
            gap_end = gap_start + 0.25 * (j - i)
        weights = [len(norm(out[k]["text"])) + 2 for k in range(i, j)]
        t = gap_start
        for k, w in zip(range(i, j), weights):
            d = (gap_end - gap_start) * w / sum(weights)
            out[k]["start"], out[k]["end"] = t, t + d
            t += d
        i = j

    return [{"text": w["text"], "start": round(w["start"] + LEAD, 3), "end": round(w["end"] + LEAD, 3)} for w in out]


# ── Audio ───────────────────────────────────────────────────────────────────

def floor_for(scene: dict) -> float:
    return 4.0 if scene.get("visual") == "code" else 2.0


def to_wav(src: pathlib.Path, dst: pathlib.Path) -> None:
    subprocess.run(
        ["ffmpeg", "-y", "-loglevel", "error", "-i", str(src), "-ac", "1", "-ar", str(SR), str(dst)],
        check=True,
    )


def liveliest_window(speech: np.ndarray, seconds: float = 12.0) -> int:
    """Start sample of a lively but typical stretch of speech. Windows are
    ranked by how much the voiced pitch moves; the 70th percentile is used,
    not the maximum, because the most animated stretch of a recording is
    also its least typical (it made the clone sound more accented)."""
    import librosa

    win = int(seconds * SR)
    if len(speech) <= win:
        return 0
    hop = 512
    f0 = librosa.yin(speech, fmin=65, fmax=400, sr=SR, frame_length=2048, hop_length=hop)
    rms = librosa.feature.rms(y=speech, frame_length=2048, hop_length=hop)[0][: len(f0)]
    voiced = rms > np.percentile(rms, 35)
    frames_per_win = win // hop
    candidates = []
    for start in range(0, len(f0) - frames_per_win, max(1, SR // hop)):  # 1 s steps
        v = voiced[start:start + frames_per_win]
        if v.mean() < 0.5:
            continue
        pitch = np.log(f0[start:start + frames_per_win][v])
        candidates.append((float(np.std(pitch)) * float(v.mean()), start))
    if not candidates:
        return 0
    scores = np.array([c[0] for c in candidates])
    target = np.percentile(scores, 70)
    best = min(candidates, key=lambda c: abs(c[0] - target))[1]
    return best * hop


def reference_clip(sample: pathlib.Path) -> pathlib.Path:
    import librosa

    # A distinct name: the upload itself may already be voice_sample.wav, and
    # ffmpeg refuses to write over its own input.
    full = RENDER / "voice_sample_24k_mono.wav"
    to_wav(sample, full)
    audio, _ = librosa.load(full, sr=SR)
    intervals = librosa.effects.split(audio, top_db=30)
    speech = np.concatenate([audio[s:e] for s, e in intervals]) if len(intervals) else audio
    start = liveliest_window(speech)
    window = speech[start:start + SR * 12]
    # Chatterbox reads the start of the reference for delivery (6-10 s) but
    # averages its speaker embedding over the whole file, so the rest of the
    # recording follows the window: it anchors the timbre and accent.
    rest = np.concatenate([speech[:start], speech[start + SR * 12:]])[: SR * 48]
    clip = np.concatenate([window, rest])
    out = RENDER / "reference.wav"
    sf.write(out, clip, SR)
    print(f"Reference: 12 s delivery window from {start / SR:.0f} s into the recording, "
          f"{len(clip) / SR:.0f} s in total for the voice print")
    REFERENCE_INFO.update({"window_start": round(start / SR, 1), "total_seconds": round(len(clip) / SR, 1)})
    return out


def load_tts(reference: pathlib.Path, voice: dict):
    from chatterbox.tts import ChatterboxTTS

    model = ChatterboxTTS.from_pretrained(device="cpu")
    model.prepare_conditionals(str(reference), exaggeration=voice["exaggeration"])
    return model


def load_asr():
    from faster_whisper import WhisperModel

    return WhisperModel("base.en", device="cpu", compute_type="int8")


def transcribe(asr, audio: np.ndarray) -> list[tuple[str, float, float]]:
    import librosa

    audio16 = librosa.resample(audio, orig_sr=SR, target_sr=16000).astype(np.float32)
    # Whisper normally leaves hesitations out of its text. A prompt that
    # contains fillers makes it write them down, so an "uh" before a line is
    # caught and cut instead of passing as a clean take.
    segments, _ = asr.transcribe(audio16, language="en", word_timestamps=True, beam_size=1, condition_on_previous_text=False,
                                 initial_prompt="Umm, uh, let me think, hmm. Uh, okay, so, ah.")
    return [(w.word.strip(), float(w.start), float(w.end)) for seg in segments for w in (seg.words or [])]


NON_SPEECH = re.compile(r"^[\[(*].*[\])*]$")  # Whisper's "[laughs]", "(excited)", "*sighs*"


def split_non_speech(heard: list[tuple[str, float, float]]):
    """Separate real words from Whisper's descriptions of non-speech sounds."""
    words = [h for h in heard if not NON_SPEECH.match(h[0]) and norm(h[0])]
    return words, len(heard) - len(words)


def take_quality(audio: np.ndarray, words: list[tuple[str, float, float]], n_spoken: int) -> dict:
    """Pace (words per second between first and last word) and seconds of
    loud audio that no word accounts for: gasps, laughs, "excited" noises."""
    import librosa

    if not words:
        return {"pace": 0.0, "noise": len(audio) / SR, "start": 0.0, "end": len(audio) / SR}
    start, end = words[0][1], words[-1][2]
    pace = n_spoken / max(0.5, end - start)

    hop = 256
    rms = librosa.feature.rms(y=audio, frame_length=1024, hop_length=hop)[0]
    times = np.arange(len(rms)) * hop / SR
    loud = rms > 0.3 * np.percentile(rms, 90)
    covered = np.zeros_like(loud)
    for _, s, e in words:
        covered |= (times >= s - 0.12) & (times <= e + 0.12)
    noise = float(np.sum(loud & ~covered)) * hop / SR
    return {"pace": pace, "noise": noise, "start": start, "end": end}


def take_score(cmp: dict, quality: dict, sounds: int) -> float:
    """Start from 1 and take 0.1 off per word heard wrong, missing, or extra
    (a filler like "uh", a mumble, a word tacked on), so a single bad word
    forces another take. Then penalties for non-word sound and rushing."""
    score = 1.0 - 0.1 * cmp["mismatches"]
    score -= 0.4 * max(0.0, quality["noise"] - 0.25)       # more than a breath of non-word sound
    score -= 0.15 * sounds                                  # Whisper heard a laugh/gasp/etc.
    score -= 0.2 * max(0.0, quality["pace"] - MAX_PACE)     # rushed
    return score


def line_bounds(n_spoken: int, heard: list[tuple[str, float, float]], cmp: dict):
    """Which heard words belong to the line, and where anything before or
    after it starts or ends (for cutting).

    The line runs from the first to the last heard word that matched the
    script, plus, at either edge, as many unmatched heard words as there are
    unmatched script words there: a misheard last word ("bio" heard as
    "bile") is still the last word of the line. Only words beyond those
    (a tacked-on "Dental?", an "um" before the line) are cut.

    The previous version treated every unmatched word at the edges as extra,
    so whenever Whisper misheard the final word, the cut landed at its start
    and the word was chopped in half.

    Returns (line words, end of what to cut before, start of what to cut after)."""
    if not heard:
        return heard, None, None
    s2h = cmp["spoken_to_heard"]
    if not s2h:
        return heard, None, None
    first_h, last_h = min(s2h.values()), max(s2h.values())
    missing_head = min(s2h)                  # script words before the first match
    missing_tail = n_spoken - 1 - max(s2h)   # script words after the last match

    def take(words, n):
        kept = []
        for w in words:
            if len(kept) == n or norm(w[0]) in FILLERS:
                break
            kept.append(w)
        return kept

    tail = take(heard[last_h + 1:], missing_tail)
    head = take(list(reversed(heard[:first_h])), missing_head)[::-1]
    line = head + heard[first_h:last_h + 1] + tail
    after = heard[last_h + 1 + len(tail):]
    before = heard[:first_h - len(head)]
    return line, (before[-1][2] if before else None), (after[0][1] if after else None)


QUIET_DB = -30.0       # below the line's speech level by this much counts as a pause
FRAME = 0.01           # seconds per loudness frame


def frame_db(audio: np.ndarray, level: float) -> np.ndarray:
    """Loudness of each 10 ms frame in dB relative to the line's speech level."""
    n = int(FRAME * SR)
    frames = audio[: len(audio) // n * n].reshape(-1, n)
    return 20 * np.log10(np.sqrt(np.mean(frames ** 2, axis=1)) + 1e-9) - 20 * np.log10(level)


def speech_level(audio: np.ndarray) -> float:
    n = int(FRAME * SR)
    frames = audio[: len(audio) // n * n].reshape(-1, n)
    return float(np.percentile(np.sqrt(np.mean(frames ** 2, axis=1)), 90)) + 1e-9


def quiet_gap(audio: np.ndarray, t0: float, t1: float, level: float, min_len: float = 0.06, last_gap: bool = False):
    """The first (or last) pause of at least min_len seconds between t0 and t1,
    as (start, end) in seconds, or None when the sound runs straight through."""
    db = frame_db(audio, level)
    lo, hi = max(0, int(t0 / FRAME)), min(len(db), int(np.ceil(t1 / FRAME)))
    need = max(1, int(round(min_len / FRAME)))
    gaps, run = [], None
    for k in range(lo, hi):
        if db[k] < QUIET_DB:
            run = k if run is None else run
        else:
            if run is not None and k - run >= need:
                gaps.append((run * FRAME, k * FRAME))
            run = None
    if run is not None and hi - run >= need:
        gaps.append((run * FRAME, hi * FRAME))
    if not gaps:
        return None
    return gaps[-1] if last_gap else gaps[0]


def run_to_quiet(audio: np.ndarray, end: int, level: float, limit: float = 0.4) -> int:
    """Move a cut point forward to the next quiet frame if it lands mid-sound."""
    db = frame_db(audio, level)
    n = int(FRAME * SR)
    k = max(0, end // n - 1)
    stop = min(len(db), k + int(limit / FRAME))
    while k < stop and db[k] >= QUIET_DB:
        k += 1
    return int(min(len(audio), max(end, (k + 1) * n)))


def trim_to_words(audio: np.ndarray, words, quality: dict):
    """Drop separate bursts of sound before the first word or after the last
    one (an excited noise, a breath), without touching the words themselves.

    Boundaries come from the audio's own sound segments, not Whisper's word
    times: Whisper marks word endings early, and cutting there clipped the
    last syllable of every line."""
    import librosa

    segments = librosa.effects.split(audio, top_db=40, frame_length=1024, hop_length=256)
    first, last = quality["start"], quality["end"]
    keep = [(int(s), int(e)) for s, e in segments if s / SR <= last + 0.25 and e / SR >= first - 0.25]
    info = {"trimmed_start_ms": 0, "trimmed_end_ms": 0, "cut_extra_after": False, "cut_extra_before": False,
            "extended_end_ms": 0}
    if not keep:
        return audio, words, info
    start = max(0, keep[0][0] - int(0.03 * SR))
    end = min(len(audio), max(keep[-1][1] + int(0.12 * SR), int((last + 0.3) * SR)))
    level = speech_level(audio)
    # Sound Whisper heard after the line ended ("Dental?") or a filler before
    # it began is cut, but only at a real pause in the audio. Whisper marks
    # word endings early, so an "extra word" straight after the line is
    # usually the tail of the last word: cutting it at Whisper's times
    # chopped the ending (render of 13 Nov 2026: three lines cut at -2 to
    # -10 dB). With no pause, the sound stays.
    if quality.get("cut_after") is not None:
        # 120 ms: a stop consonant's closure inside a word ("subscri_bed") is shorter.
        gap = quiet_gap(audio, last, quality["cut_after"] + 0.15, level, min_len=0.12)
        if gap and int(gap[0] * SR) + int(0.08 * SR) < end:
            end = int(gap[0] * SR) + int(0.08 * SR)
            info["cut_extra_after"] = True
    if quality.get("cut_before") is not None:
        gap = quiet_gap(audio, max(0.0, quality["cut_before"] - 0.15), first + 0.05, level, min_len=0.08, last_gap=True)
        if gap and int(gap[1] * SR) - int(0.03 * SR) > start:
            start = int(gap[1] * SR) - int(0.03 * SR)
            info["cut_extra_before"] = True
    # Never stop while the voice is still sounding: run on to the next quiet
    # moment (at most 0.4 s), so the last word always decays naturally.
    extended = run_to_quiet(audio, end, level)
    info["extended_end_ms"] = round((extended - end) / SR * 1000)
    end = extended
    info["trimmed_start_ms"] = round(start / SR * 1000)
    info["trimmed_end_ms"] = round((len(audio) - end) / SR * 1000)
    shift = start / SR
    return audio[start:end], [(w, s - shift, e - shift) for w, s, e in words], info


ENDING_LOUD_DB = -20.0  # end of a line louder than this (vs its speech) was cut mid-sound


def ending_check(audio: np.ndarray, words) -> dict:
    """How a kept line ends, for the log and the review page: how loud its
    last 40 ms are compared with its speech (a word that decays naturally is
    far quieter; a chopped one is not), and how long the audio runs past the
    last word Whisper placed. Needs no script text, so it is safe to log."""
    n = int(0.04 * SR)
    if len(audio) < n * 3:
        return {"tail_db": None, "after_last_word_ms": None, "clipped": False}
    frames = audio[: len(audio) // n * n].reshape(-1, n)
    rms = np.sqrt(np.mean(frames ** 2, axis=1)) + 1e-9
    speech = np.percentile(rms, 90)
    tail_db = float(20 * np.log10(rms[-1] / speech))
    after = round((len(audio) / SR - words[-1][2]) * 1000) if words else None
    return {"tail_db": round(tail_db, 1), "after_last_word_ms": None if after is None else int(after), "clipped": bool(tail_db > ENDING_LOUD_DB)}


def fade(audio: np.ndarray) -> np.ndarray:
    """Short fade in and a softer fade out, so lines never start or stop with
    a click (the "mic switching on and off")."""
    audio = audio.copy()
    n_in, n_out = min(len(audio) // 2, int(FADE_IN * SR)), min(len(audio) // 2, int(FADE_OUT * SR))
    if n_in:
        audio[:n_in] *= np.linspace(0, 1, n_in, dtype=np.float32)
    if n_out:
        audio[-n_out:] *= np.linspace(1, 0, n_out, dtype=np.float32) ** 2
    return audio


def slow_down(audio: np.ndarray, words, pace: float):
    """Gently slow a rushed take towards TARGET_PACE with ffmpeg's atempo
    (pitch preserved), never below MAX_SLOWDOWN speed."""
    if pace <= MAX_PACE:
        return audio, words
    tempo = max(MAX_SLOWDOWN, TARGET_PACE / pace)
    src, dst = RENDER / "take_in.wav", RENDER / "take_slow.wav"
    sf.write(src, audio, SR)
    subprocess.run(["ffmpeg", "-y", "-loglevel", "error", "-i", str(src), "-filter:a", f"atempo={tempo:.3f}", str(dst)], check=True)
    slowed, _ = sf.read(dst, dtype="float32")
    print(f"  slowed from {pace:.1f} to {pace * tempo:.1f} words/s")
    return slowed, [(w, s / tempo, e / tempo) for w, s, e in words]


def letters_joined(text: str) -> str:
    """The same line with spelled letters as one acronym: "G P T five" ->
    "GPT five". The voice garbles either form now and then, so takes
    alternate between the two and the checker treats them as equal."""
    return re.sub(r"\b[A-Z](?: [A-Z]\b)+", lambda m: m.group(0).replace(" ", ""), text)


def speak(model, asr, text: str, voice: dict, seed: int, energy: dict | None = None):
    """Up to TAKES attempts at one line; keep the cleanest. Each take is judged
    on what Whisper hears (every word as written, no fillers or extra words,
    no non-word sounds, natural pace). `energy` overrides exaggeration and
    cfg_weight for this line (hook and end lines get more).
    Returns (audio, heard words, match ratio of the kept take, report info)."""
    import librosa
    import torch

    spoken = words_of(text)
    forms = [text] + ([letters_joined(text)] if letters_joined(text) != text else [])
    best = None
    takes = []
    stopped = None
    previous_wrong = None
    for take in range(TAKES):
        torch.manual_seed(seed + take)
        params = {**voice, **(energy or {})}
        wav = model.generate(
            forms[take % len(forms)],
            exaggeration=params["exaggeration"],
            cfg_weight=params["cfg_weight"],
            temperature=params["temperature"],
        )
        audio = wav.squeeze(0).cpu().numpy().astype(np.float32)
        audio, _ = librosa.effects.trim(audio, top_db=50)  # only true silence; the tail of the last word stays
        heard_all, sounds = split_non_speech(transcribe(asr, audio))
        cmp = compare(spoken, [w for w, _, _ in heard_all])
        match = cmp["ratio"]
        words, cut_before, cut_after = line_bounds(len(spoken), heard_all, cmp)
        quality = take_quality(audio, words, len(spoken))
        quality["cut_before"], quality["cut_after"] = cut_before, cut_after
        score = take_score(cmp, quality, sounds)
        print(f"  take {take + 1}: {len(audio) / SR:.1f} s, {cmp['mismatches']} wrong/extra words, match {match:.2f}, pace {quality['pace']:.1f} w/s, "
              f"extra sound {quality['noise']:.1f} s{f', {sounds} non-word sounds' if sounds else ''}, score {score:.2f}")
        takes.append({"take": take + 1, "seconds": round(len(audio) / SR, 2), "match": round(match, 3), "wrong_words": cmp["mismatches"], "text": forms[take % len(forms)],
                      "pace": round(quality["pace"], 2), "extra_sound": round(quality["noise"], 2),
                      "non_word_sounds": sounds, "score": round(score, 3)})
        if best is None or score > best["score"]:
            best = {"audio": audio, "words": words, "match": match, "quality": quality, "score": score, "take": take + 1}
        if score >= GOOD_ENOUGH:
            break
        # The same script words missed by two takes in a row are not bad luck:
        # the voice or the checker cannot say or hear them as written, and
        # more takes only cost minutes. The word needs a pronunciation entry.
        wrong = frozenset(k for k, ok in enumerate(cmp["spoken_ok"]) if not ok)
        if wrong and wrong == previous_wrong:
            stopped = f"same {len(wrong)} word(s) missed in takes {take} and {take + 1}"
            print(f"  stopped: {stopped}; another take will not fix that, a pronunciation entry will")
            break
        previous_wrong = wrong

    audio, words = best["audio"], best["words"]
    slowed = None
    trim = None
    if words and best["match"] >= TRUST_TIMING:
        audio, words, trim = trim_to_words(audio, words, best["quality"])
        before = len(audio)
        audio, words = slow_down(audio, words, best["quality"]["pace"])
        if len(audio) != before:
            slowed = round(before / len(audio), 3)
    ending = {**ending_check(audio, words), **(trim or {})}
    info = {"takes": takes, "kept_take": best["take"], "slowed_to": slowed, "ending": ending, "stopped_early": stopped}
    return audio, words, best["match"], info


def biquad(kind: str, f: float, gain_db: float = 0.0, q: float = 0.707) -> np.ndarray:
    """One RBJ-cookbook filter section as a scipy "sos" row."""
    a_ = 10 ** (gain_db / 40)
    w = 2 * np.pi * f / SR
    cw, alpha = np.cos(w), np.sin(w) / (2 * q)
    if kind == "peak":
        b = [1 + alpha * a_, -2 * cw, 1 - alpha * a_]
        a = [1 + alpha / a_, -2 * cw, 1 - alpha / a_]
    elif kind == "highshelf":
        sq = 2 * np.sqrt(a_) * alpha
        b = [a_ * ((a_ + 1) + (a_ - 1) * cw + sq), -2 * a_ * ((a_ - 1) + (a_ + 1) * cw), a_ * ((a_ + 1) + (a_ - 1) * cw - sq)]
        a = [(a_ + 1) - (a_ - 1) * cw + sq, 2 * ((a_ - 1) - (a_ + 1) * cw), (a_ + 1) - (a_ - 1) * cw - sq]
    elif kind == "highpass":
        b = [(1 + cw) / 2, -(1 + cw), (1 + cw) / 2]
        a = [1 + alpha, -2 * cw, 1 - alpha]
    else:  # lowpass
        b = [(1 - cw) / 2, 1 - cw, (1 - cw) / 2]
        a = [1 + alpha, -2 * cw, 1 - alpha]
    return np.array(b + a) / a[0]


def polish(audio: np.ndarray) -> np.ndarray:
    """A light voice EQ: cut rumble, add a little warmth, ease the harsh
    presence band and soften the metallic top end the voice model adds,
    which is most of what makes a cloned voice sound robotic."""
    try:
        from scipy.signal import sosfiltfilt

        sos = np.stack([
            biquad("highpass", 70),
            biquad("peak", 180, 0.75, 0.9),
            biquad("peak", 3200, -0.75, 1.0),
            biquad("highshelf", 7500, -1.5),
            biquad("lowpass", 11000),
        ])
        # Run forward and back: no phase smear, and each gain counts twice.
        return sosfiltfilt(sos, audio).astype(np.float32)
    except Exception as exc:  # a nicety, never a failure
        print(f"Skipped voice EQ: {exc}")
        return audio


def normalise(audio: np.ndarray) -> np.ndarray:
    try:
        import pyloudnorm

        meter = pyloudnorm.Meter(SR)
        loudness = meter.integrated_loudness(audio)
        if np.isfinite(loudness):
            audio = pyloudnorm.normalize.loudness(audio, loudness, -16.0)
    except Exception as exc:  # loudness is a nicety, never a failure
        print(f"Skipped loudness normalisation: {exc}")
    return np.clip(audio, -0.99, 0.99)


def json_safe(o):
    """numpy numbers and flags in the report or props, as plain JSON values."""
    if isinstance(o, np.bool_):
        return bool(o)
    if isinstance(o, np.integer):
        return int(o)
    if isinstance(o, np.floating):
        return float(o)
    if isinstance(o, np.ndarray):
        return o.tolist()
    raise TypeError(f"{type(o).__name__} is not JSON serializable")


def log_scene(i: int, scene: dict) -> None:
    """One summary line per scene for the public render log: positions and
    measurements only, never script text (the repository is public). The
    words themselves are on the reel's review page. Problems are also raised
    as GitHub warnings so they show on the run's summary page."""
    flags = scene["words"]
    wrong = [k + 1 for k, w in enumerate(flags) if not w["ok"]]
    end = scene.get("ending") or {}
    final_wrong = bool(flags) and not flags[-1]["ok"]
    parts = [f"kept take {scene['kept_take']}",
             f"wrong words at {', '.join(map(str, wrong))} of {len(flags)}" + (" (incl. the final word)" if final_wrong else "") if wrong else f"all {len(flags)} words heard as written"]
    if end.get("tail_db") is not None:
        parts.append(f"line end {end['tail_db']:.0f} dB vs speech ({'CUT MID-SOUND' if end['clipped'] else 'decays cleanly'})")
    if end.get("after_last_word_ms") is not None:
        parts.append(f"audio runs {end['after_last_word_ms']} ms past the last word")
    if end.get("trimmed_end_ms") or end.get("trimmed_start_ms"):
        parts.append(f"trimmed {end.get('trimmed_start_ms', 0)} ms before / {end.get('trimmed_end_ms', 0)} ms after"
                     + (" (cut a tacked-on word)" if end.get("cut_extra_after") else ""))
    if scene.get("stopped_early"):
        parts.append(f"stopped early: {scene['stopped_early']}")
    print(f"  Scene {i} result: " + "; ".join(parts))
    if end.get("clipped"):
        print(f"::warning::Scene {i}: the line ends while still sounding ({end['tail_db']:.0f} dB), the last word may be cut")
    if wrong:
        print(f"::warning::Scene {i}: words {', '.join(map(str, wrong))} of {len(flags)} not heard as written; see the Voice check on the review page")


def main() -> None:
    dummy = "--dummy" in sys.argv
    draft = json.loads((RENDER / "draft.json").read_text())
    files = draft["files"]
    voice = {**DEFAULT_VOICE, **(draft.get("voice") or {})}

    model = asr = None
    if not dummy:
        model = load_tts(reference_clip(RENDER / files["voice_sample"]), voice)
        asr = load_asr()
        print(f"Voice: exaggeration {voice['exaggeration']}, cfg {voice['cfg_weight']}, temperature {voice['temperature']}, "
              f"{len(voice['pronunciations'])} pronunciations")

    pieces, scenes, seconds, matches, report = [], [], [], [], []
    for i, scene in enumerate(draft["scenes"], start=1):
        text = scene.get("voice_line", "").strip()
        spoken_text, mapping = pronounce(text, voice["pronunciations"])
        spoken = words_of(spoken_text)

        if dummy or not text:
            speech = np.zeros(int(SR * len(spoken) / PACE), dtype=np.float32)
            heard = []
            info = None
        else:
            print(f"Scene {i}/{len(draft['scenes'])}: {len(spoken)} words")
            energy = voice.get("lift") if scene.get("type") in ("hook", "end") else None
            speech, heard, score, info = speak(model, asr, spoken_text, voice, seed=int(voice["seed"]) * 100 + i * 10, energy=energy)
            info["energy"] = "lifted" if energy else "body"
            matches.append(score)

        if text and info is not None:
            heard_words = [w for w, _, _ in heard]
            report.append({
                "scene": i,
                "script": text,
                "spoken": spoken_text,
                "heard": " ".join(heard_words),
                "words": word_flags(mapping, spoken, heard_words),
                "match": round(matches[-1], 3),
                **info,
            })
            log_scene(i, report[-1])

        speech_seconds = len(speech) / SR
        length = max(floor_for(scene), LEAD + speech_seconds + BREATH)
        clip = np.zeros(int(round(length * SR)), dtype=np.float32)
        start = int(LEAD * SR)
        clip[start:start + len(speech)] = fade(speech) if len(speech) else speech

        pieces.append(clip)
        seconds.append(round(length, 2))
        scenes.append({
            **scene,
            "duration_seconds": round(length, 3),
            "words": caption_words(time_words(mapping, spoken, heard, speech_seconds, matches[-1] if (text and not dummy) else 1.0)) if text else [],
        })

    voice_audio = np.concatenate(pieces)
    if not dummy:
        voice_audio = normalise(polish(voice_audio))
        # A faint, constant noise floor: pauses sound like a quiet room rather
        # than the signal cutting out between lines.
        rng = np.random.default_rng(7)
        voice_audio = np.clip(voice_audio + rng.normal(0, ROOM_TONE, len(voice_audio)).astype(np.float32), -0.99, 0.99)
    sf.write(RENDER / "voice.wav", voice_audio, SR)

    props = {
        "title": draft["title"],
        "scenes": scenes,
        "voice": "render/voice.wav",
        "featuredImage": f"render/{files['featured']}" if files.get("featured") else None,
        "portrait": f"render/{files['portrait']}" if files.get("portrait") else None,
        "brand": draft["brand"],
    }
    (RENDER / "props.json").write_text(json.dumps(props, indent=2, default=json_safe))
    (RENDER / "meta.json").write_text(json.dumps({"duration_seconds": round(sum(seconds) + 0.5, 2), "scene_seconds": seconds}, default=json_safe))
    # What the speech check heard, per scene, shown on the review page.
    (RENDER / "report.json").write_text(json.dumps({
        "voice": {k: voice[k] for k in ("exaggeration", "cfg_weight", "temperature")},
        "reference": REFERENCE_INFO,
        "scenes": report,
    }, default=json_safe))
    low = [f"{i + 1}" for i, m in enumerate(matches) if m < GOOD_ENOUGH]
    print(f"Voiceover: {sum(seconds):.1f} s across {len(seconds)} scenes"
          + (f"; scenes {', '.join(low)} never matched cleanly" if low else ""))


if __name__ == "__main__":
    main()
