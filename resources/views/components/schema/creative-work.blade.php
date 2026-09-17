@props(['project'])

@php
    /**
     * CreativeWork entity for a portfolio project.
     *
     * The point of this component is the author reference. AI Overviews cite
     * these project pages when answering "who is Adil Sher", and until now the
     * author was not stated at all, leaving the page as a bare name string for
     * an entity resolver to guess at. Pointing author at the Person @id ties
     * the cited page to this site's own declared entity.
     */
    $site     = rtrim(config('app.url'), '/');
    $personId = $site . config('person.id_fragment', '#person');

    // Build URLs off APP_URL rather than route()/Storage::url(): both follow
    // the request host and can come out relative on production, which would
    // hand a resolver a different @id per host.
    $workUrl = $site . '/portfolio/' . $project->slug;
    $image   = $project->featured_image ? Storage::url($project->featured_image) : null;
    if ($image && ! Str::startsWith($image, ['http://', 'https://'])) {
        $image = $site . '/' . ltrim($image, '/');
    }

    $work = array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => 'CreativeWork',
        '@id'         => $workUrl . '#project',
        'name'        => $project->title,
        'headline'    => $project->title,
        'description' => $project->short_description,
        'url'         => $workUrl,
        'image'       => $image,
        'author'      => [
            '@type' => 'Person',
            '@id'   => $personId,
            'name'  => config('person.name'),
            'url'   => $site . '/',
        ],
        'creator' => ['@id' => $personId],
        'publisher' => ['@id' => $site . '#organization'],
        'dateCreated' => $project->project_date?->toDateString(),
        'keywords'    => is_array($project->technologies) && $project->technologies
            ? implode(', ', $project->technologies)
            : null,
        'isPartOf'   => ['@id' => $site . '#website'],
        'inLanguage' => 'en-US',
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<script type="application/ld+json">
{!! json_encode($work, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
