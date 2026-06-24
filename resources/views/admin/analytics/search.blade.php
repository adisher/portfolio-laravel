@extends('layouts.admin')

@section('title', 'Search Performance (SEO) - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Search Performance</h1>
            <p class="text-gray-600 dark:text-gray-400">Google Search Console: how people find you in search</p>
        </div>
        @if($configured ?? false)
        <select id="period-select" onchange="updatePeriod()"
            class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            <option value="7" {{ $days==7 ? 'selected' : '' }}>Last 7 days</option>
            <option value="28" {{ $days==28 ? 'selected' : '' }}>Last 28 days</option>
            <option value="90" {{ $days==90 ? 'selected' : '' }}>Last 90 days</option>
        </select>
        @endif
    </div>
</div>

@if(!($configured ?? false))
{{-- Not configured --}}
<div class="admin-card p-8 text-center max-w-2xl mx-auto">
    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Search Console not connected yet</h3>
    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
        To show search performance here, connect a Google service account with read access to your Search Console property.
    </p>
    <ol class="text-left text-sm text-gray-600 dark:text-gray-400 space-y-1 max-w-md mx-auto list-decimal list-inside">
        <li>Create a Google Cloud service account and enable the Search Console API.</li>
        <li>Add the service account email as a user on your GSC property.</li>
        <li>Upload its JSON key to <code class="text-teal">storage/app/google/gsc.json</code>.</li>
        <li>Set <code class="text-teal">GSC_SITE_URL</code> and <code class="text-teal">GSC_CREDENTIALS_PATH</code> in <code>.env</code>, then <code>php artisan config:cache</code>.</li>
    </ol>
</div>
@else

@php
    $cur = $summary['current'];
    $delta = $summary['delta'];
    $fmtPct = fn($v) => ($v >= 0 ? '+' : '') . $v . '%';
@endphp

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ number_format($cur['clicks']) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Clicks</div>
        <div class="text-xs mt-1 {{ $delta['clicks'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $fmtPct($delta['clicks']) }} vs prior</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-indigo-600">{{ number_format($cur['impressions']) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Impressions</div>
        <div class="text-xs mt-1 {{ $delta['impressions'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $fmtPct($delta['impressions']) }} vs prior</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-teal-600">{{ number_format($cur['ctr'] * 100, 1) }}%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Avg CTR</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-purple-600">{{ number_format($cur['position'], 1) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Avg Position</div>
        @if($delta['position'] != 0)
        {{-- Lower position is better, so a negative change is an improvement --}}
        <div class="text-xs mt-1 {{ $delta['position'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $delta['position'] <= 0 ? '▲ improved ' : '▼ dropped ' }}{{ abs($delta['position']) }}
        </div>
        @endif
    </div>
</div>

@if($cur['impressions'] == 0)
<div class="admin-card p-4 mb-8 text-sm text-gray-600 dark:text-gray-400">
    No search data for this period yet. Your property is newly verified and Google data lags about 2 days, so numbers will appear and grow over the coming weeks.
</div>
@endif

{{-- Clicks/Impressions trend --}}
<div class="admin-card p-6 mb-8">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Clicks &amp; Impressions Trend</h3>
    <canvas id="gscTrend" height="80"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Top queries --}}
    <div class="admin-card p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Top Search Queries</h3>
        <div class="overflow-x-auto">
            <table class="admin-table w-full text-sm">
                <thead><tr><th class="text-left">Query</th><th class="text-right">Clicks</th><th class="text-right">Impr.</th><th class="text-right">Pos.</th></tr></thead>
                <tbody>
                    @forelse($topQueries as $q)
                    <tr>
                        <td class="text-left">{{ $q['query'] }}</td>
                        <td class="text-right">{{ number_format($q['clicks']) }}</td>
                        <td class="text-right">{{ number_format($q['impressions']) }}</td>
                        <td class="text-right">{{ number_format($q['position'], 1) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-gray-500 py-4">No query data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top pages --}}
    <div class="admin-card p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Top Pages in Search</h3>
        <div class="overflow-x-auto">
            <table class="admin-table w-full text-sm">
                <thead><tr><th class="text-left">Page</th><th class="text-right">Clicks</th><th class="text-right">Impr.</th></tr></thead>
                <tbody>
                    @forelse($topPages as $p)
                    <tr>
                        <td class="text-left truncate max-w-xs">{{ \Illuminate\Support\Str::after($p['page'], 'adilsher.pro') ?: $p['page'] }}</td>
                        <td class="text-right">{{ number_format($p['clicks']) }}</td>
                        <td class="text-right">{{ number_format($p['impressions']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-gray-500 py-4">No page data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Country breakdown (search) --}}
<div class="admin-card p-6 mb-8">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Search Impressions by Country</h3>
    <div class="overflow-x-auto">
        <table class="admin-table w-full text-sm">
            <thead><tr><th class="text-left">Country</th><th class="text-right">Clicks</th><th class="text-right">Impressions</th></tr></thead>
            <tbody>
                @forelse($byCountry as $c)
                <tr>
                    <td class="text-left">{{ $c['country'] }}</td>
                    <td class="text-right">{{ number_format($c['clicks']) }}</td>
                    <td class="text-right">{{ number_format($c['impressions']) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-gray-500 py-4">No country data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function updatePeriod() {
    const v = document.getElementById('period-select').value;
    window.location.href = '{{ route('admin.analytics.search') }}?period=' + v;
}
@if($configured ?? false)
document.addEventListener('DOMContentLoaded', function () {
    const trend = @json($trend ?? []);
    const el = document.getElementById('gscTrend');
    if (el && window.Chart && trend.length) {
        new Chart(el, {
            type: 'line',
            data: {
                labels: trend.map(r => r.date),
                datasets: [
                    { label: 'Clicks', data: trend.map(r => r.clicks), borderColor: '#2563eb', tension: 0.3 },
                    { label: 'Impressions', data: trend.map(r => r.impressions), borderColor: '#41EAD4', tension: 0.3, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { type: 'linear', position: 'left' },
                    y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }
});
@endif
</script>
@endpush
