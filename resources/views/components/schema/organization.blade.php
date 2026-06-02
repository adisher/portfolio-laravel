<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "name": "{{ config('app.name') }}",
    "url": "{{ config('app.url') }}",
    "logo": "{{ asset('logo.png') }}",
    "sameAs": [
        "https://x.com/adilsherdotpro",
        "https://www.linkedin.com/in/adilsher/",
        "https://github.com/adilsher"
    ],
    "contactPoint": {
        "@@type": "ContactPoint",
        "contactType": "customer service",
        "url": "{{ route('contact') }}"
    }
}
</script>
