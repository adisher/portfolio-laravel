@props([
    'name'     => null,
    'jobTitle' => null,
])

@php
    /**
     * Person entity. Built as a PHP array and json_encode'd so quotes and
     * unicode in any value cannot break the JSON-LD block.
     *
     * The @id is the whole point of this component. Without a stable
     * identifier, every mention of "Adil Sher" on this site is just a name
     * string, and Google's entity resolver binds it to the only Adil Sher it
     * already knows (the filmmaker). With it, this site declares its own node
     * that Organization, WebSite and every Article can point back at.
     */
    $site     = rtrim(config('app.url'), '/');
    $personId = $site . config('person.id_fragment', '#person');

    $person = [
        '@context' => 'https://schema.org',
        '@type'    => 'Person',
        '@id'      => $personId,
        'name'     => $name ?? config('person.name'),
        'jobTitle' => $jobTitle ?? config('person.job_title'),
        'description' => config('person.description'),
        'disambiguatingDescription' => config('person.disambiguating_description'),
        'url'      => $site . '/',
        'mainEntityOfPage' => [
            '@type' => 'ProfilePage',
            '@id'   => route('about'),
        ],
        'image'       => asset(config('person.image')),
        'homeLocation' => [
            '@type'   => 'Place',
            'address' => array_filter([
                '@type'           => 'PostalAddress',
                'addressLocality' => config('person.locality'),
                'addressRegion'   => config('person.region'),
                'addressCountry'  => config('person.country'),
            ]),
        ],
        'knowsAbout' => config('person.knows_about', []),
        'sameAs'     => config('person.same_as', []),
        'worksFor'   => ['@id' => $site . '#organization'],
    ];
@endphp

<script type="application/ld+json">
{!! json_encode($person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
