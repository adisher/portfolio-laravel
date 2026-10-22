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
SR = 24000            # Chatterbox's output rate
PACE = 2.5            # words per second, same as config/social.php
BREATH = 0.35         # pause after each scene's line
LEAD = 0.15           # pause before each scene's line
TAKES = 3             # attempts per scene
GOOD_ENOUGH = 0.88    # take score that stops further takes
TRUST_TIMING = 0.75   # below this match, captions use even timing instead of Whisper's
TARGET_PACE = 2.7     # words per second a rushed take is slowed towards
MAX_PACE = 2.95       # faster than this counts as rushed
MAX_SLOWDOWN = 0.85   # never slow a take below 85% speed (it starts to sound stretched)
DEFAULT_VOICE = {"exaggeration": 0.55, "cfg_weight": 0.45, "temperature": 0.65, "pronunciations": [], "seed": 1}


# ── Text ────────────────────────────────────────────────────────────────────

def words_of(text: str) -> list[str]:
    return [w for w in re.split(r"\s+", (text or "").strip()) if w]


def norm(word: str) -> str:
    return re.sub(r"[^a-z0-9]", "", word.lower())


def pronounce(text: str, pairs: list) -> tuple[str, list[tuple[str, int, int]]]:
    """Apply "word = how to say it" pairs to one line.

    Returns the text to speak and, for each original word, the range of spoken
    words it became, so captions can show the real spelling with real timing.
    Matching is per word, ignores case and surrounding punctuation.
    """
    lookup = {norm(k): v for k, v in pairs if norm(k)}
    spoken: list[str] = []
    mapping = []
    for word in words_of(text):
        m = re.match(r"^(\W*)(.*?)(\W*)$", word)
        lead, core, trail = m.groups() if m else ("", word, "")
        replacement = lookup.get(norm(core))
        parts = words_of(f"{lead}{replacement}{trail}") if replacement else [word]
        mapping.append((word, len(spoken), len(spoken) + len(parts)))
        spoken.extend(parts)
    return " ".join(spoken), mapping


NUMBER_WORDS = set(
    "zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen "
    "seventeen eighteen nineteen twenty thirty forty fifty sixty seventy eighty ninety hundred thousand "
    "million billion trillion percent point and".split()
)


def is_number(word: str) -> bool:
    return any(c.isdigit() for c in word) or word in NUMBER_WORDS


def match_ratio(spoken: list[str], heard: list[str]) -> float:
    """How closely Whisper's transcript matches what was meant to be said.
    Numbers are ignored on both sides: "three thousand" is often transcribed
    "3,000", which is not a mistake."""
    a = [w for w in map(norm, spoken) if w and not is_number(w)]
    b = [w for w in map(norm, heard) if w and not is_number(w)]
    if not a:
        return 1.0
    return difflib.SequenceMatcher(None, a, b, autojunk=False).ratio()


def time_words(mapping, spoken: list[str], heard: list[tuple[str, float, float]], speech_seconds: float, match: float = 1.0) -> list[dict]:
    """Time each original word for the captions. Spoken words are matched to
    Whisper's words in order; an original word spans the times of the spoken
    words it became. Words Whisper missed share the gaps around them.

    Whisper's times are only trusted when the take matched the script well,
    and only while they move forward; otherwise words are spread evenly over
    the speech, which looks calm rather than jumpy."""
    a = [norm(w) for w in spoken]
    b = [norm(w) for w, _, _ in heard]
    spoken_times: list[tuple[float, float] | None] = [None] * len(spoken)
    if match >= TRUST_TIMING:
        last_end = 0.0
        for block in difflib.SequenceMatcher(None, a, b, autojunk=False).get_matching_blocks():
            for k in range(block.size):
                _, s, e = heard[block.b + k]
                if s + 0.02 < last_end or e <= s:
                    continue  # out of order or empty: let the gap filler time it
                spoken_times[block.a + k] = (s, max(e, s + 0.08))
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
    """Start sample of the most animated stretch of speech: the window whose
    voiced pitch moves the most, among windows that are mostly voiced."""
    import librosa

    win = int(seconds * SR)
    if len(speech) <= win:
        return 0
    hop = 512
    f0 = librosa.yin(speech, fmin=65, fmax=400, sr=SR, frame_length=2048, hop_length=hop)
    rms = librosa.feature.rms(y=speech, frame_length=2048, hop_length=hop)[0][: len(f0)]
    voiced = rms > np.percentile(rms, 35)
    frames_per_win = win // hop
    best, best_score = 0, -1.0
    for start in range(0, len(f0) - frames_per_win, max(1, SR // hop)):  # 1 s steps
        v = voiced[start:start + frames_per_win]
        if v.mean() < 0.5:
            continue
        pitch = np.log(f0[start:start + frames_per_win][v])
        score = float(np.std(pitch)) * float(v.mean())
        if score > best_score:
            best, best_score = start, score
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
    clip = speech[start:start + SR * 12]
    out = RENDER / "reference.wav"
    sf.write(out, clip, SR)
    print(f"Reference clip: {len(clip) / SR:.1f} s of speech, from {start / SR:.0f} s into the recording")
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
    segments, _ = asr.transcribe(audio16, language="en", word_timestamps=True, beam_size=1, condition_on_previous_text=False)
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


def take_score(match: float, quality: dict, sounds: int) -> float:
    """Word match, minus penalties for non-word sounds and for rushing."""
    score = match
    score -= 0.4 * max(0.0, quality["noise"] - 0.25)       # more than a breath of non-word sound
    score -= 0.15 * sounds                                  # Whisper heard a laugh/gasp/etc.
    score -= 0.2 * max(0.0, quality["pace"] - MAX_PACE)     # rushed
    return score


def trim_to_words(audio: np.ndarray, words, quality: dict):
    """Cut sounds before the first word and after the last one."""
    start = max(0, int((quality["start"] - 0.08) * SR))
    end = min(len(audio), int((quality["end"] + 0.15) * SR))
    shift = start / SR
    return audio[start:end], [(w, s - shift, e - shift) for w, s, e in words]


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


def speak(model, asr, text: str, voice: dict, seed: int):
    """Up to TAKES attempts at one line; keep the cleanest. Each take is judged
    on what Whisper hears (right words, no extra sounds, natural pace).
    Returns (audio, heard words, match ratio of the kept take)."""
    import librosa
    import torch

    spoken = words_of(text)
    best = None
    for take in range(TAKES):
        torch.manual_seed(seed + take)
        wav = model.generate(
            text,
            exaggeration=voice["exaggeration"],
            cfg_weight=voice["cfg_weight"],
            temperature=voice["temperature"],
        )
        audio = wav.squeeze(0).cpu().numpy().astype(np.float32)
        audio, _ = librosa.effects.trim(audio, top_db=35)
        words, sounds = split_non_speech(transcribe(asr, audio))
        match = match_ratio(spoken, [w for w, _, _ in words])
        quality = take_quality(audio, words, len(spoken))
        score = take_score(match, quality, sounds)
        print(f"  take {take + 1}: {len(audio) / SR:.1f} s, match {match:.2f}, pace {quality['pace']:.1f} w/s, "
              f"extra sound {quality['noise']:.1f} s{f', {sounds} non-word sounds' if sounds else ''}, score {score:.2f}")
        if best is None or score > best["score"]:
            best = {"audio": audio, "words": words, "match": match, "quality": quality, "score": score}
        if score >= GOOD_ENOUGH:
            break

    audio, words = best["audio"], best["words"]
    if words and best["match"] >= TRUST_TIMING:
        audio, words = trim_to_words(audio, words, best["quality"])
        audio, words = slow_down(audio, words, best["quality"]["pace"])
    return audio, words, best["match"]


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

    pieces, scenes, seconds, matches = [], [], [], []
    for i, scene in enumerate(draft["scenes"], start=1):
        text = scene.get("voice_line", "").strip()
        spoken_text, mapping = pronounce(text, voice["pronunciations"])
        spoken = words_of(spoken_text)

        if dummy or not text:
            speech = np.zeros(int(SR * len(spoken) / PACE), dtype=np.float32)
            heard = []
        else:
            print(f"Scene {i}/{len(draft['scenes'])}: {len(spoken)} words")
            speech, heard, score = speak(model, asr, spoken_text, voice, seed=int(voice["seed"]) * 100 + i * 10)
            matches.append(score)

        speech_seconds = len(speech) / SR
        length = max(floor_for(scene), LEAD + speech_seconds + BREATH)
        clip = np.zeros(int(round(length * SR)), dtype=np.float32)
        start = int(LEAD * SR)
        clip[start:start + len(speech)] = speech

        pieces.append(clip)
        seconds.append(round(length, 2))
        scenes.append({
            **scene,
            "duration_seconds": round(length, 3),
            "words": time_words(mapping, spoken, heard, speech_seconds, matches[-1] if (text and not dummy) else 1.0) if text else [],
        })

    voice_audio = np.concatenate(pieces)
    sf.write(RENDER / "voice.wav", voice_audio if dummy else normalise(voice_audio), SR)

    props = {
        "title": draft["title"],
        "scenes": scenes,
        "voice": "render/voice.wav",
        "featuredImage": f"render/{files['featured']}" if files.get("featured") else None,
        "portrait": f"render/{files['portrait']}" if files.get("portrait") else None,
        "brand": draft["brand"],
    }
    (RENDER / "props.json").write_text(json.dumps(props, indent=2))
    (RENDER / "meta.json").write_text(json.dumps({"duration_seconds": round(sum(seconds) + 0.5, 2), "scene_seconds": seconds}))
    low = [f"{i + 1}" for i, m in enumerate(matches) if m < GOOD_ENOUGH]
    print(f"Voiceover: {sum(seconds):.1f} s across {len(seconds)} scenes"
          + (f"; scenes {', '.join(low)} never matched cleanly" if low else ""))


if __name__ == "__main__":
    main()
