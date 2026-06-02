@extends('layouts.admin')

@section('title', 'Visibility Control')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Visibility Control</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toggle pages, sections, features, and nav items on or off. Changes take effect immediately.</p>
    </div>
</div>

@php
$groupMeta = [
    'page'    => ['label' => 'Pages',                'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z', 'desc' => 'Control access to entire routes'],
    'section' => ['label' => 'Sections',             'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z', 'desc' => 'Show or hide sections within pages'],
    'feature' => ['label' => 'Features',             'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'desc' => 'Enable or disable functional features'],
    'nav'     => ['label' => 'Navigation Links',     'icon' => 'M3.75 9h16.5m-16.5 6.75h16.5', 'desc' => 'Control which links appear in the navbar'],
    'banner'  => ['label' => 'Announcement Banner',  'icon' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46', 'desc' => 'Global banner shown above the navbar'],
];
@endphp

<div class="space-y-6">
    @foreach($grouped as $group => $flags)
    @if($flags->isNotEmpty())
    @php $meta = $groupMeta[$group]; @endphp
    <div class="admin-card overflow-hidden">
        {{-- Group header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $meta['label'] }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $meta['desc'] }}</p>
            </div>
        </div>

        {{-- Flags list --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
            @foreach($flags as $flag)
            <div class="flex items-center justify-between px-6 py-4 gap-4"
                 x-data="{ enabled: {{ $flag->is_enabled ? 'true' : 'false' }}, saving: false }">

                {{-- Info --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $flag->label }}</span>
                        <span x-show="!enabled" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">Hidden</span>
                        <span x-show="enabled" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">Visible</span>
                    </div>
                    @if($flag->description)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $flag->description }}</p>
                    @endif
                    <p class="text-xs text-gray-300 dark:text-gray-600 mt-0.5 font-mono">{{ $flag->key }}</p>
                </div>

                {{-- Toggle --}}
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox"
                           class="sr-only peer"
                           :checked="enabled"
                           :disabled="saving"
                           @change="
                               saving = true;
                               fetch('{{ route('admin.visibility.toggle', $flag) }}', {
                                   method: 'PATCH',
                                   headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
                               })
                               .then(r => r.json())
                               .then(d => { enabled = d.is_enabled; saving = false; })
                               .catch(() => { saving = false; });
                           ">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer
                                peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                peer-checked:bg-blue-600 dark:bg-gray-700
                                peer-disabled:opacity-50 peer-disabled:cursor-not-allowed transition-opacity"></div>
                </label>
            </div>

            {{-- Banner meta fields (only for banner group) --}}
            @if($group === 'banner' && $flag->key === 'banner.global')
            <div x-data="bannerMeta()" x-show="enabled" x-cloak
                 class="px-6 pb-5 bg-gray-50/50 dark:bg-gray-800/30">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Banner Content</p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Message <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.message" @blur="save()"
                               placeholder="e.g. Launching ComplianceCore v2 — stay tuned!"
                               class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Link URL (optional)</label>
                            <input type="url" x-model="form.link" @blur="save()"
                                   placeholder="https://..."
                                   class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Link Text</label>
                            <input type="text" x-model="form.link_text" @blur="save()"
                                   placeholder="Learn more"
                                   class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Colour</label>
                        <div class="flex gap-3">
                            @foreach(['teal' => '#41EAD4', 'sunset' => '#FF6B35', 'ocean' => '#1B3A4B'] as $c => $hex)
                            <label class="cursor-pointer">
                                <input type="radio" x-model="form.color" value="{{ $c }}" @change="save()" class="sr-only peer">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-all
                                             peer-checked:ring-2 peer-checked:ring-blue-500 peer-checked:border-blue-500
                                             border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">
                                    <span class="w-3 h-3 rounded-full" style="background:{{ $hex }}"></span>
                                    {{ ucfirst($c) }}
                                </span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3" x-text="status"></p>
            </div>

            <script>
            function bannerMeta() {
                return {
                    form: {
                        message:   '{{ addslashes($flag->metadata['message'] ?? '') }}',
                        link:      '{{ addslashes($flag->metadata['link'] ?? '') }}',
                        link_text: '{{ addslashes($flag->metadata['link_text'] ?? '') }}',
                        color:     '{{ $flag->metadata['color'] ?? 'teal' }}',
                    },
                    status: '',
                    save() {
                        this.status = 'Saving\u2026';
                        fetch('{{ route('admin.visibility.meta', $flag) }}', {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.form),
                        })
                        .then(r => r.json())
                        .then(() => { this.status = 'Saved \u2713'; setTimeout(() => this.status = '', 2000); })
                        .catch(() => { this.status = 'Error saving.'; });
                    }
                }
            }
            </script>
            @endif

            @endforeach
        </div>
    </div>
    @endif
    @endforeach
</div>
@endsection
