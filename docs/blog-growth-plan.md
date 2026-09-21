# Blog Growth & Ad-Readiness Plan

Running doc. Updated as we work. Status legend: DONE / DOING / TODO / BLOCKED / DECIDED.


Rule: all data in this doc comes from PROD. The local DB is a stale June snapshot and is never used as evidence.
Last updated: 2026-09-19

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

## FIX BUILT 2026-09-21: the pipeline stall (not deployed)
Root cause: `articles:process` inferred "not yet processed" from "has no category", so uncategorisable rows never left the queue; with no ORDER BY every hourly run re-took the same 200 and never reached the ~14,370 behind them. Invisible because re-scoring an unchanged row is an Eloquent no-op (not even `updated_at` moved).
- **Migration** `2026_09_21_120000_add_processed_at_to_collected_articles_table`: adds `processed_at` + index `(status, processed_at, id)`.
- **`ProcessArticles`**: selects `pending AND processed_at IS NULL` (was: null category OR score 0), adds `orderBy('id')`, and `forceFill(['processed_at' => now()])->save()` on EVERY article BEFORE the work, so a row that cannot be categorised, or one that throws, still leaves the queue. `--force` re-runs processed rows.
- **`CollectedArticle`**: `processed_at` in fillable + datetime cast.
- **`tests/Feature/ProcessArticlesQueueTest`**: 5 tests pinning the exact stall (uncategorisable row still marked; queue advances past unusable rows; processed rows not re-taken; `--force` re-runs; a throwing row cannot block). Suite 116 passing, same 6 pre-existing ProfileTest/ExampleTest failures.
- AFTER DEPLOY: ~27,700 pending rows are all `processed_at IS NULL`, so at 200/hour the backlog takes ~6 days to drain. Consider one catch-up run with a big `--limit`.

## BUILT 2026-09-21 (rewrite quality gate, not deployed)
- `App\Services\RewriteQualityService`: the rules-based output gate. `evaluate()` scores a stored post; `evaluateRawResponse()` adds the missing-H1 check that only works pre-parse. Hard fails: title >= 70% similar to source, body 5-gram overlap >= 50%, under 500 words, no attribution, refusal/placeholder text, non-ASCII > 20%. Soft flags (-10 each, pass mark 70): < 2 headings, no first person, overlap 25-50%, word count 500-600 or > 1200, no excerpt, headline < 40 or > 100 chars.
- `php artisan blog:score-rewrites` (READ ONLY dry run; `--limit`, `--failing-only`, `--show`, `--ids`). Reports blocked count, rules tallied, score distribution, worst offenders.
- `tests/Unit/RewriteQualityServiceTest`: 10 tests, all passing. Suite 99 -> 109 passing; the same 6 pre-existing ProfileTest/ExampleTest failures remain.
- **IMPORTANT FINDING while building:** the earlier "45/45 passthrough posts have no `# ` heading" check was NOT evidence. `parseTransformationResponse()` strips the `# ` line from the body, so NO stored post has one, passthrough or not. The check could not discriminate. The parser-bug diagnosis still stands on the title evidence (45 posts byte-identical to their source headline), but the H1 rule can only be enforced live on the raw model output.
- **PROD CALIBRATION RUN 2026-09-21 (454 posts):** blocked 225 (49.6%), all on hard failures. Rules that matched independent evidence and are CORRECTLY calibrated: `title_matches_source` 50 (~45 known passthroughs + near-misses), `body_copies_source` 34 (33 known), `too_short` 12, `not_english` 3, `missing_attribution` 1. Soft flags barely fire (396 of 454 scored 100; 0 blocked on score alone).
- **BUG FOUND BY THE RUN:** `placeholder_or_refusal` hit 173 posts because the pattern flagged any line starting with ``` . The prompt ASKS for code snippets, so fenced blocks are correct output. FIXED: only a fence wrapping the whole response, or an explicit ```markdown wrapper, counts. Two regression tests added (12 total, all passing). Expected real block rate after the fix: roughly 60-70 posts (~14%), which is the known-bad set.
- **LIMITATION found:** `not_english` uses a non-ASCII ratio, so it catches Thai/Chinese/Korean/Russian but NOT Latin-script languages. Post 331 is Portuguese and passed that rule. A stopword-based check would be needed; deferred.
- **CONFIRMED on PROD 2026-09-21 after the fix: 59 blocked of 454 (13%)**, matching the predicted 60-70. `placeholder_or_refusal` 173 -> 7. Rule tallies sum to 107 across 59 posts, so the bad ones fail several rules at once, which is the expected shape. **The gate is calibrated and ready to wire in.**
- Soft flags remain nearly inert (396/454 score 100, nothing blocked on score alone), so the pass mark of 70 is currently decorative. Revisit once the gate is live.
- The 59 are concentrated in ids 317-358 (the Aug 18-19 parser-bug window) plus a few older ones. Worst: id 317 at 3,214 words and 86% source overlap under the source's own headline.

## DOING

- [ ] **Analysis continues.** User asked to HOLD all code fixes until we have more data and shared understanding.
- [ ] **Publishing stopped: last post 2026-09-10.** Reviewed 2026-09-19: nothing in this work stopped it. No publish-path code changed after 2026-09-07 (commits since: `b11253b` = breaking-news detector + reclassify schedule, `10b0d6d` = SEO/schema only). Auto-publish still falls back to basic content if AI fails, so an AI error can't silently block a post. Remaining suspects: no article clears the funnel (score >= 75 AND fetched within 30d AND not parked AND auto_publish source), the cron stopped, or someone turned auto-publish off in admin. Diagnostic commands issued, awaiting prod output.
  - **2026-09-19 FOUND THE MECHANISM:** fetching is current (newest article 2026-09-19), auto-publish enabled, min 75. There ARE 32 approved, unparked, fresh articles scoring >= 75 from auto-publish sources, but **all 32 have `assigned_category_id = NULL`**. The publisher loops `Category::active()->forBlog()` and selects by category, so uncategorized articles are invisible to it. `posts:auto-publish --dry-run` -> "No eligible articles". Funnel: 296 approved+unparked articles HAVE a category, but 0 of them were fetched in the last 30 days.
  - Contributing design flaw: `ProcessArticles` approves on score alone and never requires a category, so articles get approved and then stranded without any warning.
  - Nothing ever clears a category once set (`reassignCategory` is never called), so these were never categorized.
  - **2026-09-19 ROOT CAUSE (revised after weekly supply data):**
    - ~~Aug 4 tightening choked supply~~ **WRONG.** After Aug 4 the real scorer kept producing 33-44 articles >= 75 per week (Aug 3 wk: 44, Aug 10 wk: 33). Supply did not thin; categorization STOPPED. Week of Aug 17 is partial (946) and nothing after; newest categorized >= 75 article is 2026-08-19 00:00.
    - **Leading theory: queue clog in `ProcessArticles`.** "Not yet processed" is detected as `assigned_category_id IS NULL` (or score 0, but the fetch step already gives every article a nonzero score). An article the keyword detector cannot categorize is scored, stays `pending` with NULL category, and so looks unprocessed forever. The query has NO `ORDER BY` and `--limit=200`, so every hourly run takes the same oldest 200. Once >= 200 uncategorizable articles pile up at the front, every run re-processes the same dead 200, and no new article is ever reached. Silent: nothing throws, nothing logs. Confirmation command issued (predicts ~200 of the picked articles touched in the last 2h, and all picked articles fetched before Aug 19).
    - **Why an article goes uncategorized (from code, 2026-09-20):** a source with `target_category_id` ALWAYS categorizes, so only sources without one can fail. Then keyword detection reads ONLY title + RSS description, needs a score >= 30, i.e. 2 keyword hits in one category (DB lists: hits / 5 x 100, so 1 hit = 20), and matches English substrings only. Fails on non-English posts, essay-style titles, and empty/short RSS descriptions, which is exactly Dev.to.
    - **ROOT CAUSE CONFIRMED 2026-09-21: the clog, invisible because the run writes nothing.** A full 200-row batch run by hand COMPLETED and changed `updated_at` on zero rows. `ProcessArticles` updates `relevance_score`, and Eloquent skips a no-op write, so a run on rows whose score recomputes identical (and which cannot be categorised, and sit between the reject/approve thresholds) leaves no trace at all. Writes stopped ~Sept 7 because the score's recency component bottoms out at ~2 weeks, so the head rows' scores stopped changing. The job never stopped running. Disproven: memory, speed, stale mutex, cron, PHP binary. Fix: ORDER BY + a real processed marker so uncategorisable rows leave the queue.
    - ~~2026-09-21 timeline corrected + crash theory disproven~~ (superseded): Prod: job IS scheduled (hourly, next due normally), overlap lock NOT held, CLI memory_limit 1536M, and NO memory/fatal/TypeError anywhere in Aug-Sep logs. So it does not crash. The 200 head-of-queue rows were last touched **2026-09-07 01:01:46**, so the job ran and re-chewed them right up to then. Revised sequence: **clog from ~Aug 19** (job busy on the uncategorizable head, new articles never reached) then **job stopped entirely ~Sept 7 01:01** (before that day's commit, which landed 16:11, so not the commit). "405 rows updated in 24h" is just fetch creating rows, not processing. Next: separate created-vs-processed counts, then run `articles:process --limit=5` by hand.
    - **SEPARATE LIVE BUG FOUND IN LOGS:** every fetch cycle throws `SQLSTATE[22001] Data too long for column 'description'` for **Kubernetes Blog** (source 26) and sometimes **Dev.to** (source 11), so those feeds collect NOTHING. `collected_articles.description` is TEXT (64KB) and these feeds ship full HTML bodies. Fix: widen to MEDIUMTEXT/LONGTEXT or truncate on insert in `RssFeedService::parseRssXml()`. Also seen: Hacker News RSS times out (10s), and the Cricbuzz scraper API at localhost:5000 is down (unrelated to the blog).
    - **2026-09-20 PROD RESULT:** the 200 picked rows are all genuinely uncategorizable (classes C/D/E/F, no A/B) but **none were touched in the last 2h**, so `articles:process` is not running them. The clog is real but NOT the stopper. New leading suspect: the command dies with a fatal (memory: it loads 200 rows whose `content_data` holds raw source XML) or a per-article `Error` that `catch(Exception)` misses. Also confirmed: the Aug 19 prod kill switch `SIGNIFICANCE_DETECTION_ENABLED=false` is STILL active, so breaking-news has been off since Aug 19 (separate, user decision pending).
    - ~~Queue-clog theory unconfirmed~~ superseded by the line above. Original note: Two possibilities: (a) the hourly job runs but keeps re-processing the same 200 uncategorizable rows, or (b) the job isn't running/finishing and the rows would categorize fine. Prod diagnostic issued (reason classes A-F + re-processed-in-last-2h count); awaiting output.
    - The Aug 18 commit `150d169` (breaking-news path in `ProcessArticles`) has no fatal error, since each article is in a try/catch. The date lines up but it isn't shown to be the cause.
    - Aug 4 changes (`min_score` 50 -> 75, `for_blog=false` on programming + career-growth, parking) are real but secondary. Prod: 15 articles >= 75 in `programming` + 2 in `career-growth` are invisible to the publisher.
    - **Separate pre-existing bug (June):** `RssFeedService::parseRssXml()` runs a crude scorer at FETCH time and sets `status = 'approved'` when it scores >= 70, so those articles skip real scoring and categorization. Prod: 88 (Jun), 102 (Jul), 85 (Aug), 48 (Sep) such articles; 0 ever published. These are the 32 stranded Dev.to articles. Scores step in 3.53s (60/17 keywords). Their 75+ is from the crude scorer, NOT the real one.
  - Nothing in this session's work caused the stop. The cause is the Aug 4 settings change working as designed, plus a thin-supply situation it created.

## HELD (agreed, do not action yet)

- [ ] Fix the title-passthrough parser bug + prompt contradiction. 45 posts affected.
- [ ] Regenerate the 45 affected posts with `RegeneratePostContent`.
- [ ] Self-referral scraper blind spot in `flagBehaviouralScrapers`.

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
- [x] ~~Empty categories linked sitewide~~ **CORRECTED 2026-09-19:** the 7 empty categories are all `for_blog = 0` (portfolio categories). The blog sidebar uses `forBlog()`, so they never appear on blog pages, and the sitemap excludes them too. Not a blog issue. The earlier count query just didn't filter by `for_blog`.

## PIPELINE REDESIGN (user's spec, 2026-09-21) - agreed direction, NOT started

Target flow: `fetch -> pre-filter sources -> AI rewrite -> categorize (never stuck) -> score the OUTPUT -> publish if it passes`. Fewer, better articles.

1. **Source pre-filter** (before any AI spend): drop non-English, no/short description, and clear junk. Cheapest gate, runs first.
2. **AI rewrite** as today.
3. **Categorization fallback chain, so nothing ends up stuck:** (a) RSS source `target_category_id`; (b) DB keywords at a lower threshold; (c) built-in keyword lists; (d) same match run over the article BODY, not just the RSS description; (e) cheap AI classification. Final rule: every article ends **categorized OR explicitly rejected**, never pending-with-no-category. Needs a real processed marker (column or status) so "no category" stops meaning "unprocessed" - that is what creates the clog.
4. **Output-side quality gate (NEW, the key change):** score the REWRITTEN article, not the source headline. Today nothing judges the generated text. Articles failing the gate get parked, not deleted, so the AI spend is reusable.
5. **Publish only what passes.**
6. **Randomised publish times:** 7-day pattern (2 days 09:00, 2 days 15:00, 2 days 21:00, 1 day 01:00). Implement by running the publisher every 15 min and having the command decide if the current slot is today's, derived deterministically from the date. Quarterly drift: +15 min for 3 months, then -15 min back, stored as one offset setting. NOTE: cosmetic/anti-pattern only, no SEO benefit.

**DECIDED 2026-09-21: the output gate is RULES-BASED, no AI call.** Free, deterministic, testable, and it catches the failures actually observed (45 title passthroughs, 33 posts with >50% source overlap). An AI judge stays a later option if rules prove too blunt.

Open decisions: exact thresholds (proposed below); whether non-English is rejected at fetch time or at pre-filter.

## TODO (ordered)

1. [ ] **Find why `articles:process` stopped running (~Aug 19).** Diagnostics issued 2026-09-20 (last-touched timestamps, stuck overlap mutex, broad log grep for memory/fatal/TypeError, crontab + schedule:list): AWAITING OUTPUT. Everything else in the redesign depends on this.
2. [ ] **Refresh GSC data.** July baseline is stale; corpus grew 327 -> 451 since. Decides whether the "derived content has a ranking ceiling" thesis holds.
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
Confirmed on PROD 2026-09-08: 45 exact passthroughs, 45/45 with no `# ` line in the body.
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
