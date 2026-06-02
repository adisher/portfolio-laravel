<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebSite",
    "name": "{{ config('app.name') }}",
    "url": "{{ config('app.url') }}",
    "description": "Personal brand platform covering AI trends, web development, and tech news.",
    "potentialAction": {
        "@@type": "SearchAction",
        "target": {
            "@@type": "EntryPoint",
            "urlTemplate": "{{ route('blog.search') }}?q={search_term_string}"
        },
        "query-input": "required name=search_term_string"
    },
    "inLanguage": "en-US"
}
</script>
