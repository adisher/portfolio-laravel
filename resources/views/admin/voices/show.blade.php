@extends('layouts.admin')

@section('title', $workItem->name . ' - Voices')

@section('content')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $workItem->name }} <span class="text-gray-400 font-normal">&middot; Voices</span></h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Search for real user sentiment, verify each one on its source, paste a screenshot, then approve.</p>
    </div>
    <div class="flex gap-2 flex-shrink-0">
        <a href="{{ route('admin.work-items.show', $workItem) }}" class="btn-secondary text-sm">Work item</a>
        <a href="{{ route('admin.voices.index') }}" class="btn-secondary text-sm">All voices</a>
    </div>
</div>

@if(session('error'))
<div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">{{ session('error') }}</div>
@endif
@if(session('success'))
<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>
@endif

{{-- Find --}}
<div class="admin-card p-6 mb-6 border-l-4 border-teal">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Find voices</h2>
    <p class="text-xs text-gray-400 mb-4">Searches the web for people describing this product's pain points. Nothing is auto-approved: results arrive as candidates for you to verify.</p>

    <form id="find-form" action="{{ route('admin.voices.find', $workItem) }}" method="POST" class="flex flex-col sm:flex-row gap-3 sm:items-end">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Engine</label>
            <select name="engine" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="claude">Claude (Haiku) &mdash; about $0.08 per run</option>
                <option value="brave" {{ $braveConfigured ? '' : 'disabled' }}>Brave &mdash; paid, ~$5 per 1,000 queries{{ $braveConfigured ? '' : ' (no key set)' }}</option>
            </select>
        </div>
        <button type="submit" id="find-btn" class="btn-primary text-sm whitespace-nowrap">Find voices</button>
        <p class="text-xs text-gray-400 sm:ml-2 sm:pb-2">
            Keywords used come from this manual's <a href="{{ route('admin.work-items.edit', $workItem) }}" class="text-teal hover:underline">target keywords</a>.
        </p>
    </form>

    {{-- Live progress --}}
    <div id="find-progress" class="hidden mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <svg class="animate-spin h-4 w-4 text-teal" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
            </svg>
            <span id="find-stage" class="text-sm text-gray-700 dark:text-gray-300">Starting…</span>
            <span id="find-timer" class="text-xs text-gray-400 ml-auto">0s</span>
        </div>
    </div>

    {{-- Result summary --}}
    <div id="find-summary" class="hidden mt-4 p-3 rounded-lg text-sm"></div>
</div>

{{-- Candidates + approved --}}
<div id="voices-results" class="admin-card p-6 mb-6">
    @include('admin.voices._lists', ['workItem' => $workItem])
</div>

{{-- Manual add --}}
<div class="admin-card p-6 mb-6">
    <details>
        <summary class="text-sm font-semibold text-gray-900 dark:text-white cursor-pointer">Add a voice manually</summary>
        <form method="POST" action="{{ route('admin.voices.store', $workItem) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
            @csrf
            <input name="quote" required placeholder="Verbatim quote" class="sm:col-span-2 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <input name="attribution" placeholder="Attribution (e.g. r/musicmarketing)" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <input name="source_url" type="url" placeholder="https://source-url" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <button class="btn-secondary text-xs sm:col-span-2 justify-self-start">Add as approved</button>
        </form>
    </details>
</div>

{{-- Run log --}}
<div class="admin-card p-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Search history</h2>
    <p class="text-xs text-gray-400 mb-4">Every run is logged with the exact searches, raw output and cost, so an empty result is never a mystery.</p>
    <div id="run-log" class="space-y-2">
        @forelse($runs as $run)
            @include('admin.voices._run', ['run' => $run])
        @empty
            <p id="run-log-empty" class="text-sm text-gray-400">No searches run yet.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // ---- paste-to-attach screenshots (re-runnable after AJAX injects cards) ----
    let armedForm = null;

    function fileFromBlob(blob) {
        const type = blob.type || 'image/png';
        const ext = (type.split('/')[1] || 'png').replace('jpeg', 'jpg');
        return new File([blob], 'voice-' + Date.now() + '.' + ext, { type: type });
    }

    function attachFile(form, f) {
        const dt = new DataTransfer();
        dt.items.add(f);
        form.querySelector('.voice-file').files = dt.files;
        const preview = form.querySelector('.voice-preview');
        preview.src = URL.createObjectURL(f);
        preview.classList.remove('hidden');
        form.querySelector('.voice-save').classList.remove('hidden');
        form.querySelector('.voice-paste').textContent = 'Pasted, click Save';
    }

    function initVoiceUploads() {
        document.querySelectorAll('.voice-upload').forEach(function (form) {
            if (form.dataset.bound) return;
            form.dataset.bound = '1';

            form.querySelector('.voice-choose').addEventListener('click', function () {
                form.querySelector('.voice-file').click();
            });
            form.querySelector('.voice-file').addEventListener('change', function () {
                if (this.files[0]) attachFile(form, this.files[0]);
            });
            form.querySelector('.voice-paste').addEventListener('click', async function () {
                armedForm = form;
                if (navigator.clipboard && navigator.clipboard.read) {
                    try {
                        const items = await navigator.clipboard.read();
                        for (const item of items) {
                            const type = item.types.find(function (t) { return t.startsWith('image/'); });
                            if (type) { attachFile(form, fileFromBlob(await item.getType(type))); return; }
                        }
                        this.textContent = 'No image copied — press Ctrl+V';
                    } catch (e) {
                        this.textContent = 'Press Ctrl+V now';
                    }
                } else {
                    this.textContent = 'Press Ctrl+V now';
                }
            });
        });
    }

    document.addEventListener('paste', function (e) {
        if (!armedForm || !e.clipboardData) return;
        for (const it of e.clipboardData.items) {
            if (it.type && it.type.startsWith('image/')) {
                attachFile(armedForm, fileFromBlob(it.getAsFile()));
                e.preventDefault();
                return;
            }
        }
    });

    // ---- Find voices, with live progress ----
    const form = document.getElementById('find-form');
    const btn = document.getElementById('find-btn');
    const progress = document.getElementById('find-progress');
    const stage = document.getElementById('find-stage');
    const timerEl = document.getElementById('find-timer');
    const summary = document.getElementById('find-summary');
    const results = document.getElementById('voices-results');
    const runLog = document.getElementById('run-log');

    const stages = [
        'Building search queries from this manual…',
        'Searching the web…',
        'Reading results…',
        'Filtering out promo and duplicates…',
        'Saving candidates…'
    ];

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        btn.disabled = true;
        btn.classList.add('opacity-60');
        summary.classList.add('hidden');
        progress.classList.remove('hidden');

        let i = 0, secs = 0;
        stage.textContent = stages[0];
        const stageTimer = setInterval(function () {
            i = Math.min(i + 1, stages.length - 1);
            stage.textContent = stages[i];
        }, 3000);
        const tick = setInterval(function () {
            secs += 1;
            timerEl.textContent = secs + 's';
        }, 1000);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.listsHtml) {
                results.innerHTML = data.listsHtml;
                initVoiceUploads();
            }
            if (data.runHtml) {
                const empty = document.getElementById('run-log-empty');
                if (empty) empty.remove();
                runLog.insertAdjacentHTML('afterbegin', data.runHtml);
            }

            let tone = 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800';
            let msg = 'Found ' + data.created + ' new candidate(s). Review them below.';
            if (data.status === 'failed') {
                tone = 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800';
                msg = data.note || 'Search failed.';
            } else if (data.created === 0) {
                tone = 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800';
                msg = (data.note || 'No new candidates.') + ' Open the newest entry in Search history to see exactly what was searched.';
            }
            summary.className = 'mt-4 p-3 rounded-lg text-sm ' + tone;
            summary.textContent = msg;
            summary.classList.remove('hidden');
        } catch (err) {
            summary.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800';
            summary.textContent = 'Request failed: ' + err.message;
            summary.classList.remove('hidden');
        } finally {
            clearInterval(stageTimer);
            clearInterval(tick);
            progress.classList.add('hidden');
            timerEl.textContent = '0s';
            btn.disabled = false;
            btn.classList.remove('opacity-60');
        }
    });

    initVoiceUploads();
})();
</script>
@endpush
