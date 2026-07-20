{{-- Expects $workItem with voiceRecords.media loaded --}}
@php
    $candidates = $workItem->voiceRecords->where('status', 'candidate');
    $approved   = $workItem->voiceRecords->where('status', 'approved');
@endphp

<div class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
            Candidates <span class="text-gray-400 font-normal">({{ $candidates->count() }})</span>
        </h3>
        @if($candidates->isNotEmpty())
        <span class="text-xs text-gray-400">Open each source, confirm the quote, paste a screenshot, then Approve</span>
        @endif
    </div>
    @if($candidates->isEmpty())
    <p class="text-sm text-gray-400">No candidates waiting. Run a search above.</p>
    @else
    <div class="space-y-2">
        @foreach($candidates as $v)
            @include('admin.voices._voice-card', ['v' => $v])
        @endforeach
    </div>
    @endif
</div>

<div>
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
        Approved <span class="text-gray-400 font-normal">({{ $approved->count() }})</span>
        <span class="text-xs text-gray-400 font-normal">&middot; these are selectable when generating an article</span>
    </h3>
    @if($approved->isEmpty())
    <p class="text-sm text-gray-400">None approved yet.</p>
    @else
    <div class="space-y-2">
        @foreach($approved as $v)
            @include('admin.voices._voice-card', ['v' => $v])
        @endforeach
    </div>
    @endif
</div>
