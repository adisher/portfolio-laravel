@php
    /**
     * WebSite entity. Anchored with an @id and pointed at both the
     * Organization (publisher) and the Person (about), so the site, the brand
     * and the human resolve to one cluster.
     */
    $site = rtrim(config('app.url'), '/');

    $website = [
        '@context'    => 'https://schema.org',
        '@type'       => 'WebSite',
        '@id'         => $site . '#website',
        'name'        => config('person.organization_name', config('app.name')),
        'alternateName' => config('app.name'),
        'url'         => $site . '/',
        'description' => 'Personal site of ' . config('person.name') . ', ' . config('person.job_title')
                         . ', covering web development, AI trends, and tech news.',
        'publisher'   => ['@id' => $site . '#organization'],
        'about'       => ['@id' => $site . config('person.id_fragment', '#person')],
        'potentialAction' => [
            '@type'  => 'SearchAction',
            'target' => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => route('blog.search') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
        'inLanguage' => 'en-US',
    ];
@endphp

<script type="application/ld+json">
{!! json_encode($website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
