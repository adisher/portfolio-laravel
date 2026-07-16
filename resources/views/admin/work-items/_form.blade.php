@php $wi = $workItem ?? null; @endphp

@if(isset($errors) && $errors->any())
<div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-400 text-sm">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

{{-- ── Basics ── --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Basics</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $wi->name ?? '') }}" required
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
            <select name="type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                @foreach(['product','service','project','skill'] as $t)
                <option value="{{ $t }}" {{ old('type', $wi->type ?? 'product') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Linked Project <span class="text-gray-400">(optional)</span></label>
            <select name="project_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">None</option>
                @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ (string) old('project_id', $wi->project_id ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Blog Category <span class="text-gray-400">(where generated articles file)</span></label>
            <select name="blog_category_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">None</option>
                @foreach(\App\Models\Category::forBlog()->orderBy('name')->get() as $bc)
                <option value="{{ $bc->id }}" {{ (string) old('blog_category_id', $wi->blog_category_id ?? '') === (string) $bc->id ? 'selected' : '' }}>{{ $bc->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Live URL <span class="text-gray-400">(optional)</span></label>
            <input type="url" name="url" value="{{ old('url', $wi->url ?? '') }}" placeholder="https://..."
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tagline <span class="text-gray-400">(one-line positioning)</span></label>
            <input type="text" name="tagline" value="{{ old('tagline', $wi->tagline ?? '') }}"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tech Stack</label>
            <input type="text" name="tech_stack" value="{{ old('tech_stack', $wi->tech_stack ?? '') }}" placeholder="Laravel, FastAPI, ..."
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        </div>
        <div class="flex items-end gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="active" value="1" {{ old('active', $wi->active ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-300 dark:border-gray-600">
                Active
            </label>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $wi->sort_order ?? 0) }}"
                    class="w-20 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
        </div>
    </div>
</div>

{{-- ── Positioning ── --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Positioning</h2>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Audience <span class="text-gray-400">(who it's for)</span></label>
            <textarea name="target_audience" rows="2" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('target_audience', $wi->target_audience ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">How It Helps <span class="text-gray-400">(the solution / your approach)</span></label>
            <textarea name="how_it_helps" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('how_it_helps', $wi->how_it_helps ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Call To Action <span class="text-gray-400">(what you want the reader to do)</span></label>
            <textarea name="call_to_action" rows="2" placeholder="e.g. Get in touch to see it in action..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('call_to_action', $wi->call_to_action ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- ── List fields ── --}}
@php
    $lists = [
        'pain_points'     => ['Pain Points', 'The problems this addresses'],
        'objections'      => ['Objections', 'Hesitations a prospect has (and you can preempt)'],
        'key_outcomes'    => ['Key Outcomes / Proof', 'Results, metrics, evidence'],
        'proof_links'     => ['Proof Links', 'Demo video, case study, testimonials (URLs)'],
        'differentiators' => ['Differentiators', 'Why you / why this'],
        'target_keywords' => ['Target Keywords', 'Search terms people use for the pain'],
        'article_angles'  => ['Article Angles', 'Content hooks / ideas'],
        'hooks'           => ['Opening Hooks', 'Real events to open an article with (add a source). Order by priority: best real event first. The generator opens with the one you pick, or writes a concrete unnamed scene if you pick none.'],
        'voices'          => ['User Voices', 'Real user sentiment as social proof: a short quote + who said it + source (e.g. "quote" — r/musicmarketing, url). Curated only, never invented. The generator weaves 1-2 in and cites them.'],
        'screenshots'     => ['Screenshot Library', 'Fixed set of product screenshots as "slug — description" (e.g. analytics — the page views and link clicks panel). The generator only uses [[screenshot: slug]] markers from this list. See docs/screenshot-library.md.'],
    ];
@endphp
@foreach($lists as $field => [$label, $hint])
<div class="admin-card p-6 mb-6" x-data="stringList(@js(old($field, $wi->{$field} ?? [])))">
    <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $label }}</h2>
            <p class="text-xs text-gray-400">{{ $hint }}</p>
        </div>
        <button type="button" @click="add()" class="btn-secondary text-xs py-1 px-3">+ Add</button>
    </div>
    <div class="space-y-2">
        <template x-for="(item, i) in items" :key="i">
            <div class="flex gap-2">
                <input type="text" name="{{ $field }}[]" x-model="item.value"
                    class="flex-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 text-xs px-2">Remove</button>
            </div>
        </template>
        <p x-show="items.length === 0" class="text-sm text-gray-400">None added yet.</p>
    </div>
</div>
@endforeach

{{-- ── Stories ── --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1 pb-2 border-b border-gray-200 dark:border-gray-700">Stories &amp; Real Details</h2>
    <p class="text-xs text-gray-400 mt-2 mb-3">Real anecdotes, origin story, opinions, personal details. This is the authentic raw material the AI weaves into generated articles, so they do not read generic.</p>
    <textarea name="stories" rows="6" placeholder="e.g. I built this from a take-home interview task. The role went to someone else, but the problem stuck with me and I kept refining it..."
        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('stories', $wi->stories ?? '') }}</textarea>
</div>

{{-- ── Notes ── --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Notes</h2>
    <textarea name="notes" rows="4" placeholder="Anything else worth remembering about this work item..."
        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ old('notes', $wi->notes ?? '') }}</textarea>
</div>

@push('scripts')
<script>
function stringList(initial) {
    return {
        items: (Array.isArray(initial) ? initial : []).map(v => ({ value: v })),
        add() { this.items.push({ value: '' }); },
        remove(i) { this.items.splice(i, 1); },
    };
}
</script>
@endpush
