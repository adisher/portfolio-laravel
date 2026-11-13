"""Tests for the text and checking helpers in voice.py (no voice model needed).

Run: python video/pipeline/test_voice.py   (or pytest video/pipeline)
"""
import pathlib
import sys
import types

import numpy as np

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
sys.modules.setdefault("soundfile", types.ModuleType("soundfile"))  # only used for file I/O

import voice as v  # noqa: E402

PAIRS = [("GPT", "G P T"), ("US", "U S"), ("C#", "C sharp"), (".NET", "dot net"), ("cache", "cash"),
         ("adilsher.pro", "Adil Sher dot pro"), ("Muse Glimmer", "Mews Glimmer")]


def said(text, pairs=PAIRS):
    return v.pronounce(text, pairs)[0]


def test_acronym_entries_match_case():
    assert said("Let us ship it in the US.") == "Let us ship it in the U S."


def test_symbols_belong_to_the_name():
    assert said("I wrote C# and C.") == "I wrote C sharp and C."
    assert said("Then .NET over the net.") == "Then dot net over the net."


def test_entries_apply_inside_compounds_and_possessives():
    assert said("GPT-5.6-Cyber beat GPT-5.") == "G P T five point six Cyber beat G P T five."
    assert said("GPT's price and Cache-Control.") == "G P T's price and cash Control."


def test_multi_word_entry_and_domain():
    assert said("Muse Glimmer is on adilsher.pro!") == "Mews Glimmer is on Adil Sher dot pro!"
    assert said("Read it on dev.to today.") == "Read it on dev dot to today."


def test_numbers_units_versions_ids():
    assert said("It costs $75, up 40%.") == "It costs seventy-five dollars, up forty percent."
    assert said("$1.5M raised in 2026.") == "one point five million dollars raised in twenty twenty-six."
    assert said("Within 24h and 300ms, 1.5MB, 10x.") == "Within twenty-four hours and three hundred milliseconds, one point five megabytes, ten times."
    assert said("Ship v2.1.0 now.") == "Ship version two point one point zero now."
    assert said("Patch CVE-2026-109618.") == "Patch CVE twenty twenty-six one zero nine six one eight."
    assert said("Over 10,000 users.") == "Over ten thousand users."


def test_built_ins():
    assert said("Next.js vs Vue.js on V8.") == "Next J S versus Vue J S on V eight."


def test_captions_keep_the_written_words():
    _, mapping = v.pronounce("GPT-5.6 is out!", PAIRS)
    assert [m[0] for m in mapping] == ["GPT-5.6", "is", "out!"]


def check(script, heard):
    spoken = v.words_of(said(script))
    return v.compare(spoken, v.words_of(heard))


def test_numbers_are_checked():
    assert check("It costs $75 a month.", "It costs $75 a month.")["mismatches"] == 0
    assert check("It costs $75 a month.", "It costs seventy dollars a month.")["mismatches"] == 1


def test_written_forms_heard_are_not_flagged():
    assert check("GPT-5.6 is up 40%.", "GPT 5.6 is up 40%.")["mismatches"] == 0
    assert check("Read it on dev.to today.", "Read it on dev .2 today.")["mismatches"] == 0
    assert check("Within 24h.", "Within 24 hours.")["mismatches"] == 0
    assert check("In 2025.", "In twenty twenty five.")["mismatches"] == 0


def timed(text, start=0.0, step=0.3):
    return [(w, start + k * step, start + k * step + 0.25) for k, w in enumerate(text.split())]


def bounds(script, heard_text):
    spoken = v.words_of(said(script))
    heard = timed(heard_text)
    cmp = v.compare(spoken, [w for w, _, _ in heard])
    return v.line_bounds(len(spoken), heard, cmp)


def test_misheard_last_word_is_kept_not_cut():
    # The bug behind the cut-off endings: "bio" heard as "bile" was treated
    # as an extra word, and the audio was cut at its start.
    line, before, after = bounds("link in my bio!", "link in my bile.")
    assert [w for w, _, _ in line] == ["link", "in", "my", "bile."]
    assert after is None and before is None


def test_tacked_on_words_and_fillers_are_still_cut():
    line, before, after = bounds("link in my bio!", "Um, link in my bio! Dental?")
    assert [w for w, _, _ in line] == ["link", "in", "my", "bio!"]
    assert before == 0.25          # end of "Um,"
    assert after == 1.5            # start of "Dental?"


def test_misheard_last_word_then_extra_word():
    line, _, after = bounds("link in my bio!", "link in my bile. Dental?")
    assert [w for w, _, _ in line][-1] == "bile."
    assert after == 1.2            # only "Dental?" is cut


def test_ending_check_flags_a_chopped_line():
    sr = v.SR
    t = np.arange(int(sr * 1.0)) / sr
    tone = (0.5 * np.sin(2 * np.pi * 220 * t)).astype(np.float32)
    decayed = tone * np.clip((1.0 - t) / 0.3, 0, 1).astype(np.float32)  # fades to silence
    words = [("word", 0.0, 0.7)]
    assert v.ending_check(tone, words)["clipped"] is True
    assert v.ending_check(decayed, words)["clipped"] is False


if __name__ == "__main__":
    tests = [f for name, f in sorted(globals().items()) if name.startswith("test_")]
    for f in tests:
        f()
        print(f"ok  {f.__name__}")
    print(f"{len(tests)} passed")
