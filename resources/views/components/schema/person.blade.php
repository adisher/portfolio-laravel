@props(['name' => 'Adil Sher', 'jobTitle' => 'Full Stack Developer'])

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Person",
    "name": "{{ $name }}",
    "jobTitle": "{{ $jobTitle }}",
    "url": "{{ config('app.url') }}",
    "sameAs": [
        "https://x.com/adilsherdotpro",
        "https://www.linkedin.com/in/adilsher/",
        "https://github.com/adilsher"
    ],
    "knowsAbout": [
        "Laravel",
        "PHP",
        "JavaScript",
        "Vue.js",
        "React",
        "Web Development",
        "AI",
        "Machine Learning",
        "Full Stack Development"
    ],
    "worksFor": {
        "@@type": "Organization",
        "name": "{{ config('app.name') }}"
    }
}
</script>
