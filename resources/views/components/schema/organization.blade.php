@php
    /**
     * Organization entity for the site itself.
     *
     * It carries a stable @id and is explicitly tied to the Person @id through
     * founder/employee, so a resolver reads the brand and the human as one
     * connected cluster instead of two loose name strings.
     *
     * Name comes from config/person.php, not APP_NAME: APP_NAME is the handle
     * "adilsherdotpro", which is useless as an entity name.
     */
    $site = rtrim(config('app.url'), '/');

    $organization = [
        '@context'      => 'https://schema.org',
        '@type'         => 'Organization',
        '@id'           => $site . '#organization',
        'name'          => config('person.organization_name', config('app.name')),
        'alternateName' => config('app.name'),
        'url'           => $site . '/',
        'logo'          => [
            '@type' => 'ImageObject',
            'url'   => asset('logo.png'),
        ],
        'image'    => asset('logo.png'),
        'founder'  => ['@id' => $site . config('person.id_fragment', '#person')],
        'employee' => ['@id' => $site . config('person.id_fragment', '#person')],
        'sameAs'   => config('person.same_as', []),
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'contactType' => 'customer service',
            'url'         => route('contact'),
        ],
    ];
@endphp

<script type="application/ld+json">
{!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
