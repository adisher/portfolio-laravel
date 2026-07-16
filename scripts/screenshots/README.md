# Screenshot capture

Captures the product screenshots used in generated articles, cropped and named by
slug, so you never hunt for them per article. See `docs/screenshot-library.md` for
the full strategy and the canonical slug set.

## Setup (once)

```bash
npm install
npx playwright install chromium
```

## Run

```bash
node scripts/screenshots/capture.mjs                 # uses targets.biolink.json
node scripts/screenshots/capture.mjs targets.foo.json # another product
```

- **Public pages** capture automatically, headless.
- **Admin pages** open a real browser and pause. Log in yourself, then press Enter
  in the terminal to continue. The script never sees or stores your password.

Output lands in `scripts/screenshots/out/<slug>.png`. Import those into the Media
library keeping the slug as the filename, then a `[[screenshot: slug]]` marker in a
draft maps straight to the matching image.

## Tightening the crop

Each target can set `"selector"` to a CSS selector so only that element is captured
(e.g. just the analytics card). Leave it `null` to capture the viewport. Fill the
real admin routes and selectors in `targets.biolink.json` once you are logged in; the
committed values are safe defaults with notes.

## Not covered here

`vercel-deploy` and `custom-domain` are external Vercel screens (not BioLink's own
UI), so capture those two manually.
