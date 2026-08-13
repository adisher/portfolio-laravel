{{-- Shared form for connecting/editing a social account. Expects $account (nullable) and $drivers. --}}
@php
    $account = $account ?? null;
    // Platforms whose API terms forbid automated posting (scheduler skips them).
    $noAutoPlatforms = collect($drivers)
        ->reject(fn ($driver) => $driver->allowsAutomatedPosting())
        ->keys()->values()->all();
@endphp

<style>[x-cloak]{display:none !important;}</style>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="{ platform: '{{ old('platform', $account->platform ?? 'facebook') }}' }">
    <div class="lg:col-span-2 space-y-6">
        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account</h2>
            <div class="space-y-4">
                <div>
                    <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform *</label>
                    <select id="platform" name="platform" required x-model="platform"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($drivers as $key => $driver)
                            <option value="{{ $key }}">{{ $driver->label() }}</option>
                        @endforeach
                    </select>
                    @error('platform')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Label *</label>
                    <input type="text" id="name" name="name" required value="{{ old('name', $account->name ?? '') }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="e.g. Adil Sher (Facebook Page)">
                    @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Credential fields are declared per driver and shown for the selected platform. --}}
                @foreach($drivers as $pkey => $driver)
                <div x-show="platform === '{{ $pkey }}'" x-cloak class="space-y-4">
                    @foreach($driver->credentialFields() as $ckey => $meta)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $meta['label'] }}@if(!empty($meta['required']) && !$account) *@endif
                        </label>
                        <input type="{{ $meta['type'] }}" autocomplete="off" name="cred[{{ $pkey }}][{{ $ckey }}]"
                            @if($meta['type'] === 'password')
                                placeholder="{{ $account ? 'Leave blank to keep the current value' : '' }}"
                            @else
                                value="{{ old('cred.'.$pkey.'.'.$ckey, ($account && $account->platform === $pkey) ? $account->credential($ckey) : '') }}"
                            @endif
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @if(!empty($meta['help']))
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $meta['help'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endforeach
                @error('credentials')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Caption template</h2>
            <textarea id="caption_template" name="caption_template" rows="6"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm"
                placeholder="{title}&#10;&#10;{excerpt}&#10;&#10;Read more: {url}&#10;&#10;{hashtags}">{{ old('caption_template', $account->caption_template ?? '') }}</textarea>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Tokens: <code>{title}</code> <code>{excerpt}</code> <code>{url}</code> <code>{category}</code> <code>{hashtags}</code>.
                Leave blank to use the default layout.
            </p>
            @error('caption_template')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="space-y-6">
        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Publishing</h2>
            <div class="space-y-4">
                <div>
                    <label for="min_human_views" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Auto-post gate (min human views) *
                    </label>
                    <input type="number" id="min_human_views" name="min_human_views" min="0" max="100000" required
                        value="{{ old('min_human_views', $account->min_human_views ?? 5) }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Only articles with at least this many human views are auto-posted.
                    </p>
                    @error('min_human_views')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}
                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active (connected &amp; usable)</span>
                </label>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="auto_post_enabled" value="1"
                        {{ old('auto_post_enabled', $account->auto_post_enabled ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Enable auto-posting</span>
                </label>
                <p class="text-xs text-gray-400">
                    Auto-posting runs via the <code>social:publish</code> command and is not on a schedule yet;
                    this toggle decides whether this account takes part once it is.
                </p>
                <p x-show="@js($noAutoPlatforms).includes(platform)" x-cloak
                    class="text-xs text-amber-600 dark:text-amber-400">
                    This platform prohibits automated posting under its API terms, so the scheduler always skips it
                    even if this toggle is on. Use <strong>Post now</strong> to publish manually.
                </p>
            </div>
        </div>

        <div class="admin-card p-6">
            <button type="submit" class="btn-primary w-full justify-center">
                {{ $account ? 'Save changes' : 'Connect account' }}
            </button>
            <a href="{{ route('admin.social.index') }}" class="btn-secondary w-full justify-center mt-3">Cancel</a>
        </div>
    </div>
</div>
