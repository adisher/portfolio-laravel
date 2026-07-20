{{-- Expects $run (VoiceSearchRun) --}}
@php
    $tone = ['success' => 'text-green-600', 'empty' => 'text-amber-500', 'failed' => 'text-red-500'][$run->status] ?? 'text-gray-500';
@endphp
<details class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
    <summary class="cursor-pointer flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
        <span class="font-medium {{ $tone }}">{{ ucfirst($run->status) }}</span>
        <span class="text-gray-700 dark:text-gray-300">{{ ucfirst($run->engine) }}</span>
        <span class="text-xs text-gray-400">
            {{ $run->candidates_found }} new &middot;
            {{ count($run->queries ?? []) }} {{ \Illuminate\Support\Str::plural('query', count($run->queries ?? [])) }} &middot;
            ${{ number_format((float) $run->cost_usd, 4) }} &middot;
            {{ $run->created_at->diffForHumans() }}
        </span>
    </summary>

    <div class="mt-3 space-y-3">
        @if($run->note)
        <p class="text-xs text-amber-600 dark:text-amber-400">{{ $run->note }}</p>
        @endif

        @if(!empty($run->queries))
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Searches performed</p>
            <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-0.5">
                @foreach($run->queries as $q)
                <li>&middot; {{ $q }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($run->raw)
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Raw output</p>
            <pre class="text-[11px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-2 overflow-x-auto max-h-56 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($run->raw, 4000) }}</pre>
        </div>
        @endif
    </div>
</details>
