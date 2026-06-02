@php
    $bannerEnabled = \App\Models\FeatureFlag::enabled('banner.global');
    $bannerMeta    = \App\Models\FeatureFlag::meta('banner.global');
    $bannerMessage = $bannerMeta['message'] ?? '';
    $bannerLink    = $bannerMeta['link'] ?? '';
    $bannerText    = $bannerMeta['link_text'] ?? 'Learn more';
    $bannerColor   = $bannerMeta['color'] ?? 'teal';

    $colorClasses = [
        'teal'   => 'bg-teal text-midnight',
        'sunset' => 'bg-sunset text-white',
        'ocean'  => 'bg-ocean text-white',
    ];
    $cls = $colorClasses[$bannerColor] ?? $colorClasses['teal'];
@endphp

@if($bannerEnabled && $bannerMessage)
<div x-data="{ show: true, key: '{{ md5($bannerMessage) }}' }"
     x-init="show = localStorage.getItem('banner_dismissed') !== key"
     x-show="show"
     x-cloak
     class="{{ $cls }} relative text-sm font-medium text-center py-2.5 px-12">
    <span>{{ $bannerMessage }}</span>
    @if($bannerLink)
    <a href="{{ $bannerLink }}" class="underline underline-offset-2 ml-2 opacity-80 hover:opacity-100 font-semibold">{{ $bannerText }} &rarr;</a>
    @endif
    <button @click="show = false; localStorage.setItem('banner_dismissed', key)"
            class="absolute right-4 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-100 transition-opacity"
            aria-label="Dismiss">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
@endif
