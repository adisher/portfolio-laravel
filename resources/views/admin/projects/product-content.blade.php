@extends('layouts.admin')
@section('title', 'Product Content - ' . $project->title)

@section('content')
<div class="p-6" x-data="productContentForm()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.projects.edit', $project) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Product Content</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 ml-8">{{ $project->title }}</p>
        </div>
        <a href="{{ route('products.show', $project->slug) }}" target="_blank"
           class="btn-secondary text-sm">
            View Product
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('admin.projects.update-product-content', $project) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ========== FEATURES ========== --}}
        <div class="admin-card p-6 mb-6">
            <div class="flex items-center justify-between mb-4 cursor-pointer" @click="sections.features = !sections.features">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
                    Features
                    <span class="text-xs font-normal text-gray-400" x-text="'(' + features.length + ')'"></span>
                </h2>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="sections.features ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </div>

            <div x-show="sections.features" x-collapse>
                <template x-for="(feature, index) in features" :key="index">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3 relative">
                        <button type="button" @click="features.splice(index, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Icon</label>
                                <select :name="'features[' + index + '][icon]'" x-model="feature.icon"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    <template x-for="ic in iconOptions" :key="ic">
                                        <option :value="ic" x-text="ic" :selected="feature.icon === ic"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Title</label>
                                <input type="text" :name="'features[' + index + '][title]'" x-model="feature.title"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Feature title">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                            <textarea :name="'features[' + index + '][description]'" x-model="feature.description" rows="2"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Brief description"></textarea>
                        </div>
                    </div>
                </template>

                <button type="button" @click="features.push({icon: 'star', title: '', description: ''})"
                        class="btn-secondary text-sm">
                    + Add Feature
                </button>
            </div>
        </div>

        {{-- ========== HOW IT WORKS ========== --}}
        <div class="admin-card p-6 mb-6">
            <div class="flex items-center justify-between mb-4 cursor-pointer" @click="sections.howItWorks = !sections.howItWorks">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                    How It Works
                    <span class="text-xs font-normal text-gray-400" x-text="'(' + howItWorks.length + ')'"></span>
                </h2>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="sections.howItWorks ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </div>

            <div x-show="sections.howItWorks" x-collapse>
                <template x-for="(step, index) in howItWorks" :key="index">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3 relative">
                        <button type="button" @click="howItWorks.splice(index, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center text-sm font-bold" x-text="index + 1"></span>
                            <input type="text" :name="'how_it_works[' + index + '][title]'" x-model="step.title"
                                   class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Step title">
                        </div>
                        <textarea :name="'how_it_works[' + index + '][description]'" x-model="step.description" rows="2"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Step description"></textarea>
                    </div>
                </template>

                <button type="button" @click="howItWorks.push({title: '', description: ''})"
                        class="btn-secondary text-sm">
                    + Add Step
                </button>
            </div>
        </div>

        {{-- ========== PRICING ========== --}}
        <div class="admin-card p-6 mb-6">
            <div class="flex items-center justify-between mb-4 cursor-pointer" @click="sections.pricing = !sections.pricing">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                    Pricing Tiers
                    <span class="text-xs font-normal text-gray-400" x-text="'(' + pricing.length + ')'"></span>
                </h2>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="sections.pricing ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </div>

            <div x-show="sections.pricing" x-collapse>
                <template x-for="(tier, tIndex) in pricing" :key="tIndex">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-4 relative">
                        <button type="button" @click="pricing.splice(tIndex, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tier Name</label>
                                <input type="text" :name="'pricing[' + tIndex + '][name]'" x-model="tier.name"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="e.g. Personal">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Price ($)</label>
                                <input type="text" :name="'pricing[' + tIndex + '][price]'" x-model="tier.price"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="29">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">CTA URL (optional)</label>
                                <input type="text" :name="'pricing[' + tIndex + '][cta_url]'" x-model="tier.cta_url"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="https://...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                            <input type="text" :name="'pricing[' + tIndex + '][description]'" x-model="tier.description"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Short tier description">
                        </div>

                        <div class="flex items-center mb-3">
                            <input type="checkbox" :id="'highlighted_' + tIndex" :name="'pricing[' + tIndex + '][highlighted]'" x-model="tier.highlighted" value="1"
                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            <label :for="'highlighted_' + tIndex" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Highlighted (Popular) tier</label>
                        </div>

                        {{-- Features list --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Features</label>
                            <template x-for="(feat, fIndex) in tier.features" :key="fIndex">
                                <div class="flex items-center gap-2 mb-2">
                                    <input type="text" :name="'pricing[' + tIndex + '][features][' + fIndex + ']'" x-model="tier.features[fIndex]"
                                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Feature item">
                                    <button type="button" @click="tier.features.splice(fIndex, 1)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="tier.features.push('')" class="text-xs text-blue-500 hover:text-blue-600 font-medium">
                                + Add Feature
                            </button>
                        </div>
                    </div>
                </template>

                <button type="button" @click="pricing.push({name: '', price: '', description: '', cta_url: '', highlighted: false, features: ['']})"
                        class="btn-secondary text-sm">
                    + Add Pricing Tier
                </button>
            </div>
        </div>

        {{-- ========== FAQ ========== --}}
        <div class="admin-card p-6 mb-6">
            <div class="flex items-center justify-between mb-4 cursor-pointer" @click="sections.faq = !sections.faq">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>
                    FAQ
                    <span class="text-xs font-normal text-gray-400" x-text="'(' + faq.length + ')'"></span>
                </h2>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="sections.faq ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </div>

            <div x-show="sections.faq" x-collapse>
                <template x-for="(item, index) in faq" :key="index">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-3 relative">
                        <button type="button" @click="faq.splice(index, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Question</label>
                            <input type="text" :name="'faq[' + index + '][question]'" x-model="item.question"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Frequently asked question">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Answer</label>
                            <textarea :name="'faq[' + index + '][answer]'" x-model="item.answer" rows="3"
                                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="Answer to the question"></textarea>
                        </div>
                    </div>
                </template>

                <button type="button" @click="faq.push({question: '', answer: ''})"
                        class="btn-secondary text-sm">
                    + Add FAQ Item
                </button>
            </div>
        </div>

        {{-- ========== CTA ========== --}}
        <div class="admin-card p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                Product CTA
            </h2>

            {{-- CTA Type --}}
            <div class="mb-5 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">CTA Type</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="cta_type" value="purchase" x-model="ctaType" class="text-blue-600">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Direct Purchase</span>
                            <p class="text-xs text-gray-400">Pricing cards go to Safepay checkout</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="cta_type" value="demo" x-model="ctaType" class="text-blue-600">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Demo Request</span>
                            <p class="text-xs text-gray-400">Pricing cards open the scheduling modal</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        <span x-show="ctaType === 'purchase'">CTA URL (Payment Link)</span>
                        <span x-show="ctaType === 'demo'">CTA URL (e.g. /contact)</span>
                    </label>
                    <input type="text" name="cta_url" x-model="ctaUrl"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                           :placeholder="ctaType === 'demo' ? '/contact' : 'https://your-payment-link.com'">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Button Label</label>
                    <input type="text" name="cta_label" x-model="ctaLabel"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                           :placeholder="ctaType === 'demo' ? 'Request a Demo' : 'Buy Now'">
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                Save Product Content
            </button>
        </div>
    </form>
</div>

<script>
function productContentForm() {
    const data = @json($project->product_data ?? []);
    return {
        sections: {
            features: true,
            howItWorks: true,
            pricing: true,
            faq: true,
        },
        iconOptions: ['star', 'shield', 'palette', 'chart', 'code', 'globe', 'lightning', 'lock', 'link', 'device', 'cloud', 'settings', 'rocket', 'download', 'users', 'refresh', 'check'],
        features: data.features || [],
        howItWorks: data.how_it_works || [],
        pricing: (data.pricing || []).map(t => ({...t, features: t.features || ['']})),
        faq: data.faq || [],
        ctaType: data.cta_type || 'purchase',
        ctaUrl: data.cta_url || '',
        ctaLabel: data.cta_label || 'Buy Now',
    };
}
</script>
@endsection
