@props(['post'])

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ $post->title }}",
    "description": "{{ $post->meta_description ?? $post->excerpt }}",
    @if($post->featured_image)
    "image": "{{ Storage::url($post->featured_image) }}",
    @endif
    "author": {
        "@@type": "Person",
        "name": "{{ $post->user->name ?? config('app.name') }}",
        "url": "{{ route('about') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "{{ config('app.name') }}",
        "url": "{{ config('app.url') }}",
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
