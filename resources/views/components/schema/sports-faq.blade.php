@props(['match'])

@php
$faqs = [];

// When does the match start?
if ($match->scheduled_at) {
    $faqs[] = [
        'question' => "When does {$match->title} start?",
        'answer' => "The match is scheduled for {$match->scheduled_at->format('F j, Y')} at {$match->scheduled_at->format('g:i A')} (local time).",
    ];
}

// Where is the match?
if ($match->venue) {
    $location = $match->venue;
    if ($match->city) $location .= ', ' . $match->city;
    if ($match->country) $location .= ', ' . $match->country;
    $faqs[] = [
        'question' => "Where is {$match->title} being played?",
        'answer' => "The match is being played at {$location}.",
    ];
}

// What was the result?
if ($match->status === 'completed' && $match->result_summary) {
    $faqs[] = [
        'question' => "What was the result of {$match->title}?",
        'answer' => $match->result_summary,
    ];
}

// What tournament?
if ($match->tournament) {
    $faqs[] = [
        'question' => "Which tournament is {$match->title} part of?",
        'answer' => "This match is part of the {$match->tournament->name}" . ($match->tournament->season ? " ({$match->tournament->season} season)" : '') . ".",
    ];
}

// What format?
if ($match->match_type) {
    $faqs[] = [
        'question' => "What format is {$match->title}?",
        'answer' => "This is a {$match->match_type} {$match->sport->name} match.",
    ];
}
@endphp

@if(count($faqs) > 0)
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($faqs as $index => $faq)
        {
            "@@type": "Question",
            "name": "{{ $faq['question'] }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $faq['answer'] }}"
            }
        }@if($index < count($faqs) - 1),@endif
        @endforeach
    ]
}
</script>
@endif
