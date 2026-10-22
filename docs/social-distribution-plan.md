# Social Distribution Plan

Running doc. Updated as we work. Status legend: DONE / DOING / TODO / BLOCKED / DECIDED.

Last updated: 2026-10-21
Status: Phase 0 DONE, Phase 1 BUILT (2026-10-21, waiting on the owner's voice recording + GitHub token for the first real render). Next: Phase 2 publishing.

---

## Goal

Turn each well-performing blog post into native content for each platform, in the
format that platform rewards, with consistent branding. Every piece is reviewed and
approved by hand in the admin before it goes out.

## Decisions (2026-10-21)

- DECIDED **First platforms: Instagram and YouTube (Shorts).** Reels first.
- DECIDED **LinkedIn** (personal profile now, company page later) follows after Instagram + YouTube: PDF carousels, native video, link in first comment, one-click manual publish.
- DECIDED **No X.** Paid API (about $0.015 per post, about $0.20 with a link since April 2026) is not worth it.
- DECIDED **n8n queue API is left as is.** It was a test; the new pipeline does not use it.
- DECIDED **Manual approval of every post** in the admin. Auto-publish can be switched on later per platform.
- DECIDED **Email alert on every new reel** that finishes rendering, linking to its review page.
- DECIDED **Voice: record once, clone, use only that voice.** Engine: **B, Chatterbox (free, MIT)**, decided 2026-10-21.
- DECIDED **Branding matters.** One brand kit drives every slide and frame.
- DECIDED **Video rendering on GitHub Actions** (repo is public, so Actions minutes are free).
- DECIDED **Photos: use the existing site photos** from the Media library, main portrait `storage/media/home-header-2.webp`.

## Where we are today (analysis 2026-10-20)

- In-site publisher (Admin > Social): Facebook Page + LinkedIn drivers, one caption template per account, link-share only (platform builds the card from OG tags), each article posts once per account, manual "Post now" board.
- `social:publish` (auto) exists but is NOT scheduled. LinkedIn driver is manual-only and uses the legacy `/v2/ugcPosts` endpoint.
- "Well performing" = `blog_posts.views >= social_accounts.min_human_views`. Weak signal at ~100-150 human visits/month.
- Measurement: `utm_source=<platform>` on links; no engagement pulled back. Last check: 12 Facebook + 2 LinkedIn visits / 30 days.
- n8n claim-queue API (`posted_at`, `posted_via`) runs independently of the in-site publisher.
- Hosting: Hostinger shared hosting, `database` queue, cron scheduler. Intervention Image installed. No video encoder or headless browser expected, hence rendering off-host.

## Platform / format map

| Platform | Main format | Second format | Article link |
|---|---|---|---|
| Instagram | Reel (9:16) | Carousel 4:5 (phase 3) | Link in bio |
| YouTube Shorts | Reel (same file) | | Description |
| LinkedIn personal | PDF carousel | Native video (4:5), text + image for news | First comment |
| LinkedIn company page | Same assets, different angle, 1-2 days later | | First comment |
| Facebook Page (later) | Reel cross-post | Carousel images | In post |

Per article type:

| Type | Package |
|---|---|
| Product article (own work) | Reel built on product screenshots + carousel |
| Guide / evergreen | Reel + carousel |
| News | Short "news in 30 seconds" reel; LinkedIn text + image; no carousel |

## Brand kit

One set of Remotion templates renders reels, carousel PNGs and the LinkedIn PDF.

- Colours: midnight `#0D1B2A`, ocean `#1B3A4B`, teal accent, sunset highlight, soft greys for text (same tokens as `tailwind.config.js`).
- Font: Satoshi.
- Fixed elements: portrait (`home-header-2.webp`), name, handle, logo mark, closing card, progress bar.
- Assets per piece: featured image, product screenshot library (`docs/screenshot-library.md`), code snippets, simple stat cards.

## Reel design

- 1080x1920, 25-45 s, MP4 (H.264 + AAC). 4:5 variant later for LinkedIn feed.
- Captions burned in, word-by-word highlight synced to the voice.
- Storyboard template:

| Time | Scene | On screen |
|---|---|---|
| 0-2 s | Hook | One bold line, fast type-on |
| 2-5 s | Context | Problem or news in one sentence over featured image / screenshot |
| 5-35 s | 3-5 key points | One point per scene: big text, screenshot with zoom/pan, code or stat card |
| last 4 s | End card | Portrait, name, handle, "Full article: link in bio", logo |

- AI writes the script as scenes (`type`, `text`, `voice_line`, `duration`, `asset`). Every scene is editable in the admin before render.

## Voice

Requirement: record once, clone, always use that voice.

Finding: the **ElevenLabs free plan does not include voice cloning** and is non-commercial (attribution required). Instant Voice Cloning starts at Starter (about $5-6/month), which also adds a commercial licence.

Options:

| Option | Cost | Quality | Notes |
|---|---|---|---|
| A. ElevenLabs Starter + Instant Voice Clone | ~$5-6/mo | Best, fast | Upload the recording once in ElevenLabs, store the voice ID in admin. Render job calls the API. |
| B. Chatterbox (Resemble AI, MIT licence, open source) | Free | Good | Clones from a ~10 s reference clip. Runs on the GitHub Actions runner (CPU), adds a few minutes per render. Output carries an inaudible watermark. |

Recommendation: **B (free) as default**, A as an upgrade if B's quality is not good enough. Either way the voice is set once and reused.

Recording: one clean 1-2 minute take (quiet room, phone is fine), uploaded once in Admin > Social > Voice. Kept in **private** storage on the site, never in the repo.

## Pipeline

```
Admin: pick article for social
  -> Laravel: AI writes reel script (scenes) + Instagram caption + YouTube title/description/tags
  -> Admin "Social Studio": edit scenes, click Render
  -> Laravel: GitHub API repository_dispatch {draft_id}
  -> GitHub Actions (this repo, video/ folder):
       fetch draft JSON + photos + voice sample from site API (token)
       generate voiceover with the cloned voice, align captions
       Remotion render -> reel.mp4 + cover.jpg
       upload to site (authenticated endpoint)
  -> Laravel: draft status "ready for review", email alert
  -> Admin review page: watch, edit, re-render, approve + schedule per platform
  -> Scheduler: publish to Instagram / YouTube at the chosen time
  -> Nightly: pull views / likes / comments back
```

## Review page and alerts

- **Email on every new reel**: subject "New reel ready: {title}", cover image, hook line, duration, platforms, button "Review reel" linking to the admin review page. Recipient configurable, default same address as the quality gate alerts. Also an email when a render fails, with the error.
- **Review page (Admin > Social > Reels > {draft})**:
  - Video player (9:16 preview), cover frame picker.
  - Scene list with text and voice line per scene; edit any scene and "Re-render".
  - Full voiceover transcript.
  - Instagram tab: caption, hashtags, cover, schedule time.
  - YouTube tab: title, description (with UTM article link), tags, privacy, schedule time.
  - Actions per platform: Approve + schedule / Publish now / Reject. Status timeline (drafted, rendered, approved, scheduled, posted, failed) with links to the live post.
  - Download MP4 button (manual fallback for any platform).
- **Reels list**: all drafts with status filters and "needs review" count, also shown on the admin dashboard.

## Publishing

- **Instagram**: Instagram Graph API, `media_type=REELS` from a public video URL on the site, then `media_publish`. Needs an Instagram Business or Creator account and a Meta app. Posting to your own account works with the app in development mode, no App Review needed.
- **YouTube Shorts**: YouTube Data API `videos.insert` (Google client already in `composer.json`), vertical and under 60 s so it lands in Shorts, `#Shorts` in title/description.
  - BLOCKER TO PLAN AROUND: uploads from an **unverified Google API project are forced to private** until the project passes Google's audit. Plan: apply for the audit at the start of Phase 2. Until approved, uploads go up private and you switch them to public in YouTube Studio (or upload by hand with the Download button).

## Security notes (public repo)

- Never commit the voice recording, tokens or draft data. The render job fetches them from the site with a token stored as an Actions secret.
- Actions logs on a public repo are public: never print tokens, URLs with tokens, or script content beyond IDs.
- Never upload renders or audio as workflow artifacts (anyone can download artifacts on a public repo). Results go straight back to the site.
- Render workflow only triggers on `repository_dispatch` / `workflow_dispatch`, never on pull requests from forks.
- Site upload endpoint: token-guarded, accepts only files for an existing draft in "rendering" state.

## Phase 0 as built (2026-10-21)

- Tables `social_drafts` + `social_draft_targets` (migration `2026_10_21_120000_create_social_drafts_tables`). Models `SocialDraft`, `SocialDraftTarget`; `BlogPost::socialDrafts()`.
- `AiContentService::generateReelScript()`: structured outputs (`output_config.format` json_schema) on `config('social.reels.ai_model')` (defaults to the original-article model, Sonnet 4.6). Logged to the AI budget as `social_reel_script`.
- `App\Services\Social\ReelDraftService`: create, regenerate, saveEdits (script change resets approvals and status), markRenderRequested, markRendered (emails `ReelReadyForReview`), markRenderFailed (emails `ReelRenderFailed`), approve/reject per target. House style enforced (no em/en dashes).
- `SocialUrl::for()` takes an optional format, added as `utm_content` (YouTube description link uses `utm_content=reel`).
- Admin: Reels nav item with needs-review badge; `/admin/social/reels` (start a reel from top articles, filters) and the review page (player, scene editor with live length/word count, Instagram/YouTube caption tabs, per-platform approve with schedule time, history). Approval is blocked until a render exists.
- Alerts go to `config('social.reels.alert_email')` (env `SOCIAL_ALERT_EMAIL`, default adilsher973@gmail.com).
- Tests: `tests/Feature/SocialReelTest` (7). Full suite: 139 passing, same 6 pre-existing ProfileTest/ExampleTest failures.

## Phase 1 as built (2026-10-21)

- **Studio settings** (Admin > Reels > Studio settings, table `social_studio_settings`, model `SocialStudioSettings`): voice recording upload (private `local` disk), GitHub fine-grained token (encrypted). Connecting verifies the token, generates a render token and saves it as the `SOCIAL_RENDER_TOKEN` Actions secret itself (sealed with the repo public key via `sodium_crypto_box_seal`), so the owner never copies secrets by hand. Token needs Actions + Secrets read/write on this repo only.
- **Render button** dispatches `.github/workflows/render-reel.yml` (`workflow_dispatch`, input `draft_id`) via `GithubRenderClient`; marks the draft `rendering` and resets approvals. Review page polls `reels/{draft}/status` every 20 s and reloads when done.
- **Render job** (GitHub Actions, public repo so free): `video/pipeline/fetch.py` pulls the script + images + voice sample from `/api/social/render/{draft}` (token-guarded, `VerifySocialRenderToken`); `voice.py` clones the voice with Chatterbox 0.1.7 on CPU (12 s of trimmed speech as reference, it reads 10 s), speaks each scene, retries implausibly long output, times words proportionally, normalises to -16 LUFS; Remotion 4.0.533 renders `Reel` (1080x1920, 30 fps, H.264 CRF 23) and the `Cover` still; the video goes back in 1.5 MB chunks (`/chunk`, offset-checked so retries are safe) because shared hosting caps uploads, then `/complete` stores it on the public disk, updates scene timings and sends the review email. `/failed` and the 45-minute `social:reels-timeout` (every 10 min) cover failures.
- Public repo hygiene: site URL pinned in the workflow (token only ever sent to adilsher.pro), no secrets or script printed, no workflow artifacts.
- Brand templates: `video/src` (Background, BrandChip, ProgressBar, word-highlight Captions, Headline with accent numbers, Portrait ring, framed 16:9 ImageCard over a blurred backdrop, CodeCard). Handle from `social.reels.handle` (adilsher.pro).
- Verified locally end to end with a stand-in voice (HF/PyTorch downloads are blocked in the build container): fetch, 31 s render in ~1.5 min (4.9 MB), chunked upload identical byte for byte, draft ready, email sent. First real render on GitHub also downloads the model (~5-8 min extra, then cached).
- Tests: `tests/Feature/SocialReelRenderTest` (14). Full suite 155 passing, same 6 pre-existing failures.

## Voice and script fixes after the first real render (2026-10-22)

Owner feedback: little variation in the voice, one word pronounced differently in two scenes, and the end card sent viewers to the original source (dev.to).
- Voice reference is now the liveliest 12 s of the recording (highest voiced pitch movement, 1 s steps), not a fixed slice. Recording tips ask for an energetic read.
- Studio settings > How your voice speaks: Calm / Natural (default) / Expressive presets (Chatterbox exaggeration 0.45/0.65/0.85, cfg 0.5/0.4/0.3, temperature 0.65/0.7/0.75; first render used 0.5/0.5/0.7) and a pronunciation list (`word = how to say it`, default includes `adilsher.pro = Adil Sher dot pro`). Pronunciations change only the audio; captions keep the real spelling.
- Each scene is spoken up to 3 times with fixed seeds (draft id based, so re-renders are repeatable); faster-whisper base.en listens back and the take with the best word match (numbers ignored) is kept, stopping at 0.9. Caption timing now comes from Whisper word timestamps mapped back to the original words.
- Prompt: credit a news source in passing at most, never send viewers to it; end card points to the full article on the owner's site (link in bio). `ReelDraftService::scriptProblems()` rejects a script that names the source next to a read/visit/check nudge and retries once with feedback (combined with the word-limit retry).
- Tests: 159 passing, same 6 pre-existing failures.

## Second voice pass (2026-10-22)

Owner tried Expressive (exaggeration 0.85, cfg 0.3): excited non-word sounds, rushed delivery, jumpy captions.
- Presets rebalanced: Calm 0.4/0.5/0.6, Natural 0.55/0.45/0.65 (default), Expressive 0.7/0.35/0.65 (exaggeration/cfg/temperature). Chatterbox rushes and adds sounds above ~0.7.
- Take score = word match minus penalties for loud audio no word explains (>0.25 s), Whisper non-speech tags ("[laughs]"), and pace above 2.95 words/s. Stops at 0.88.
- Kept take is trimmed to its first/last word, and if still rushed slowed towards 2.7 words/s with ffmpeg atempo (never below 85% speed).
- Captions only use Whisper timing when the take matched >= 0.75 and times move forward; otherwise even spacing.

## Third voice pass (2026-10-22), from the owner's render logs

Feedback: more accent, last words of sentences cut, a "mic on/off" sound between sentences, "dev.to" said as "deev.to".
- Cut endings: takes were trimmed at Whisper's last-word end + 0.15 s, and Whisper marks endings early. Trimming now follows the audio's own sound segments (keeps the tail, still drops separate bursts after the line), and the initial silence trim is gentler (top_db 50).
- "Mic on/off": hard cuts into digital silence. Each line now gets a 15 ms fade in and 80 ms fade out, and a ~-62 dBFS room-tone bed runs under the whole voiceover.
- Accent: (1) pace threshold was 2.95 w/s on a pause-free measure, so nearly every take was slowed up to 15%; now 3.9 w/s, target 3.5, max 8% slowdown. (2) the "liveliest" window picked the most animated (least typical) stretch; now the 70th-percentile window, followed by up to 48 s more of the recording so Chatterbox's speaker embedding (averaged over the whole reference) anchors timbre and accent. (3) Natural preset back to the first render's 0.5/0.5/0.7; Calm 0.4/0.55/0.65; Expressive 0.6/0.42/0.7.
- Domains (`word.to/io/com/dev/pro/ai/...`) are spoken as "word dot tld" automatically; the owner's pronunciation list still wins.
- `TQDM_DISABLE=1` in the render workflow hides the per-token progress bars; per-take summary lines remain.

## Reset after four voice rounds (2026-10-22): one problem per step

Rounds 1-3 changed several things at once without hearing the output, so fixes and regressions mixed. From here, each step has one goal and a "done when" the owner confirms on a real render.

**Step 1 DONE: evidence.** The render now sends `report.json` (per scene: script, spoken text after pronunciations, what Whisper heard, each word flagged ok or not, match, takes tried, take kept, slowdown; plus voice settings and reference window). Stored in `social_drafts.render_report`, shown as **Voice check** on the review page. Done when: the owner can see which words were misheard without guessing.

**Step 2 DONE: captions.** Root cause found: in a gap between two words `Captions.tsx` fell back to the scene's last word, so the caption jumped to the final line and back (replayed: 9 backward jumps in one 12-word sentence; first render had no gaps, so no jumps). `captionTiming.ts` now keeps the last spoken word current. Done when: captions only ever move forward.

**Step 3 built (2026-10-22): pronunciation per reel.** Evidence from the owner's Voice check: an "aa/ur" filler before "As dev.to reported", "cache" said as "say", "GPT" heard as "DPT", a stray "Dental?" after a line.
- Each reel now carries its own pronunciation list. The AI writes it with the script (acronyms as spaced letters, versions, domains, cache = cash, SQL = sequel) and it is editable on the review page under "This reel's pronunciations". Clicking a flagged word in the Voice check adds it there. The render merges it over the global list in Studio settings, the reel's entry winning.
- The script prompt now writes for the ear: no weak openers ("As", "So", "Well", "According to"), the source credited inside a sentence, varied sentence length, the key word last.
- Takes are judged on exact words: one wrong, missing or extra word (fillers included) costs 0.1, a take must score 0.93 to stop early, up to 4 takes. Spelled acronyms ("G P T") match Whisper's "GPT" or "G.P.T.".
- Fillers before a line and words tacked on after it are cut from the kept take.
- Done when: a re-render shows no flagged words in the Voice check.

**Step 4 built (2026-10-22): voice energy.** Natural body lifted 0.5 to 0.55 exaggeration. Hook and end lines get their own lift (Natural 0.7 / cfg 0.45) so they sound like an intro and an outro. Calm and Expressive moved the same way. The optional second "energetic" reference recording is held back: only if the lift is not enough. Done when: the owner signs off on the hook and outro energy.

## Roadmap: where the reel needs to go (owner's direction, 2026-10-22)

Today: one featured image, text scenes, cloned voice, captions. The owner wants motion visuals on every scene and themselves on screen, lip-synced, in different avatars, poses and outfits that match the brand. Gap analysis, in build order:

| Stage | What | Main question / dependency |
|---|---|---|
| A. Voice + captions solid | Steps 1-4 above | In progress |
| B. Scene visual plan | The AI script picks a visual per scene from a menu: b-roll clip, animated diagram, code animation, product screen recording, stat counter, avatar shot | Prompt + schema change, editable in the review page |
| C. Motion b-roll | Stock video per scene (Pexels API, key already configured for blog images), cropped to 9:16, Ken Burns fallback | Licence terms are fine for social; relevance of search results needs a check step |
| D. Animated graphics | Remotion components: kinetic text, bar/line charts from numbers in the script, code typing, before/after splits, logo reveals | Pure code, no cost; biggest visual upgrade per hour spent |
| E. Avatar library | A consistent "you" in several outfits/poses in brand colours (midnight, ocean, teal), generated from a set of your photos, approved once and reused | Needs an image model that keeps identity (paid service, or a trained likeness model); you approve every look |
| F. Lip-synced talking avatar | Your avatar speaking the cloned voiceover for hook/end (and optional mid-scenes) | Not feasible on GitHub's CPU runners in reasonable time; needs a GPU or a paid lip-sync API. Cost per reel to be decided |
| G. Edit polish | Transitions, sound design (whooshes, subtle music bed under the voice), zooms on key words, safe-zone layout per platform | Remotion, no cost |

Decisions needed before E/F: monthly budget for avatar images and lip-sync, and whether a third-party service may hold your likeness.

## Data model (sketch)

- `social_drafts`: blog_post_id, kind (reel | carousel | post), status, script (json scenes), captions (json per platform), media paths (mp4, cover, slides), render run id, error, timestamps.
- `social_draft_targets`: draft_id, social_account_id, approved_at, scheduled_for, status, external_id, external_url, error. One row per platform.
- `social_posts` (existing): add draft link, format, engagement columns, last_synced_at.
- `social_voice`: stored as settings (engine, voice id or sample path).

## Phases

| Phase | Delivers | Status |
|---|---|---|
| 0. Groundwork | Drafts + targets tables, Social Studio list, review page shell, email alert, AI script + caption generator | DONE |
| 1. Render pipeline | `video/` Remotion project with brand kit, GitHub Actions render workflow, voice clone engine, upload back, review page video player, re-render | BUILT |
| 2. Publish | Instagram Reels driver, YouTube Shorts driver, scheduling, apply for YouTube audit | TODO |
| 3. Carousels + LinkedIn | Carousel templates (PNG + PDF), Instagram carousel, LinkedIn Posts API (personal), first comment, LinkedIn video | TODO |
| 4. Picking + measuring | Social score, weekly shortlist, engagement sync, report | TODO |
| 5. More reach | LinkedIn company page (after Community Management API approval), Facebook Reels, TikTok | TODO |

## What the user needs to provide

- [x] Voice engine choice: B, Chatterbox.
- [ ] One 1-2 minute voice recording, uploaded in Admin > Reels > Studio settings.
- [ ] Instagram Business/Creator account linked to a Meta app (I will give exact steps when Phase 2 starts).
- [ ] YouTube channel + Google Cloud project OAuth (steps at Phase 2).
- [ ] A GitHub fine-grained token (Actions + Secrets read/write on this repo), pasted in Studio settings.

## Sources

- Buffer, best content format 2026: https://buffer.com/resources/data-best-content-format-social-media/
- Meta, Instagram content publishing: https://developers.facebook.com/documentation/instagram-platform/content-publishing
- Google, videos.insert (private-until-audit note): https://developers.google.com/youtube/v3/docs/videos/insert
- Remotion pricing: https://www.remotion.dev/docs/pricing
- ElevenLabs pricing overview 2026: https://bigvu.tv/blog/elevenlabs-pricing-2026-plans-credits-commercial-rights-api-costs/
- Chatterbox (MIT): https://huggingface.co/ResembleAI/chatterbox
- X API pay-per-use: https://opentweet.io/how-to/x-api-pay-per-use-explained
