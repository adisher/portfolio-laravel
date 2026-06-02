@props(['items'])

@php
$breadcrumbItems = [];
foreach ($items as $index => $item) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $item['name'],
        'item' => $item['url'] ?? null,
    ];
}
// Remove 'item' from last element (current page)
if (!empty($breadcrumbItems)) {
    unset($breadcrumbItems[count($breadcrumbItems) - 1]['item']);
}
@endphp

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
