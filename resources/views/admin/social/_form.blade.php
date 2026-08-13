{{-- Shared form for connecting/editing a social account. Expects $account (nullable) and $drivers. --}}
@php $account = $account ?? null; @endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="admin-card p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account</h2>
            <div class="space-y-4">
                <div>
                    <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform *</label>
                    <select id="platform" name="platform" required
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($drivers as $key => $driver)
                            <option value="{{ $key }}" {{ old('platform', $account->platform ?? 'facebook') === $key ? 'selected' : '' }}>
                                {{ $driver->label() }}
                            </option>
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

                <div>
                    <label for="page_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page ID</label>
                    <input type="text" id="page_id" name="page_id" value="{{ old('page_id', $account?->credential('page_id')) }}"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="165445894154299">
                    @error('page_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="access_token" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Page Access Token {{ $account ? '' : '*' }}
                    </label>
                    <input type="password" id="access_token" name="access_token" autocomplete="off"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="{{ $account ? 'Leave blank to keep the current token' : 'EAAG...' }}">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Stored encrypted. A Facebook <strong>Page</strong> token (not a User token) with
                        <code>pages_manage_posts</code> + <code>pages_read_engagement</code>.
                    </p>
                    @error('access_token')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
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
