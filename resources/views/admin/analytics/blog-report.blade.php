@extends('layouts.admin')

@section('title', 'Blog Report - Admin Panel')

@php
    $pct  = fn($v) => number_format(($v ?? 0) * 100, 1) . '%';
    $pos  = fn($v) => $v === null ? '—' : number_format($v, 1);
    $num  = fn($v) => number_format($v ?? 0);
    // Small helper for the niche bar widths (relative to the busiest niche).
    $maxNicheImpr = $byNiche->max('impressions') ?: 1;
@endphp

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Blog Report</h1>
            <p class="text-gray-600 dark:text-gray-400">
                How the blog performs by niche and by article type &mdash; search demand vs. on-site reads.
            </p>
        </div>
        <select id="period-select" onchange="updatePeriod()"
            class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
            <option value="7"  {{ $days==7  ? 'selected' : '' }}>Last 7 days</option>
            <option value="28" {{ $days==28 ? 'selected' : '' }}>Last 28 days</option>
            <option value="90" {{ $days==90 ? 'selected' : '' }}>Last 90 days</option>
        </select>
    </div>
</div>

{{-- Data-source note: two different systems, one of which may be dark. --}}
<div class="admin-card p-4 mb-6 text-sm text-gray-600 dark:text-gray-400 flex items-start gap-3">
    <svg class="w-5 h-5 flex-shrink-0 text-teal mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Impressions, clicks &amp; position</span>
        come from Google Search Console (search demand, ~2-day lag).
        <span class="font-medium text-gray-700 dark:text-gray-300">Views</span>
        are first-party on-site reads. They rarely match: an article can rank in search yet get few reads, or pull steady reads from links while barely showing in search.
        @unless($gscConfigured)
            <span class="block mt-1 text-sunset font-medium">Search Console is not connected, so impressions/clicks/position are blank. Only on-site views are shown below.</span>
        @endunless
    </div>
</div>

{{-- ── Summary strip ─────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $num($totals['articles']) }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Published articles</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-indigo-500">{{ $num($totals['impressions']) }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Search impressions</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-500">{{ $num($totals['clicks']) }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Search clicks</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-teal">{{ $pct($totals['ctr']) }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg CTR</div>
    </div>
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-sunset">{{ $num($totals['views']) }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">On-site views</div>
    </div>
</div>

{{-- ── Performance by niche ───────────────────────────────────── --}}
<div class="admin-card p-6 mb-8">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Performance by Niche</h3>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Which categories earn the most search demand. <span class="font-medium">Per-article</span> columns normalise for the fact that some niches simply have more posts.
    </p>
    <div class="overflow-x-auto">
        <table class="admin-table w-full text-sm">
            <thead>
                <tr>
                    <th class="text-left">Niche</th>
                    <th class="text-right">Articles</th>
                    <th class="text-right">Impressions</th>
                    <th class="text-right">Impr / post</th>
                    <th class="text-right">Clicks</th>
                    <th class="text-right">CTR</th>
                    <th class="text-right">Avg pos.</th>
                    <th class="text-right">Views</th>
                    <th class="text-right">Views / post</th>
                </tr>
            </thead>
            <tbody>
                @forelse($byNiche as $n)
                <tr>
                    <td class="text-left">
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $n['label'] }}</div>
                        <div class="mt-1 h-1.5 rounded-full bg-gray-200 dark:bg-white/10 overflow-hidden">
                            <div class="h-full bg-teal rounded-full" style="width: {{ max(2, round($n['impressions'] / $maxNicheImpr * 100)) }}%"></div>
                        </div>
                    </td>
                    <td class="text-right">{{ $num($n['articles']) }}</td>
                    <td class="text-right font-medium">{{ $num($n['impressions']) }}</td>
                    <td class="text-right text-teal">{{ $num($n['impr_per_post']) }}</td>
                    <td class="text-right">{{ $num($n['clicks']) }}</td>
                    <td class="text-right">{{ $pct($n['ctr']) }}</td>
                    <td class="text-right">{{ $pos($n['avg_position']) }}</td>
                    <td class="text-right">{{ $num($n['views']) }}</td>
                    <td class="text-right text-sunset">{{ $num($n['views_per_post']) }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-gray-500 py-6">No blog data in this period yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Curated vs Original ────────────────────────────────────── --}}
<div class="admin-card p-6 mb-8">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Curated vs. Original</h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Do the hand-written flagship articles (<span class="font-medium">original</span>) earn their effort against the automated rewrites (<span class="font-medium">curated</span>)? Compare the per-article columns, not the totals &mdash; there are far more curated posts.
    </p>
    <div class="overflow-x-auto">
        <table class="admin-table w-full text-sm">
            <thead>
                <tr>
                    <th class="text-left">Type</th>
                    <th class="text-right">Articles</th>
                    <th class="text-right">Impressions</th>
                    <th class="text-right">Impr / post</th>
                    <th class="text-right">Clicks</th>
                    <th class="text-right">CTR</th>
                    <th class="text-right">Avg pos.</th>
                    <th class="text-right">Views / post</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bySource as $s)
                <tr>
                    <td class="text-left">
                        @php
                            $labelMap = ['original' => 'Original (hand-written)', 'curated' => 'Curated (AI rewrite)', 'unknown' => 'Uncategorised'];
                            $badge = $s['label'] === 'original' ? 'bg-teal/10 text-teal' : ($s['label'] === 'curated' ? 'bg-sunset/10 text-sunset' : 'bg-gray-500/10 text-gray-400');
                        @endphp
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $badge }}">
                            {{ $labelMap[$s['label']] ?? ucfirst($s['label']) }}
                        </span>
                    </td>
                    <td class="text-right">{{ $num($s['articles']) }}</td>
                    <td class="text-right">{{ $num($s['impressions']) }}</td>
                    <td class="text-right font-medium text-teal">{{ $num($s['impr_per_post']) }}</td>
                    <td class="text-right">{{ $num($s['clicks']) }}</td>
                    <td class="text-right">{{ $pct($s['ctr']) }}</td>
                    <td class="text-right">{{ $pos($s['avg_position']) }}</td>
                    <td class="text-right font-medium text-sunset">{{ $num($s['views_per_post']) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-gray-500 py-6">No data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- ── Top articles by impressions ────────────────────────── --}}
    <div class="admin-card p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Top Articles by Search Demand</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Your biggest search earners &mdash; the shape to repeat.</p>
        <div class="overflow-x-auto">
            <table class="admin-table w-full text-sm">
                <thead><tr><th class="text-left">Article</th><th class="text-right">Impr.</th><th class="text-right">Clicks</th><th class="text-right">Views</th></tr></thead>
                <tbody>
                    @forelse($topArticles as $a)
                    <tr>
                        <td class="text-left">
                            <a href="{{ route('blog.show', $a['slug']) }}" target="_blank" class="text-gray-900 dark:text-gray-100 hover:text-teal">{{ \Illuminate\Support\Str::limit($a['title'], 52) }}</a>
                            <div class="text-xs text-gray-400">{{ $a['category'] }}</div>
                        </td>
                        <td class="text-right font-medium">{{ $num($a['impressions']) }}</td>
                        <td class="text-right">{{ $num($a['clicks']) }}</td>
                        <td class="text-right">{{ $num($a['views']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-gray-500 py-6">No data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Opportunity articles ───────────────────────────────── --}}
    <div class="admin-card p-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Quick-Win Opportunities</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            Ranking on page 1&ndash;2 (pos. 4&ndash;20) with real impressions but weak clicks. A sharper title/meta or a small ranking nudge is the cheapest traffic here.
        </p>
        <div class="overflow-x-auto">
            <table class="admin-table w-full text-sm">
                <thead><tr><th class="text-left">Article</th><th class="text-right">Impr.</th><th class="text-right">CTR</th><th class="text-right">Pos.</th></tr></thead>
                <tbody>
                    @forelse($opportunities as $a)
                    <tr>
                        <td class="text-left">
                            <a href="{{ route('blog.show', $a['slug']) }}" target="_blank" class="text-gray-900 dark:text-gray-100 hover:text-teal">{{ \Illuminate\Support\Str::limit($a['title'], 52) }}</a>
                            <div class="text-xs text-gray-400">{{ $a['category'] }}</div>
                        </td>
                        <td class="text-right font-medium">{{ $num($a['impressions']) }}</td>
                        <td class="text-right {{ $a['ctr'] < 0.01 ? 'text-sunset' : '' }}">{{ $pct($a['ctr']) }}</td>
                        <td class="text-right">{{ $pos($a['position']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-gray-500 py-6">No opportunities flagged in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($unmatchedCount > 0)
<div class="admin-card p-4 text-sm text-gray-500 dark:text-gray-400">
    Note: {{ $num($unmatchedImpr) }} search impressions across {{ $unmatchedCount }} blog URL(s) didn't match a current post (renamed or deleted slugs). They're counted in the summary strip but excluded from the niche and type roll-ups.
</div>
@endif
@endsection

@push('scripts')
<script>
function updatePeriod() {
    window.location.href = '{{ route('admin.analytics.blog-report') }}?period=' + document.getElementById('period-select').value;
}
</script>
@endpush
