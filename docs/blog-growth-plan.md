# Blog Growth & Ad-Readiness Plan

Running doc. Updated as we work. Status legend: DONE / DOING / TODO / BLOCKED / DECIDED.

Last updated: 2026-09-08 (step 3)

---

## Where we are

- 451 published posts, 320 indexable, 131 noindexed. All `source_type = curated`.
- Real human traffic after the analytics cleanup: **~100-150 visits / 30 days** (105 external referrals + 16 multi-page visitors). Previously mis-reported as ~3,200.
- GSC (July 2026 baseline, needs refresh): avg position 15.7, 701 impressions, **9 clicks / 90 days**.
- Verdict on ads: not yet. Not because AI is disqualifying, but because measured demand is near zero and the corpus is 100% derived.

---

## DONE

- [x] **Analytics honesty pass** (2026-09-07). Found second scraper wave; 433 UA-based + 2,905 distributed scraper + 433 ip_flood reflagged. Human/raw split now 25%.
- [x] **Scheduled `analytics:reclassify --apply` nightly at 04:30** before the 05:00 recount. Was never scheduled, so the recount had been re-deriving "human" counts from stale flags. Committed in `b11253b`.
- [x] **Researched Google's actual position on AI-repurposed content** (primary sources, not SEO blogs). Conclusion: AI is not itself penalised; the test is value added. Publisher Policies explicitly permit copied content WITH "additional commentary, curation, or otherwise adding value". Volume alone is not the stated test, but rate is evidence.
- [x] **Read the scoring + generation pipeline.** See "How the system actually works" below.
- [x] **Corrected two of my own earlier errors:** `image_alt` was never broken (model accessor falls back to title); the "1,277 plausibly real visitors" figure was inflated by self-referrals.

## DOING

- [ ] **Analysis continues.** User asked to HOLD all code fixes until we have more data and shared understanding.

## HELD (agreed, do not action yet)

- [ ] Fix the title-passthrough parser bug + prompt contradiction. 45 posts affected.
- [ ] Regenerate the 45 affected posts with `RegeneratePostContent`.
- [ ] Self-referral scraper blind spot in `flagBehaviouralScrapers`.
- [ ] Hide/noindex the 7 empty category pages.

## VERIFIED 2026-09-08

- [x] **Rewrite divergence: CLAIM VALIDATED.** 452 posts compared. Median title similarity to original **31%**; 334 (74%) clearly reframed under 40%. Median body 5-gram overlap **1.4%**. The rewrite genuinely produces different titles and different prose.
- [x] **BUT a defect cluster exists:** 50 posts (11%) at 80%+ title similarity, 33 posts with >50% body overlap. ROOT CAUSE FOUND, see below.
- [x] **Scoring gate is NOT selecting on quality.** Published avg score 59.2 vs approved-pool avg 58.8 (statistically identical). 307 of 452 published sit in the 50-59 band; 5 published below 50. Max published score 84.3, and the approved pool reaches 87.4, so nothing published ever cleared the config default of 85.
- [x] **Category reality:** 7 active categories all-time, fairly evenly spread (Web Dev 96, AI/ML 91, Design 58, DevOps 58, Tech News 54, Programming 52, Career 43). Last 30d is concentrated: Web Dev 31, AI/ML 16, Programming 8, Tech News 2, rest 0. So the user's "2 categories now" is right about recent behaviour; the CORPUS is still spread over 7.
- [x] **7 empty categories exist** (Business, Ecommerce, Entertainment, OTT, Portfolio, SaaS, Technology) with 0 posts each. Possible empty indexable pages, needs checking.
- [x] Backlog scale: 27,723 pending, 13,127 approved, 12,710 parked, 452 published.

## VERIFIED 2026-09-08 (step 3)

- [x] **Parser bug CONFIRMED as the sole cause.** 45 exact title passthroughs, and 45 of 45 have no `# ` heading in the body. Perfect correlation. Affected ids are near-contiguous (3, 4, 241, 292-295, 317-358) = a sustained window where model output format drifted.
- [x] **The real publish gate:** `auto_publish_settings.min_score_for_auto_publish = 75`, max 3/day, times 09:00/13:00/17:00, AI enhancement on. Config file says 85 and is ignored.
- [x] **CONTRADICTION UNRESOLVED:** the gate is 75, yet 307 of 452 published posts scored 50-59 and 5 scored under 50. Either they predate the raise to 75, or they published through a path that bypasses the gate. Needs a publish-date-vs-score check.
- [x] **`require_review_below_score = 25`** (config default is 75). Effectively nothing is ever held for human review.
- [x] **Canonical is CLEAN.** `original_url` never reaches rel=canonical/og:url; canonical is computed from APP_URL + path in `layouts/app.blade.php`. Only other use is `llms-full-txt.blade.php`. So attribution was never telling Google the source outranks us.
- [x] **Empty categories: not in the sitemap** (`SitemapController` filters on `whereHas('blogPosts', published)`), BUT all 7 are `is_active = true` and the blog sidebar renders every active category with no zero-count filter, so they are linked sitewide from every blog page and therefore crawlable.

## TODO (ordered)

1. [ ] **Refresh GSC data.** July baseline is stale; corpus grew 327 -> 451 since. Decides whether the "derived content has a ranking ceiling" thesis holds.
2. [ ] **Add an output-side quality gate.** Current scoring judges the SOURCE item before generation; nothing scores the generated article. This is the single biggest gap for an "editorial oversight" argument.
3. [ ] **Fix the self-referral scraper blind spot** in `flagBehaviouralScrapers` (self-referral + single page view is impossible for a real browser; currently evades the `noRefRatio >= 0.9` test).
4. [ ] **Decide the category strategy**: concentrate on one category to build topical authority, vs continue broad.
5. [ ] **Start the Proof of Work / knowledge-base series** (original content from work items). Planned 1/week.
6. [ ] **Activate distribution.** Social publisher is built and idle: 12 Facebook + 2 LinkedIn visits in 30d.
7. [ ] **Verify internal linking** actually exists, is crawlable, and moves anything.
8. [ ] Noindex/delete the 4 thin non-English posts (ids 141, 241, 294, 353).

## DECIDED

- Keep AI in the workflow. The constraint (one person, no time to hand-write, low budget) is real and AI is the correct answer to it. The question is what the AI is pointed at, not whether it is used.

## OPEN QUESTIONS

- Is 1 original article/week enough to shift the picture? (Probably not alone, but it compounds and it is the only content with no ranking ceiling.)
- Do the scraped articles count as a legitimate "knowledge base" for ads purposes? Depends on whether output demonstrably adds value; verification in progress.

---

## How the system actually works (verified by reading the code, 2026-09-08)

### 1. Selection scoring: `App\Services\ArticleScoringService`
Scores each **collected RSS item** 0-100 from its `title + description` only:
- Keyword relevance to a category (40%)
- Source authority = `rss_sources.priority * 10` (20%)
- Recency, exponential decay (15%)
- Engagement words: "how to", "guide", "why", "top 10" (15%)
- "Quality" = description length + title length + has-author (10%)

**This is a relevance/newsworthiness triage of INPUTS, not a quality check on OUTPUT.** Nothing in it reads the generated article.

### 2. Significance detection: `App\Services\SignificanceDetector`
Separate breaking-news fast path. Requires a trigger term and a notable entity to co-occur in the same PROSE sentence. Hardened after 10/10 false positives from page boilerplate. Not a quality gate either.

### 3. Publish gates: DB ROW, not config
IMPORTANT: `config/blog_automation.php` ('publishing' key) is effectively a decoy. `AutoPublishService` reads
`AutoPublishSetting::getInstance()` and gates on `min_score_for_auto_publish` from the
`auto_publish_settings` TABLE. Editing the config file or .env does NOT change the live gate.
- Config defaults (unused by the service): min_score 85, require_review_below 75, max_per_day 3, per_category 1
- Duplicate detection at 0.85 title similarity

### 5. THE TITLE-PASSTHROUGH BUG (found 2026-09-08)
`AiContentService::parseTransformationResponse()`:
```php
$title = $article->title;                        // defaults to the ORIGINAL title
if (preg_match('/^#\s+(.+)/m', $content, $m)) { // only a "# " H1 line matches
    $title = trim($m[1]);
}
```
If the model returns its headline as `## Heading`, bold text, or plain prose rather than `# Heading`,
the regex misses and the post **silently keeps the source article's exact title**. The prompt asks for
"a creative, opinionated headline" but never states it must be an H1, while a later rule says
"Use ## headings for sections, no H1" -- actively steering the model away from the one format the parser accepts.
Confirmed locally: 2/2 passthrough posts had no `# ` line in the body.
FIX: make the prompt demand `# Headline` explicitly, and have the parser fall back to
a first `## ` line or first bold line before ever reusing the original title; if nothing parses, fail the
article rather than publishing it under the source's headline.

### 4. Generation: `AiContentService::buildTransformationPrompt()`
This is where the actual editorial standard lives, and it is a strong spec:
- First-person voice as Adil Sher, a working developer
- **"A creative, opinionated headline that is NOT the original article title"**
- Personal anecdote hook, own analysis section ("My Take"), practical code snippet
- 600-900 words
- **"Do not reproduce large sections of the original verbatim"**
- Mandatory attribution line linking the original

**Design intent matches what the policy asks for.** The gap is that nothing verifies the output met the spec.
