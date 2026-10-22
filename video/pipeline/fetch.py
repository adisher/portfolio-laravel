"""Download everything a reel render needs from the site into video/public/render.

Usage: python fetch.py <draft_id>   (env: SITE_URL, RENDER_TOKEN)

Writes draft.json, featured.<ext>, portrait.<ext> and voice_sample.<ext>.
Only the site's own render API gets the token; image URLs are public.
"""
import json
import mimetypes
import os
import pathlib
import sys
import urllib.request

OUT = pathlib.Path(__file__).resolve().parent.parent / "public" / "render"
SITE = os.environ["SITE_URL"].rstrip("/")
TOKEN = os.environ["RENDER_TOKEN"]
UA = "adilsher-reel-renderer/1.0"


def get(url: str, auth: bool) -> tuple[bytes, str]:
    headers = {"User-Agent": UA, "Accept": "*/*"}
    if auth:
        headers["Authorization"] = f"Bearer {TOKEN}"
    with urllib.request.urlopen(urllib.request.Request(url, headers=headers), timeout=60) as res:
        return res.read(), res.headers.get("Content-Type", "")


def save(name: str, url: str | None, auth: bool = False) -> str | None:
    if not url:
        return None
    try:
        body, ctype = get(url, auth)
    except Exception as exc:  # a missing image should not fail the whole reel
        print(f"Could not download {name}: {exc}")
        return None
    ext = mimetypes.guess_extension(ctype.split(";")[0].strip()) or pathlib.Path(url.split("?")[0]).suffix or ".bin"
    ext = {".jpe": ".jpg", ".jpeg": ".jpg"}.get(ext, ext)
    path = OUT / f"{name}{ext}"
    path.write_bytes(body)
    print(f"Saved {path.name} ({len(body) // 1024} KB)")
    return path.name


def main() -> None:
    draft_id = sys.argv[1]
    if not draft_id.isdigit():
        sys.exit("draft id must be numeric")

    OUT.mkdir(parents=True, exist_ok=True)
    body, _ = get(f"{SITE}/api/social/render/{draft_id}", auth=True)
    draft = json.loads(body)

    assets = draft.get("assets", {})
    draft["files"] = {
        "featured": save("featured", assets.get("featured_image")),
        "portrait": save("portrait", assets.get("portrait")),
        "voice_sample": save("voice_sample", assets.get("voice_sample"), auth=True),
    }
    if not draft["files"]["voice_sample"]:
        sys.exit("No voice sample available; upload one in Reels > Studio settings.")

    (OUT / "draft.json").write_text(json.dumps(draft, indent=2))
    print(f"Fetched reel #{draft['id']} with {len(draft['scenes'])} scenes")


if __name__ == "__main__":
    main()
