@props(['match'])

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SportsEvent",
    "name": "{{ $match->title }}",
    "description": "{{ $match->meta_description ?? $match->title . ' - ' . ($match->tournament->name ?? $match->sport->name) }}",
    "startDate": "{{ $match->scheduled_at->toIso8601String() }}",
    @if($match->ended_at)
    "endDate": "{{ $match->ended_at->toIso8601String() }}",
    @endif
    "eventStatus": "{{ $match->status === 'cancelled' ? 'https://schema.org/EventCancelled' : ($match->status === 'postponed' ? 'https://schema.org/EventPostponed' : 'https://schema.org/EventScheduled') }}",
    "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
    @if($match->venue)
    "location": {
        "@@type": "Place",
        "name": "{{ $match->venue }}",
        "address": {
            "@@type": "PostalAddress"
            @if($match->city)
            ,"addressLocality": "{{ $match->city }}"
            @endif
            @if($match->country)
            ,"addressCountry": "{{ $match->country }}"
            @endif
        }
    },
    @endif
    "homeTeam": {
        "@@type": "SportsTeam",
        "name": "{{ $match->homeTeam->name }}"
    },
    "awayTeam": {
        "@@type": "SportsTeam",
        "name": "{{ $match->awayTeam->name }}"
    },
    "competitor": [
        {
            "@@type": "SportsTeam",
            "name": "{{ $match->homeTeam->name }}"
        },
        {
            "@@type": "SportsTeam",
            "name": "{{ $match->awayTeam->name }}"
        }
    ],
    @if($match->tournament)
    "superEvent": {
        "@@type": "SportsEvent",
        "name": "{{ $match->tournament->name }}"
    },
    @endif
    "sport": "{{ $match->sport->name }}",
    "url": "{{ route('sports.match', [$match->sport->slug, $match->slug]) }}"
}
</script>
