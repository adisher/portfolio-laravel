@extends('layouts.admin')

@section('title', $workItem->name . ' - Manual')

@section('content')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $workItem->name }}</h1>
            <span class="status-badge">{{ ucfirst($workItem->type) }}</span>
            @unless($workItem->active)<span class="text-xs text-gray-400">(inactive)</span>@endunless
        </div>
        @if($workItem->tagline)<p class="text-gray-600 dark:text-gray-400 mt-1">{{ $workItem->tagline }}</p>@endif
        <div class="flex items-center gap-4 mt-2 text-sm">
            @if($workItem->url)<a href="{{ $workItem->url }}" target="_blank" class="text-teal hover:underline">{{ $workItem->url }}</a>@endif
            @if($workItem->project)<a href="{{ route('admin.projects.edit', $workItem->project) }}" class="text-gray-500 hover:text-teal">Linked: {{ $workItem->project->title }}</a>@endif
        </div>
    </div>
    <div class="flex gap-2 flex-shrink-0">
        <a href="{{ route('admin.work-items.edit', $workItem) }}" class="btn-primary text-sm">Edit</a>
        <a href="{{ route('admin.work-items.index') }}" class="btn-secondary text-sm">Back</a>
    </div>
</div>

@if(session('error'))
<div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">{{ session('error') }}</div>
@endif
@if(session('success'))
<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>
@endif

{{-- Generate an article --}}
<div class="admin-card p-6 mb-6 border-l-4 border-teal">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Generate an Article</h2>
    <p class="text-xs text-gray-400 mb-4">Pick an angle. The AI drafts a first-person article from this manual and its stories, then drops it in the editor for you to review and publish.</p>

    @if(empty($workItem->article_angles))
    <p class="text-sm text-gray-500">Add some <strong>Article Angles</strong> to this manual first, then you can generate from them.</p>
    @else
    <form action="{{ route('admin.work-items.generate-article', $workItem) }}" method="POST"
        x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1 grid grid-cols-1 {{ empty($workItem->hooks) ? '' : 'sm:grid-cols-2' }} gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Article angle</label>
                    <select name="angle" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @foreach($workItem->article_angles as $angle)
                        <option value="{{ $angle }}">{{ $angle }}</option>
                        @endforeach
                    </select>
                </div>
                @unless(empty($workItem->hooks))
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Opening hook</label>
                    <select name="hook"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        @foreach($workItem->hooks as $hook)
                        <option value="{{ $hook }}">{{ \Illuminate\Support\Str::limit($hook, 70) }}</option>
                        @endforeach
                        <option value="">None (concrete unnamed scene)</option>
                    </select>
                </div>
                @endunless
            </div>
            <button type="submit" :disabled="submitting"
                class="btn-primary text-sm whitespace-nowrap disabled:opacity-60">
                <span x-show="!submitting">Generate Draft</span>
                <span x-show="submitting" x-cloak>Generating... (up to 30s)</span>
            </button>
        </div>

        @php $approvedForGen = $workItem->voiceRecords->where('status', 'approved'); @endphp
        @if($approvedForGen->isNotEmpty())
        <div class="mt-4">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Include user voices <span class="text-gray-400">({{ $approvedForGen->count() }} approved, pick any)</span></label>
            <div class="space-y-1 max-h-40 overflow-y-auto pr-1">
                @foreach($approvedForGen as $v)
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="voice_ids[]" value="{{ $v->id }}" class="mt-1 rounded border-gray-300 dark:border-gray-600">
                    <span>
                        {{ \Illuminate\Support\Str::limit($v->quote, 90) }}
                        @if($v->attribution)<span class="text-gray-400">— {{ $v->attribution }}</span>@endif
                        @if($v->media_id)<span class="text-teal text-xs ml-1">[screenshot]</span>@endif
                    </span>
                </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mt-1">Selected voices are woven in as social proof. Ones with a screenshot get the image embedded automatically.</p>
        </div>
        @endif

        @unless($workItem->blog_category_id)
        <p class="text-xs text-amber-500 mt-2">Tip: set a <strong>Blog Category</strong> on this manual so generated articles file automatically.</p>
        @endunless
    </form>
    @endif
</div>

{{-- User Voices (social proof) --}}
<div class="admin-card p-6 mb-6">
    <div class="flex items-center justify-between mb-1">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">User Voices</h2>
        <form method="POST" action="{{ route('admin.work-items.find-voices', $workItem) }}" x-data="{ finding: false }" @submit="finding = true">
            @csrf
            <button type="submit" :disabled="finding" class="btn-secondary text-xs disabled:opacity-60">
                <span x-show="!finding">Find Voices</span>
                <span x-show="finding" x-cloak>Searching the web... (up to 60s)</span>
            </button>
        </form>
    </div>
    <p class="text-xs text-gray-400 mb-4">Real user sentiment for social proof. Find candidates via web search, review them, attach a screenshot from your Media library, then approve. Approved voices can be selected when generating an article.</p>

    @php
        $candidateVoices = $workItem->voiceRecords->where('status', 'candidate');
        $approvedVoices  = $workItem->voiceRecords->where('status', 'approved');
    @endphp

    <details class="mb-4">
        <summary class="text-xs text-teal cursor-pointer">+ Add a voice manually</summary>
        <form method="POST" action="{{ route('admin.work-items.voices.store', $workItem) }}" class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
            @csrf
            <input name="quote" required placeholder="Verbatim quote" class="sm:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <input name="attribution" placeholder="Attribution (e.g. r/musicmarketing)" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <input name="source_url" type="url" placeholder="https://source-url" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <button class="btn-secondary text-xs sm:col-span-2 justify-self-start">Add (approved)</button>
        </form>
    </details>

    @if($candidateVoices->isNotEmpty())
    <p class="text-xs uppercase tracking-wide text-gray-400 mb-2">Candidates ({{ $candidateVoices->count() }}) &middot; verify each quote on its source before approving</p>
    <div class="space-y-2 mb-5">
        @foreach($candidateVoices as $v)
            @include('admin.work-items._voice-card', ['v' => $v, 'mediaOptions' => $mediaOptions])
        @endforeach
    </div>
    @endif

    <p class="text-xs uppercase tracking-wide text-gray-400 mb-2">Approved ({{ $approvedVoices->count() }})</p>
    @if($approvedVoices->isEmpty())
    <p class="text-sm text-gray-400">None approved yet.</p>
    @else
    <div class="space-y-2">
        @foreach($approvedVoices as $v)
            @include('admin.work-items._voice-card', ['v' => $v, 'mediaOptions' => $mediaOptions])
        @endforeach
    </div>
    @endif
</div>

@php
    $listBlocks = [
        'pain_points'     => ['Pain Points', 'text-red-500'],
        'objections'      => ['Objections', 'text-orange-500'],
        'key_outcomes'    => ['Key Outcomes / Proof', 'text-green-500'],
        'differentiators' => ['Differentiators', 'text-teal-500'],
        'article_angles'  => ['Article Angles', 'text-purple-500'],
        'hooks'           => ['Opening Hooks', 'text-blue-500'],
        'screenshots'     => ['Screenshot Library', 'text-amber-500'],
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Positioning --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Positioning</h2>
        @if($workItem->target_audience)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Target Audience</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">{{ $workItem->target_audience }}</p>
        @endif
        @if($workItem->how_it_helps)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">How It Helps</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 whitespace-pre-line">{{ $workItem->how_it_helps }}</p>
        @endif
        @if($workItem->call_to_action)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Call To Action</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 whitespace-pre-line">{{ $workItem->call_to_action }}</p>
        @endif
        @if($workItem->tech_stack)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Tech Stack</p>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $workItem->tech_stack }}</p>
        @endif
    </div>

    {{-- Target keywords + proof links --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Target Keywords</h2>
        @if(!empty($workItem->target_keywords))
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach($workItem->target_keywords as $kw)
            <span class="text-xs px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $kw }}</span>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400 mb-5">None set.</p>
        @endif

        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Proof Links</h2>
        @if(!empty($workItem->proof_links))
        <ul class="space-y-1">
            @foreach($workItem->proof_links as $link)
            <li><a href="{{ $link }}" target="_blank" class="text-sm text-teal hover:underline break-all">{{ $link }}</a></li>
            @endforeach
        </ul>
        @else
        <p class="text-sm text-gray-400">None yet (demo video, case study, testimonials go here).</p>
        @endif
    </div>

    {{-- List blocks --}}
    @foreach($listBlocks as $field => [$label, $color])
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">{{ $label }}</h2>
        @if(!empty($workItem->{$field}))
        <ul class="space-y-2">
            @foreach($workItem->{$field} as $entry)
            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <span class="{{ $color }} mt-0.5">•</span>
                <span>{{ $entry }}</span>
            </li>
            @endforeach
        </ul>
        @else
        <p class="text-sm text-gray-400">None added.</p>
        @endif
    </div>
    @endforeach
</div>

@if($workItem->stories)
<div class="admin-card p-6 mt-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Stories &amp; Real Details</h2>
    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $workItem->stories }}</p>
</div>
@endif

@if($workItem->notes)
<div class="admin-card p-6 mt-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Notes</h2>
    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $workItem->notes }}</p>
</div>
@endif
@endsection
