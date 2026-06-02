{{-- Basic Info --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Basic Info</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
            <input type="text" name="title" value="{{ old('title', $productPage->title ?? '') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Page Type</label>
            <select name="type" x-model="type"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="setup">Setup (Post-Purchase)</option>
                <option value="deploy">Deploy (Step-by-Step Guide)</option>
                <option value="custom">Custom (Markdown)</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $productPage->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1"
                       {{ old('is_published', $productPage->is_published ?? true) ? 'checked' : '' }}
                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">Published</span>
            </label>
        </div>
    </div>
</div>

{{-- ========== SETUP TYPE FIELDS ========== --}}
<div x-show="type === 'setup'" x-transition>
    <div class="admin-card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Setup Page Content</h2>

        <div class="grid grid-cols-1 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heading</label>
                <input type="text" name="content_heading"
                       value="{{ old('content_heading', ($productPage->content ?? [])['heading'] ?? 'Congratulations!') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Congratulations!">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                <textarea name="content_message" rows="2"
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                          placeholder="Thank you for your purchase...">{{ old('content_message', ($productPage->content ?? [])['message'] ?? '') }}</textarea>
            </div>
        </div>

        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Option Cards</h3>

        <template x-for="(option, index) in options" :key="index">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3 relative">
                <button type="button" @click="options.splice(index, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Icon</label>
                        <select :name="'options[' + index + '][icon]'" x-model="option.icon"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <template x-for="ic in iconOptions" :key="ic">
                                <option :value="ic" x-text="ic"></option>
                            </template>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Title</label>
                        <input type="text" :name="'options[' + index + '][title]'" x-model="option.title"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Option title">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                    <textarea :name="'options[' + index + '][description]'" x-model="option.description" rows="2"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Option description"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Button Label</label>
                        <input type="text" :name="'options[' + index + '][button_label]'" x-model="option.button_label"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Get Started">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Button URL</label>
                        <input type="text" :name="'options[' + index + '][button_url]'" x-model="option.button_url"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="https://...">
                    </div>
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" :name="'options[' + index + '][recommended]'" x-model="option.recommended" value="1"
                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs text-gray-600 dark:text-gray-400">Recommended option</span>
                </label>
            </div>
        </template>

        <button type="button" @click="options.push({title: '', description: '', icon: 'star', button_label: '', button_url: '', recommended: false})"
                class="btn-secondary text-sm">
            + Add Option
        </button>
    </div>
</div>

{{-- ========== DEPLOY TYPE FIELDS ========== --}}
<div x-show="type === 'deploy'" x-transition>
    <div class="admin-card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Deployment Guide Content</h2>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heading</label>
            <input type="text" name="content_heading"
                   value="{{ old('content_heading', ($productPage->content ?? [])['heading'] ?? 'Deployment Guide') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Deployment Guide">
        </div>

        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Steps</h3>

        <template x-for="(step, index) in steps" :key="index">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-4 relative">
                <button type="button" @click="steps.splice(index, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <div class="flex items-center gap-3 mb-3">
                    <span class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center text-sm font-bold" x-text="index + 1"></span>
                    <input type="text" :name="'steps[' + index + '][title]'" x-model="step.title"
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Step title">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                    <textarea :name="'steps[' + index + '][description]'" x-model="step.description" rows="2"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Step description"></textarea>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Checklist Items (one per line)</label>
                    <textarea :name="'steps[' + index + '][items]'" x-model="step.items" rows="3"
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono" placeholder="Item 1&#10;Item 2&#10;Item 3"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Button Label</label>
                        <input type="text" :name="'steps[' + index + '][button_label]'" x-model="step.button_label"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Open Dashboard">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Button URL</label>
                        <input type="text" :name="'steps[' + index + '][button_url]'" x-model="step.button_url"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="https://...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Guidance (optional italic tip)</label>
                    <input type="text" :name="'steps[' + index + '][guidance]'" x-model="step.guidance"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="e.g. Click the button to continue...">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Note (optional callout box)</label>
                    <input type="text" :name="'steps[' + index + '][note]'" x-model="step.note"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="e.g. This step may take a few minutes...">
                </div>
            </div>
        </template>

        <button type="button" @click="steps.push({title: '', description: '', items: '', button_label: '', button_url: '', guidance: '', note: ''})"
                class="btn-secondary text-sm mb-6">
            + Add Step
        </button>

        <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 mt-6">Support Section</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Heading</label>
                <input type="text" name="support_heading"
                       value="{{ old('support_heading', ($productPage->content ?? [])['support_heading'] ?? 'Need Help?') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Message</label>
                <input type="text" name="support_message"
                       value="{{ old('support_message', ($productPage->content ?? [])['support_message'] ?? 'If you run into any issues, feel free to reach out.') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Support URL</label>
                <input type="text" name="support_url"
                       value="{{ old('support_url', ($productPage->content ?? [])['support_url'] ?? '') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="https://... or leave empty for contact page">
            </div>
        </div>
    </div>
</div>

{{-- ========== CUSTOM TYPE FIELDS ========== --}}
<div x-show="type === 'custom'" x-transition>
    <div class="admin-card p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Custom Content (Markdown)</h2>
        <textarea name="content_markdown" rows="20"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm"
                  placeholder="Write your content in Markdown format...">{{ old('content_markdown', ($productPage->content ?? [])['markdown'] ?? '') }}</textarea>
        <p class="text-xs text-gray-400 mt-2">Supports Markdown formatting: headings, bold, italic, lists, code blocks, links, etc.</p>
    </div>
</div>
