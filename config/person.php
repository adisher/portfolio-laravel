<?php

/*
|--------------------------------------------------------------------------
| Person Entity (identity / disambiguation)
|--------------------------------------------------------------------------
|
| Single source of truth for the "Adil Sher" Person entity emitted as JSON-LD.
|
| Why this exists: there is another, far more established Adil Sher (a
| Lahore-based filmmaker) who already owns the only "Adil Sher" node in
| Google's Knowledge Graph. Because this site never declared a stable entity
| of its own, entity resolvers attach mentions of this site's author to that
| node instead. Everything below feeds the signals a resolver actually reads:
| a stable @id, sameAs profiles that link back here, and an explicit
| disambiguatingDescription that states which Adil Sher this is not.
|
| The @id is an identifier, not a URL to fetch. Never change it once it has
| been crawled, otherwise the entity starts over from zero.
|
*/

return [

    'name'      => 'Adil Sher',
    'job_title' => 'Full Stack Developer',

    // Entity name for the site/brand. Deliberately NOT config('app.name'):
    // APP_NAME on production is the handle "adilsherdotpro", which is a
    // username, not something a knowledge graph can resolve to a brand.
    'organization_name' => 'Adil Sher',

    // Fragment appended to the site root to form the stable entity @id.
    'id_fragment' => '#person',

    // schema.org disambiguatingDescription. This property exists specifically
    // to separate same-named entities, and naming the other one is legitimate
    // use. Keep the wording identical to the on-page bio and social profiles.
    'disambiguating_description' => 'Full-stack software developer based in Islamabad, Pakistan, building Laravel and Python web platforms. Not the Lahore-based filmmaker and photographer of the same name.',

    'description' => 'Full Stack Developer based in Islamabad, Pakistan, specializing in Laravel, PHP, Python and modern JavaScript applications.',

    'locality' => 'Islamabad',
    'region'   => 'Islamabad Capital Territory',
    'country'  => 'PK',

    // Profile URLs. Every one of these MUST link back to this site (LinkedIn
    // website field, GitHub profile URL, X bio) or the reciprocity that lets
    // Google merge the cluster is missing and sameAs carries little weight.
    'same_as' => [
        'https://x.com/adilsherdotpro',
        'https://www.linkedin.com/in/adilsher/',
        'https://github.com/adilsher',
    ],

    'knows_about' => [
        'Laravel',
        'PHP',
        'Python',
        'JavaScript',
        'Vue.js',
        'React',
        'API Development',
        'Web Development',
        'AI Integration',
        'Full Stack Development',
    ],

    // Path to the portrait used across the site and social profiles. The same
    // image on every profile is itself a clustering signal.
    'image' => 'storage/media/home-header-2.webp',

];
