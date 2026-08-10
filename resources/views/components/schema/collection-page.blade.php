@props(['title', 'description', 'url', 'posts'])

@php
$itemListElement = [];
foreach ($posts as $index => $post) {
    $itemListElement[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => route('blog.show', $post->slug),
        'name' => $post->title,
    ];
}
@endphp

<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $title,
    'description' => $description,
    'url' => $url,
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => $itemListElement,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
