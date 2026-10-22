"""Speak a reel's script in the owner's cloned voice and build the render props.

Usage: python voice.py [--dummy]

Reads video/public/render/draft.json (from fetch.py). Clones the voice from
the uploaded sample with Chatterbox (MIT, runs on CPU), speaks each scene's
voice_line, times every word, and writes:
  public/render/voice.wav    the full voiceover, loudness-normalised
  public/render/props.json   input props for the Remotion composition
  public/render/meta.json    total and per-scene seconds, sent back to the site
--dummy skips the model and writes silence of the estimated length, so the
rest of the pipeline can be tested anywhere.
"""
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


def words_of(text: str) -> list[str]:
    return [w for w in re.split(r"\s+", (text or "").strip()) if w]


def floor_for(scene: dict) -> float:
    return 4.0 if scene.get("visual") == "code" else 2.0


def to_wav(src: pathlib.Path, dst: pathlib.Path) -> None:
    subprocess.run(
        ["ffmpeg", "-y", "-loglevel", "error", "-i", str(src), "-ac", "1", "-ar", str(SR), str(dst)],
        check=True,
    )


def reference_clip(sample: pathlib.Path) -> pathlib.Path:
    """Chatterbox only reads the first 10 s of the reference, so give it 12 s of
    actual speech: silence trimmed, starting a second in (past any breath or
    tap at the start of the recording)."""
    import librosa

    # A distinct name: the upload itself may already be voice_sample.wav, and
    # ffmpeg refuses to write over its own input.
    full = RENDER / "voice_sample_24k_mono.wav"
    to_wav(sample, full)
    audio, _ = librosa.load(full, sr=SR)
    intervals = librosa.effects.split(audio, top_db=30)
    speech = np.concatenate([audio[s:e] for s, e in intervals]) if len(intervals) else audio
    start = SR if len(speech) > SR * 13 else 0
    clip = speech[start:start + SR * 12]
    out = RENDER / "reference.wav"
    sf.write(out, clip, SR)
    print(f"Reference clip: {len(clip) / SR:.1f} s of speech")
    return out


def load_model(reference: pathlib.Path):
    import torch
    from chatterbox.tts import ChatterboxTTS

    torch.set_num_threads(max(1, (torch.get_num_threads() or 2)))
    model = ChatterboxTTS.from_pretrained(device="cpu")
    model.prepare_conditionals(str(reference), exaggeration=0.5)
    return model


def speak(model, text: str) -> np.ndarray:
    """One scene's line. Retries once if the output is implausibly long, which
    is how a TTS loop or mumble shows up."""
    import librosa

    limit = len(words_of(text)) / 1.3 + 3.0
    for temperature in (0.7, 0.5):
        wav = model.generate(text, exaggeration=0.5, cfg_weight=0.5, temperature=temperature)
        audio = wav.squeeze(0).cpu().numpy().astype(np.float32)
        audio, _ = librosa.effects.trim(audio, top_db=35)
        if len(audio) / SR <= limit:
            return audio
        print(f"  speech ran {len(audio) / SR:.1f} s for {len(words_of(text))} words, retrying")
    return audio


def time_words(text: str, speech_seconds: float) -> list[dict]:
    """Spread the line's words across its speech, weighted by word length.
    Chatterbox gives no timestamps; this is close enough for highlighting."""
    ws = words_of(text)
    if not ws:
        return []
    weights = [len(re.sub(r"\W", "", w)) + 2 for w in ws]
    total = sum(weights)
    t, out = LEAD, []
    for w, weight in zip(ws, weights):
        d = speech_seconds * weight / total
        out.append({"text": w, "start": round(t, 3), "end": round(t + d, 3)})
        t += d
    return out


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

    model = None
    if not dummy:
        model = load_model(reference_clip(RENDER / files["voice_sample"]))

    pieces, scenes, seconds = [], [], []
    for i, scene in enumerate(draft["scenes"], start=1):
        text = scene.get("voice_line", "").strip()
        if dummy or not text:
            speech = np.zeros(int(SR * len(words_of(text)) / PACE), dtype=np.float32)
        else:
            print(f"Scene {i}/{len(draft['scenes'])}: {len(words_of(text))} words")
            speech = speak(model, text)

        speech_seconds = len(speech) / SR
        length = max(floor_for(scene), LEAD + speech_seconds + BREATH)
        clip = np.zeros(int(round(length * SR)), dtype=np.float32)
        start = int(LEAD * SR)
        clip[start:start + len(speech)] = speech

        pieces.append(clip)
        seconds.append(round(length, 2))
        scenes.append({**scene, "duration_seconds": round(length, 3), "words": time_words(text, speech_seconds)})

    voice = normalise(np.concatenate(pieces)) if not dummy else np.concatenate(pieces)
    sf.write(RENDER / "voice.wav", voice, SR)

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
    print(f"Voiceover: {sum(seconds):.1f} s across {len(seconds)} scenes")


if __name__ == "__main__":
    main()
