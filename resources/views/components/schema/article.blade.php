@props(['post'])

@php
    // Storage::url() can return a relative path on production, which makes the
    // Article image unusable for rich results. Force it absolute off APP_URL,
    // the same way layouts/app.blade.php does for og:image.
    $__site = rtrim(config('app.url'), '/');
    $schemaImage = $post->featured_image ? Storage::url($post->featured_image) : null;
    if ($schemaImage && ! \Illuminate\Support\Str::startsWith($schemaImage, ['http://', 'https://'])) {
        $schemaImage = $__site . '/' . ltrim($schemaImage, '/');
    }
@endphp

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ $post->title }}",
    "description": "{{ $post->meta_description ?? $post->excerpt }}",
    @if($schemaImage)
    "image": "{{ $schemaImage }}",
    @endif
    "author": {
        "@@type": "Person",
        "@@id": "{{ rtrim(config('app.url'), '/') . config('person.id_fragment', '#person') }}",
        "name": "{{ config('person.name') }}",
        "url": "{{ rtrim(config('app.url'), '/') }}/"
    },
    "publisher": {
        "@@type": "Organization",
        "@@id": "{{ rtrim(config('app.url'), '/') }}#organization",
        "name": "{{ config('person.organization_name', config('app.name')) }}",
        "url": "{{ rtrim(config('app.url'), '/') }}/",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('logo.png') }}"
        }
    },
    "datePublished": "{{ $post->published_at?->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ route('blog.show', $post->slug) }}"
    },
    "articleSection": "{{ $post->category->name ?? 'Blog' }}",
    "wordCount": {{ str_word_count(strip_tags($post->content)) }},
    @if($post->tags->count())
    "keywords": "{{ $post->tags->pluck('name')->implode(', ') }}",
    @endif
    "inLanguage": "en-US"
}
</script>
