@extends('layouts.admin')

@section('title', 'About Page - Admin Panel')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">About Page</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Manage all content shown on the public About page</p>
    </div>
    <a href="{{ route('about') }}" target="_blank"
       class="btn-secondary text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
        Preview
    </a>
</div>

@if(session('success'))
<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400">
    {{ session('success') }}
</div>
@endif

<form action="{{ route('admin.about.update') }}" method="POST" x-data="aboutEditor()">
    @csrf
    @method('PUT')

    {{-- ── HERO ── --}}
    <div class="admin-card p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            Hero Section
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio Text</label>
                <textarea name="hero_bio" rows="3"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ $data['hero_bio']->value ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resume URL</label>
                <input type="url" name="resume_url"
                    value="{{ $data['resume_url']->value ?? '' }}"
                    placeholder="https://..."
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
        </div>
    </div>

    {{-- ── STATS ── --}}
    <div class="admin-card p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            Stats
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach(($data['stats']->display_value ?? []) as $i => $stat)
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-2">
                <input type="text" name="stats[{{ $i }}][value]"
                    value="{{ $stat['value'] ?? '' }}"
                    placeholder="50+"
                    class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-bold">
                <input type="text" name="stats[{{ $i }}][label]"
                    value="{{ $stat['label'] ?? '' }}"
                    placeholder="Projects Completed"
                    class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <select name="stats[{{ $i }}][color]"
                    class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="teal" {{ ($stat['color'] ?? '') === 'teal' ? 'selected' : '' }}>Teal</option>
                    <option value="sunset" {{ ($stat['color'] ?? '') === 'sunset' ? 'selected' : '' }}>Sunset</option>
                </select>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── SKILLS ── --}}
    <div class="admin-card p-6 mb-6" x-data="repeater({{ json_encode($data['skills']->display_value ?? []) }})">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Skills</h2>
            <button type="button" @click="add({title:'',description:'',color:'teal',tags:''})"
                class="btn-secondary text-xs py-1 px-3">+ Add Skill</button>
        </div>
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 relative">
                    <button type="button" @click="remove(index)"
                        class="absolute top-3 right-3 text-red-400 hover:text-red-600 text-xs">Remove</button>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Title</label>
                            <input type="text" :name="`skills[${index}][title]`" x-model="item.title"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                            <input type="text" :name="`skills[${index}][description]`" x-model="item.description"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Accent Color</label>
                            <select :name="`skills[${index}][color]`" x-model="item.color"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="teal">Teal</option>
                                <option value="sunset">Sunset</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tags
                                <span class="font-normal text-gray-400">(comma-separated)</span>
                            </label>
                            <input type="text" :name="`skills[${index}][tags]`"
                                :value="Array.isArray(item.tags) ? item.tags.join(', ') : item.tags"
                                @input="item.tags = $event.target.value"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                placeholder="Laravel, PHP, MySQL">
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── EXPERIENCE ── --}}
    <div class="admin-card p-6 mb-6" x-data="repeater({{ json_encode($data['experience']->display_value ?? []) }})">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Experience Timeline</h2>
            <button type="button" @click="add({period:'',title:'',company:'',description:'',color:'teal',tags:''})"
                class="btn-secondary text-xs py-1 px-3">+ Add Entry</button>
        </div>
        <div class="space-y-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 relative">
                    <button type="button" @click="remove(index)"
                        class="absolute top-3 right-3 text-red-400 hover:text-red-600 text-xs">Remove</button>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Period</label>
                            <input type="text" :name="`experience[${index}][period]`" x-model="item.period"
                                placeholder="2022 - Present"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Job Title</label>
                            <input type="text" :name="`experience[${index}][title]`" x-model="item.title"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Company</label>
                            <input type="text" :name="`experience[${index}][company]`" x-model="item.company"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Accent Color</label>
                            <select :name="`experience[${index}][color]`" x-model="item.color"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="teal">Teal</option>
                                <option value="sunset">Sunset</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                            <textarea :name="`experience[${index}][description]`" x-model="item.description" rows="2"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tags
                                <span class="font-normal text-gray-400">(comma-separated)</span>
                            </label>
                            <input type="text" :name="`experience[${index}][tags]`"
                                :value="Array.isArray(item.tags) ? item.tags.join(', ') : item.tags"
                                @input="item.tags = $event.target.value"
                                class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                placeholder="Laravel, MySQL, HLS">
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── VALUES ── --}}
    <div class="admin-card p-6 mb-6" x-data="repeater({{ json_encode($data['values']->display_value ?? []) }})">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Values / Approach</h2>
            <button type="button" @click="add({title:'',description:'',color:'teal'})"
                class="btn-secondary text-xs py-1 px-3">+ Add Value</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 relative space-y-3">
                    <button type="button" @click="remove(index)"
                        class="absolute top-3 right-3 text-red-400 hover:text-red-600 text-xs">Remove</button>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Title</label>
                        <input type="text" :name="`values[${index}][title]`" x-model="item.title"
                            class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                        <input type="text" :name="`values[${index}][description]`" x-model="item.description"
                            class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Accent Color</label>
                        <select :name="`values[${index}][color]`" x-model="item.color"
                            class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="teal">Teal</option>
                            <option value="sunset">Sunset</option>
                        </select>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── PERSONAL / BEYOND CODING ── --}}
    <div class="admin-card p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
            Beyond Coding Section
        </h2>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio Text</label>
                <textarea name="personal_bio" rows="3"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">{{ $data['personal_bio']->value ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Interest Tags
                    <span class="font-normal text-gray-400">(pipe-separated, e.g. Music|Travel)</span>
                </label>
                <input type="text" name="interests"
                    value="{{ $data['interests']->value ?? '' }}"
                    placeholder="Continuous Learning|Open Source|Music"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('about') }}" target="_blank" class="btn-secondary">Preview Page</a>
        <button type="submit" class="btn-primary">Save Changes</button>
    </div>
</form>

@push('scripts')
<script>
function repeater(initial) {
    return {
        items: initial.map(item => ({
            ...item,
            tags: Array.isArray(item.tags) ? item.tags.join(', ') : (item.tags || '')
        })),
        add(template) { this.items.push({...template}); },
        remove(i)     { this.items.splice(i, 1); },
    };
}

function aboutEditor() {
    return {};
}
</script>
@endpush
@endsection
