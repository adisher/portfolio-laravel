@extends('layouts.admin')

@section('title', 'Review reel - Social Studio')

@php
    $post = $draft->blogPost;
    $seconds = $draft->duration_seconds ?: $draft->plannedSeconds();
    $sceneTypes = ['hook' => 'Hook', 'context' => 'Context', 'point' => 'Point', 'end' => 'End card'];
    $visuals = ['featured_image' => 'Featured image', 'portrait' => 'My photo', 'screenshot' => 'Product screenshot', 'text' => 'Brand background', 'code' => 'Code snippet'];
@endphp

@section('content')
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <div class="min-w-0">
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('admin.social.index') }}" class="hover:text-gray-700">Social Accounts</a>
            <span>›</span>
            <a href="{{ route('admin.social.reels.index') }}" class="hover:text-gray-700">Reels</a>
            <span>›</span>
            <span>Review</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Review reel</h1>
        <p class="text-gray-600 dark:text-gray-400">
            From <a href="{{ route('blog.show', $post->slug) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $post->title }}</a>
        </p>
    </div>
    <div class="flex items-center gap-2">
        <span class="status-badge {{ $draft->status === 'ready' ? 'status-published' : ($draft->status === 'render_failed' ? 'status-unread' : 'status-draft') }}">{{ $draft->statusLabel() }}</span>
        <form method="POST" action="{{ route('admin.social.reels.regenerate', $draft) }}"
            onsubmit="return confirm('Write a fresh script and captions? Your edits and any approvals will be replaced.')">
            @csrf
            <button type="submit" class="btn-secondary text-sm py-2 px-4">Rewrite with AI</button>
        </form>
        <form method="POST" action="{{ route('admin.social.reels.destroy', $draft) }}"
            onsubmit="return confirm('Delete this reel?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:underline px-2">Delete</button>
        </form>
    </div>
</div>

@if($errors->any())
    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900 text-red-700 dark:text-red-200 text-sm">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- LEFT: player, render, approvals, history --}}
    <div class="space-y-6">
        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Watch</h2>

            @if($draft->isRendered())
                <video controls playsinline preload="metadata"
                    src="{{ $draft->videoUrl() }}" @if($draft->coverUrl()) poster="{{ $draft->coverUrl() }}" @endif
                    style="width: 100%; max-width: 320px; aspect-ratio: 9 / 16; display: block; margin: 0 auto; border-radius: 12px; background: #0D1B2A;"></video>
                <div class="text-xs text-gray-400 text-center mt-2">
                    {{ $seconds }} s · rendered {{ $draft->rendered_at->format('M j, H:i') }}
                    · <a href="{{ $draft->videoUrl() }}" download class="text-blue-600 hover:underline">Download MP4</a>
                </div>
            @else
                <div style="width: 100%; max-width: 320px; aspect-ratio: 9 / 16; margin: 0 auto; border-radius: 12px; background: #0D1B2A; display: flex; align-items: center; justify-content: center; text-align: center; padding: 24px;">
                    <div>
                        <div style="color: #41EAD4; font-weight: 700; font-size: 18px; margin-bottom: 8px;">
                            {{ collect($draft->scenes())->firstWhere('type', 'hook')['on_screen_text'] ?? 'Not rendered yet' }}
                        </div>
                        <div style="color: #93A9B8; font-size: 13px;">
                            @if($draft->status === 'rendering')
                                Rendering now. You'll get an email when it's ready.
                            @else
                                Not rendered yet. About {{ $seconds }} seconds planned.
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($draft->status === 'render_failed' && $draft->error)
                <div class="mt-4 p-3 rounded bg-red-50 dark:bg-red-900 text-xs text-red-700 dark:text-red-200 font-mono break-words">{{ $draft->error }}</div>
            @endif

            @if($draft->status === 'rendering')
                <div x-data="renderWatch(@js(route('admin.social.reels.status', $draft)))">
                    <button type="button" disabled class="btn-primary w-full mt-4 text-sm py-2 opacity-50 cursor-not-allowed">Rendering...</button>
                    <p class="text-xs text-gray-400 mt-2 text-center">
                        Started {{ $draft->render_requested_at?->diffForHumans() }}. Takes about 10 to 15 minutes; this page refreshes when it's done.
                        <a href="{{ $runsUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Watch on GitHub</a>
                    </p>
                    <form method="POST" action="{{ route('admin.social.reels.stop-render', $draft) }}" class="mt-2 text-center"
                        onsubmit="return confirm('Stop waiting for this render? Use this if the run failed or was cancelled on GitHub.')">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:underline">Stop waiting</button>
                    </form>
                </div>
            @elseif(! $studioReady)
                <a href="{{ route('admin.social.reels.settings') }}" class="btn-primary w-full mt-4 text-sm py-2">Set up voice and GitHub to render</a>
                <p class="text-xs text-gray-400 mt-2 text-center">One-time setup: upload your voice recording and connect GitHub.</p>
            @else
                <form method="POST" action="{{ route('admin.social.reels.render', $draft) }}"
                    @if($draft->isRendered()) onsubmit="return confirm('Render this reel again? The current video will be replaced and approvals reset.')" @endif>
                    @csrf
                    <button type="submit" class="btn-primary w-full mt-4 text-sm py-2">{{ $draft->isRendered() ? 'Render again' : 'Render video' }}</button>
                </form>
                <p class="text-xs text-gray-400 mt-2 text-center">Uses your cloned voice and brand templates. Save script changes first.</p>
            @endif
        </div>

        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Approve per platform</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Each platform is approved on its own. Publishing to Instagram and YouTube is connected in a later step; approvals are kept until then.</p>

            <div class="space-y-4">
                @foreach($draft->targets as $target)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $target->label() }}</span>
                            <span class="text-xs px-2 py-1 rounded-full {{ $target->status === 'approved' ? 'bg-green-100 text-green-800' : ($target->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700') }}">
                                {{ ucfirst($target->status) }}
                            </span>
                        </div>

                        @if($target->status === 'approved')
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Scheduled for {{ $target->scheduled_for?->format('M j, Y H:i') }}</p>
                        @endif
                        @if($target->external_url)
                            <a href="{{ $target->external_url }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline">View live post</a>
                        @endif

                        @if($target->status !== 'posted')
                            <form method="POST" action="{{ route('admin.social.reels.targets.approve', [$draft, $target]) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <label class="block text-xs text-gray-500 dark:text-gray-400">Post at (leave empty for as soon as possible)</label>
                                <input type="datetime-local" name="scheduled_for"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                    value="{{ $target->scheduled_for?->format('Y-m-d\TH:i') }}">
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary text-xs py-1 px-3 {{ $draft->isRendered() ? '' : 'opacity-50 cursor-not-allowed' }}"
                                        @disabled(! $draft->isRendered())>{{ $target->status === 'approved' ? 'Update' : 'Approve' }}</button>
                                </div>
                            </form>
                            @if($target->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.social.reels.targets.reject', [$draft, $target]) }}" class="mt-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Don't post here</button>
                                </form>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
            @unless($draft->isRendered())
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-3">Approval unlocks once the video is rendered and you've watched it.</p>
            @endunless
        </div>

        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">History</h2>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li>Script written: {{ $draft->created_at->format('M j, Y H:i') }}</li>
                @if($draft->updated_at->ne($draft->created_at))
                    <li>Last edited: {{ $draft->updated_at->format('M j, Y H:i') }}</li>
                @endif
                @if($draft->render_requested_at)
                    <li>Render requested: {{ $draft->render_requested_at->format('M j, Y H:i') }}</li>
                @endif
                @if($draft->rendered_at)
                    <li>Rendered: {{ $draft->rendered_at->format('M j, Y H:i') }}</li>
                @endif
                @foreach($draft->targets as $target)
                    @if($target->approved_at)
                        <li>{{ $target->label() }} approved: {{ $target->approved_at->format('M j, Y H:i') }}</li>
                    @endif
                    @if($target->posted_at)
                        <li>{{ $target->label() }} posted: {{ $target->posted_at->format('M j, Y H:i') }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    {{-- RIGHT: script + captions editor --}}
    <form method="POST" action="{{ route('admin.social.reels.update', $draft) }}" class="lg:col-span-2"
        x-data="reelEditor(@js($draft->scenes()))">
        @csrf
        @method('PUT')
        <div class="space-y-6">

        <div class="admin-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Script</h2>
                <div class="text-sm" :class="inRange() ? 'text-gray-500 dark:text-gray-400' : 'text-amber-600 dark:text-amber-400'">
                    <span x-text="totalSeconds()"></span> s ·
                    <span x-text="totalWords()"></span> spoken words
                    <span x-show="!inRange()"> (aim for {{ config('social.reels.min_seconds') }}-{{ config('social.reels.max_seconds') }} s, {{ config('social.reels.min_words') }}-{{ config('social.reels.max_words') }} words)</span>
                </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">The big text on screen, the line spoken in your voice, and what fills the frame. Each scene lasts as long as its spoken line takes. Changing the script means the video has to be rendered again.</p>

            <div class="space-y-4">
                <template x-for="(scene, i) in scenes" :key="scene._key">
                    <div class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="text-xs font-bold text-gray-400" x-text="'#' + (i + 1)"></span>
                            <select :name="`scenes[${i}][type]`" x-model="scene.type"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                @foreach($sceneTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <select :name="`scenes[${i}][visual]`" x-model="scene.visual"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                @foreach($visuals as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <span class="text-sm text-gray-500 dark:text-gray-400" title="Worked out from the spoken words">
                                about <span x-text="sceneSeconds(scene)"></span> s
                            </span>
                            <div class="ml-auto flex items-center gap-2 text-sm">
                                <button type="button" class="text-gray-500 hover:text-gray-700" @click="move(i, -1)" :disabled="i === 0" title="Move up">↑</button>
                                <button type="button" class="text-gray-500 hover:text-gray-700" @click="move(i, 1)" :disabled="i === scenes.length - 1" title="Move down">↓</button>
                                <button type="button" class="text-red-600 hover:underline" @click="remove(i)" x-show="scenes.length > 1">Remove</button>
                            </div>
                        </div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">On screen (max 8 words)</label>
                        <input type="text" :name="`scenes[${i}][on_screen_text]`" x-model="scene.on_screen_text" maxlength="120"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white mb-3">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Spoken in your voice</label>
                        <textarea rows="2" :name="`scenes[${i}][voice_line]`" x-model="scene.voice_line" maxlength="500"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white mb-3"></textarea>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">What to show</label>
                        <textarea rows="2" :name="`scenes[${i}][visual_note]`" x-model="scene.visual_note" maxlength="800"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            :class="scene.visual === 'code' ? 'font-mono' : ''"></textarea>
                    </div>
                </template>
            </div>

            <button type="button" class="btn-secondary text-sm py-2 px-4 mt-4" @click="add()" x-show="scenes.length < 10">Add scene</button>
        </div>

        <div class="admin-card p-6" id="reel-pronunciations"
            x-data="{ text: @js($draft->pronunciationText()) }"
            @add-pronunciation.window="
                const word = $event.detail.replace(/[.,!?;:]+$/, '');
                if (! text.split(/\n/).some(l => l.split('=')[0].trim().toLowerCase() === word.toLowerCase())) {
                    text = (text.trim() ? text.trim() + '\n' : '') + word + ' = ';
                }
                $el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                $nextTick(() => { $refs.box.focus(); $refs.box.setSelectionRange(text.length, text.length); });
            ">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-1">This reel's pronunciations</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                Written by the AI with the script, for the names, acronyms and terms in this reel. One per line: <span class="font-mono">word = how to say it</span>,
                for example <span class="font-mono">GPT-5.6 = G P T five point six</span>. These win over your
                <a href="{{ route('admin.social.reels.settings') }}" class="text-blue-600 hover:underline">global list</a>. Only the voice changes; captions keep the real spelling.
            </p>
            <textarea name="pronunciations" rows="5" x-ref="box" x-model="text"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm"></textarea>
        </div>

        <div class="admin-card p-6" x-data="{ tab: 'instagram' }">
            <div class="flex gap-4 border-b border-gray-200 dark:border-gray-700 mb-4">
                <button type="button" class="pb-2 text-sm font-medium" @click="tab = 'instagram'"
                    :class="tab === 'instagram' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 dark:text-gray-400'">Instagram</button>
                <button type="button" class="pb-2 text-sm font-medium" @click="tab = 'youtube'"
                    :class="tab === 'youtube' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 dark:text-gray-400'">YouTube</button>
            </div>

            <div x-show="tab === 'instagram'" class="space-y-4" x-data="{ caption: @js($draft->caption('instagram', 'caption', '')) }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Caption</label>
                    <textarea name="instagram_caption" rows="7" maxlength="2200" x-model="caption"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    <p class="text-xs text-gray-400 mt-1"><span x-text="caption.length"></span> / 2200. Links don't work in Instagram captions, so it points to the link in your bio.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hashtags</label>
                    <input type="text" name="instagram_hashtags" value="{{ collect($draft->caption('instagram', 'hashtags', []))->map(fn ($t) => '#' . $t)->implode(' ') }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-400 mt-1">Separated by spaces. Added after the caption when posting.</p>
                </div>
            </div>

            <div x-show="tab === 'youtube'" x-cloak class="space-y-4" x-data="{ title: @js($draft->caption('youtube', 'title', '')) }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
                    <input type="text" name="youtube_title" maxlength="100" x-model="title"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-400 mt-1"><span x-text="title.length"></span> / 100. Keep "#Shorts" in it.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea name="youtube_description" rows="6" maxlength="5000"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $draft->caption('youtube', 'description', '') }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Includes the tracked link to the full article.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                    <input type="text" name="youtube_tags" value="{{ implode(', ', $draft->caption('youtube', 'tags', [])) }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-400 mt-1">Separated by commas.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
        </div>
    </form>
</div>

{{-- Voice check: what the speech check heard in the last render --}}
@php $report = $draft->render_report; @endphp
@if($report && ! empty($report['scenes']))
    @php
        $flagged = collect($report['scenes'])->sum(fn ($s) => collect($s['words'] ?? [])->where('ok', false)->count());
    @endphp
    <div class="admin-card p-6 mt-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Voice check</h2>
            <span class="text-xs px-2 py-1 rounded-full {{ $flagged ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                {{ $flagged ? $flagged . ' ' . \Illuminate\Support\Str::plural('word', $flagged) . ' not heard as written' : 'Every word heard as written' }}
            </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            After rendering, a speech recogniser listened to each line. Words it heard differently are marked red: usually a mispronunciation.
            Click a red word to add it to this reel's pronunciations, type how it should be said, save, and render again.
            The recogniser can miss a slightly odd pronunciation, so trust your ears too.
        </p>

        <div class="space-y-4">
            @foreach($report['scenes'] as $line)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-400">Scene {{ $line['scene'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            match {{ round(($line['match'] ?? 0) * 100) }}%
                            · take {{ $line['kept_take'] ?? '?' }} of {{ count($line['takes'] ?? []) }}
                            @if(! empty($line['slowed_to'])) · slowed to {{ round($line['slowed_to'] * 100) }}% speed @endif
                        </span>
                    </div>
                    <p class="text-gray-900 dark:text-white mb-1">
                        @foreach($line['words'] ?? [] as $word)
                            @if($word['ok'])
                                <span>{{ $word['text'] }}</span>
                            @else
                                <button type="button" class="bg-red-100 text-red-800 rounded px-1 hover:underline" title="Heard differently. Click to add it to this reel's pronunciations."
                                    onclick="window.dispatchEvent(new CustomEvent('add-pronunciation', { detail: @js($word['text']) }))">{{ $word['text'] }}</button>
                            @endif
                        @endforeach
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-medium">Heard:</span> {{ $line['heard'] ?: '(nothing recognised)' }}</p>
                    @if(($line['spoken'] ?? '') !== ($line['script'] ?? ''))
                        <p class="text-xs text-gray-400 mt-1">Spoken as: {{ $line['spoken'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        @if(! empty($report['reference']))
            <p class="text-xs text-gray-400 mt-4">
                Voice: exaggeration {{ $report['voice']['exaggeration'] ?? '?' }}, cfg {{ $report['voice']['cfg_weight'] ?? '?' }},
                temperature {{ $report['voice']['temperature'] ?? '?' }} · reference from {{ $report['reference']['window_start'] ?? '?' }} s
                into your recording, {{ $report['reference']['total_seconds'] ?? '?' }} s used for the voice print.
            </p>
        @endif
    </div>
@endif
@endsection

@push('scripts')
<script>
// While a render runs, check its status every 20 s and reload once it changes.
function renderWatch(url) {
    return {
        init() {
            const timer = setInterval(async () => {
                try {
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (res.ok && (await res.json()).status !== 'rendering') {
                        clearInterval(timer);
                        window.location.reload();
                    }
                } catch (e) { /* try again on the next tick */ }
            }, 20000);
        },
    };
}

function reelEditor(initial) {
    let key = 0;
    const blank = () => ({ _key: ++key, type: 'point', on_screen_text: '', voice_line: '', visual: 'text', visual_note: '' });
    const pace = {{ (float) config('social.reels.words_per_second', 2.5) }};
    const words = text => (text || '').trim().split(/\s+/).filter(Boolean).length;

    return {
        scenes: (initial.length ? initial : [blank()]).map(s => ({ ...s, _key: ++key })),
        add() { this.scenes.splice(this.scenes.length - 1, 0, blank()); },
        remove(i) { this.scenes.splice(i, 1); },
        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.scenes.length) return;
            [this.scenes[i], this.scenes[j]] = [this.scenes[j], this.scenes[i]];
        },
        // Mirrors SocialDraft::estimateSeconds(): words at the voice's pace plus a breath.
        sceneSeconds(s) {
            const floor = s.visual === 'code' ? 4 : 2;
            return Math.round(Math.min(15, Math.max(floor, words(s.voice_line) / pace + 0.4)) * 10) / 10;
        },
        totalSeconds() { return Math.round(this.scenes.reduce((t, s) => t + this.sceneSeconds(s), 0)); },
        totalWords() { return this.scenes.reduce((t, s) => t + words(s.voice_line), 0); },
        inRange() {
            const s = this.totalSeconds(), w = this.totalWords();
            return s >= {{ (int) config('social.reels.min_seconds') }} && s <= {{ (int) config('social.reels.max_seconds') }}
                && w >= {{ (int) config('social.reels.min_words') }} && w <= {{ (int) config('social.reels.max_words') }};
        },
    };
}
</script>
@endpush
