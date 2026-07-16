# Article screenshot library

How generated articles get their images without hunting for them per article.

## The idea

An article marks where an image belongs with a placement marker. It does not
produce the image. Because each product has a **finite, stable set of screens**,
you capture that set **once**, store each in the Media library, and every article
reuses them. Capture is a defined task, never an open search.

Two marker types, produced by the generator (`AiContentService::buildWorkItemPrompt`):

- `[[screenshot: slug]]` — a product screenshot. Constrained to the product's
  screenshot library (see below), so every marker maps to a real asset.
- `[[social: description]]` — a social-proof screenshot (a reddit thread, a tweet).
  Sits under a blockquote of the same quote; if you have no screenshot, the
  blockquote stands on its own.

Reference URLs for citations are already in the work item's `hooks` and `voices`
fields, so those need no capture.

## The screenshot library (per work item)

Each work item has a `screenshots` field: a list of `slug — description` entries.
The generator may only emit `[[screenshot: slug]]` markers whose slug is in that
list. Keep the Media filename equal to the slug so drafts map 1:1 to assets.

### BioLink Pro

| Slug | What it shows | Source | Crop |
|---|---|---|---|
| `live-page` | Public bio page, default theme | Own UI (public) | Page frame, no browser chrome |
| `live-themes` | 2-3 of the 5 themes together | Own UI (public) | Tight on the pages |
| `admin-dashboard` | Admin overview after login | Own UI (admin) | Main panel |
| `analytics` | Page views + link-click counts | Own UI (admin) | The analytics card only |
| `link-editor` | Links list mid drag-and-drop | Own UI (admin) | List + a visible drag handle |
| `theme-picker` | The 5-theme selector | Own UI (admin) | The picker component |
| `profile-editor` | Photo, bio, social-link fields | Own UI (admin) | The form card |
| `vercel-deploy` | One-click Vercel deploy screen | External (Vercel) | The deploy panel, capture manually |
| `custom-domain` | Vercel custom-domain settings | External (Vercel) | The domain card, capture manually |

Own-UI shots are captured by the script below. The two external Vercel screens are
captured by hand.

## Capturing

`scripts/screenshots/` holds a Playwright capturer. Public pages run headless and
automatically; admin pages open a real browser and wait for you to log in (no
password is ever read or stored). Output is `scripts/screenshots/out/<slug>.png`.

```bash
npm install
npx playwright install chromium
node scripts/screenshots/capture.mjs
```

Then import the PNGs into the Media library, filename = slug. Set each image's `alt`
to a short description of the screen (good for accessibility and image SEO). Tighten
crops by setting a `selector` per target in `targets.biolink.json`.

## Adding a new product

1. Add a `screenshots` list (`slug — description`) to that product's work item.
2. Add a `targets.<product>.json` in `scripts/screenshots/` and run the capturer
   with it.
3. Import the results into Media with slug filenames.
