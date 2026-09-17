# Entity disambiguation: "Adil Sher" (developer) vs "Adil Sher" (filmmaker)

Status: code side shipped 2026-09-16. Off-site side is ongoing and is the slow part.

## The problem

Google AI Overviews correctly cite adilsher.pro when describing Adil Sher the
developer, but the name in the answer links to a Knowledge Panel for a
Lahore-based filmmaker and photographer of the same name.

Nothing in that panel is wrong. It belongs to the filmmaker and is accurate for
him. What is broken is entity resolution: Google's Knowledge Graph contains
exactly one "Adil Sher" node (the filmmaker, who has IMDb credits, years of
press, and owns adilsher.com). This site never declared an entity of its own,
so every mention of the name here was just a name string, and the resolver
bound it to the only candidate it had.

The fix is not to correct that panel (it is not ours, and its facts are
correct). It is to make Google create a second, separate entity.

## What is in code now

Single source of truth: `config/person.php`. Change identity facts there, not
in the blade components.

- `config/person.php` holds name, job title, entity id fragment,
  `disambiguating_description`, locality, `same_as` profiles, `knows_about`,
  and `organization_name`.
- `components/schema/person.blade.php` emits a Person with a stable `@id`
  (`APP_URL#person`), `disambiguatingDescription`, `homeLocation`,
  `mainEntityOfPage` pointing at /about, `sameAs`, and `knowsAbout`.
  `disambiguatingDescription` is the property that does the real work: it
  exists specifically to separate same-named entities, and naming the other one
  is legitimate use.
- Person is emitted **site-wide** from `layouts/app.blade.php`, not just on
  /about, so every page declares the entity. The duplicate push on the about
  page was removed.
- `components/schema/organization.blade.php` has `@id` `APP_URL#organization`
  and links to the Person through `founder` and `employee`. Its name comes from
  `person.organization_name` ("Adil Sher"), not `APP_NAME`, which on production
  is the handle "adilsherdotpro" and is useless as an entity name.
- `components/schema/website.blade.php` has `@id` `APP_URL#website` and points
  at the Organization (`publisher`) and the Person (`about`).
- `components/schema/article.blade.php` author and publisher now carry the
  Person and Organization `@id` instead of a bare name string.
- `components/schema/creative-work.blade.php` (new) is pushed on
  `frontend/project-detail.blade.php`. These project pages are what AI
  Overviews actually cite, and they previously had no schema at all, so the
  cited page named no author. Now `author`/`creator` point at the Person `@id`.
- `<link rel="me">` for every `same_as` profile is emitted in the layout head.
- On-page text now leads with the disambiguator: the home `<title>` and the
  hero eyebrow carry "Adil Sher", "Full Stack Developer" and "Islamabad,
  Pakistan"; the about `<title>`, `<h1>` and meta description do the same; the
  hero portrait `alt` names the person instead of saying "Development".
- `LlmsTxtController` emits an "Author Identity" block in both `/llms.txt` and
  `/llms-full.txt`, including the canonical entity id and the disambiguation
  sentence in plain words. AI answer engines read these as text, so the
  statement has to exist outside JSON-LD too.

Also fixed while in here: `Storage::url()` can return a relative path on
production, which made the Article and project `image` values unusable. Both
are now forced absolute off `APP_URL`, matching the og:image handling in the
layout.

### Do not change

The `@id` values are identifiers, not URLs to fetch. Once crawled, changing
them restarts the entity from zero. `APP_URL` on production is
`https://www.adilsher.pro` (www is canonical, non-www 301s to it), so the
Person id is `https://www.adilsher.pro#person`.

## What is NOT in code, and matters more

Structured data only declares the claim. Corroboration is what makes Google
accept it.

1. **Reciprocal links.** `sameAs` is weak in one direction. Every profile in
   `config('person.same_as')` must link back to adilsher.pro: the LinkedIn
   website field, the GitHub profile URL field, the X bio link. That round trip
   is what lets Google merge the cluster. This is the single cheapest
   outstanding item.
2. **Identical descriptor everywhere.** Same photo and same one-liner
   ("Full Stack Developer, Islamabad, Pakistan") on every profile. Inconsistency
   is what keeps a cluster below the confidence threshold.
3. **Independent third-party mentions.** Something that is not our own site
   naming him as the developer: a client press release, a conference talk, a
   technical interview, coverage of a shipped product. Self-published claims do
   not build entity confidence on their own. This is the slow, unavoidable part.
4. **Feedback on the AI Overview.** Thumbs down, then the overflow menu ->
   feedback, stating plainly that the name links to a different individual, with
   the adilsher.pro URL. Same via the panel's own Feedback link. Low odds alone,
   but human review does happen for identity mix-ups.

### Explicitly do not do

- Do not claim or suggest edits on the filmmaker's Knowledge Panel. It is not
  ours, verification will fail, and its facts are correct.
- Do not create a Wikidata item as a shortcut. Wikidata requires the subject to
  be describable from serious independent references; items about working
  professionals without independent coverage get deleted, and a deleted item
  leaves a worse trail than none. Revisit only after item 3 above exists.

## Verifying

```
php artisan view:clear
php artisan serve --port=8777
```

Then confirm each page emits valid JSON-LD with the expected `@id` values, and
run the live URLs through the Rich Results Test and the Schema Markup Validator
after deploy.

## Expectations

Recrawl and reconciliation run on the order of one to three months after the
signals are consistent. A Knowledge Panel of his own may never appear; that
requires notability. The achievable win is narrower and still worth it: the
entity linker stops resolving the name to the filmmaker, and LLM answer engines
stop conflating the two.
