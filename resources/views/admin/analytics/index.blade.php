@extends('layouts.admin')

@section('title', 'Analytics Dashboard - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Analytics Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Insights and performance metrics for your portfolio</p>
        </div>
        <div class="flex space-x-3">
            <select id="period-select" onchange="updatePeriod()"
                class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="7" {{ $days==7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $days==30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $days==90 ? 'selected' : '' }}>Last 90 days</option>
                <option value="365" {{ $days==365 ? 'selected' : '' }}>Last year</option>
            </select>
            <a href="{{ route('admin.analytics.realtime') }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Real-time
            </a>
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Export
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50">
                    <a href="{{ route('admin.analytics.export', ['type' => 'visitors', 'period' => $days]) }}"
                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Visitor
                        Data</a>
                    <a href="{{ route('admin.analytics.export', ['type' => 'pages', 'period' => $days]) }}"
                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Page
                        Views</a>
                    <a href="{{ route('admin.analytics.export', ['type' => 'contacts', 'period' => $days]) }}"
                        class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Contact
                        Data</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overview Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">{{ number_format($stats['current']['visitors']) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Visitors</div>
        @if(isset($stats['changes']['visitors']))
        <div class="text-xs mt-1 {{ $stats['changes']['visitors'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $stats['changes']['visitors'] >= 0 ? '+' : '' }}{{ $stats['changes']['visitors'] }}%
        </div>
        @endif
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-green-600">{{ number_format($stats['current']['page_views']) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Page Views</div>
        @if(isset($stats['changes']['page_views']))
        <div class="text-xs mt-1 {{ $stats['changes']['page_views'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $stats['changes']['page_views'] >= 0 ? '+' : '' }}{{ $stats['changes']['page_views'] }}%
        </div>
        @endif
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-purple-600">{{ gmdate('i:s', $stats['current']['avg_session_duration']) }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Avg. Session</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-orange-600">{{ $stats['current']['bounce_rate'] }}%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Bounce Rate</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-red-600">{{ number_format($stats['current']['contacts']) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Contacts</div>
        @if(isset($stats['changes']['contacts']))
        <div class="text-xs mt-1 {{ $stats['changes']['contacts'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $stats['changes']['contacts'] >= 0 ? '+' : '' }}{{ $stats['changes']['contacts'] }}%
        </div>
        @endif
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-indigo-600">{{ $stats['current']['conversion_rate'] }}%</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Conversion</div>
    </div>
</div>

<!-- Traffic Trends Chart -->
<div class="admin-card p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Traffic Trends</h2>
    <div class="h-80">
        <canvas id="traffic-chart"></canvas>
    </div>
</div>

<!-- Content Performance -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Popular Pages -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Popular Pages</h2>
        <div class="space-y-3">
            @forelse($popularPages as $page)
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $page->page_title ?: 'Untitled' }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ $page->url }}
                    </div>
                </div>
                <div class="text-sm font-medium text-blue-600">
                    {{ number_format($page->views) }} views
                </div>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400">No page data available</p>
            @endforelse
        </div>
    </div>

    <!-- Popular Projects -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Popular Projects</h2>
        <div class="space-y-3">
            @forelse($popularProjects as $project)
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $project['title'] }}
                    </div>
                    @if($project['url'])
                    <a href="{{ $project['url'] }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-500">
                        View Project →
                    </a>
                    @endif
                </div>
                <div class="text-sm font-medium text-green-600">
                    {{ number_format($project['views']) }} views
                </div>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400">No project data available</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Visitor Insights -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Device Breakdown -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Device Types</h2>
        <div class="space-y-3">
            @php
            $totalDevices = array_sum($deviceBreakdown->toArray());
            @endphp
            @foreach($deviceBreakdown as $device => $count)
            @php
            $percentage = $totalDevices > 0 ? round(($count / $totalDevices) * 100, 1) : 0;
            @endphp
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div
                        class="w-3 h-3 rounded-full mr-3 {{ $device === 'mobile' ? 'bg-blue-500' : ($device === 'tablet' ? 'bg-green-500' : 'bg-purple-500') }}">
                    </div>
                    <span class="text-sm text-gray-900 dark:text-white capitalize">{{ $device }}</span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $percentage }}% ({{ number_format($count) }})
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Traffic Sources -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Traffic Sources</h2>
        <div class="space-y-3">
            @php
            $totalSources = array_sum($trafficSources);
            $sourceColors = ['direct' => 'bg-gray-500', 'search' => 'bg-green-500', 'social' => 'bg-blue-500',
            'referral' => 'bg-orange-500'];
            @endphp
            @foreach($trafficSources as $source => $count)
            @php
            $percentage = $totalSources > 0 ? round(($count / $totalSources) * 100, 1) : 0;
            @endphp
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full mr-3 {{ $sourceColors[$source] ?? 'bg-gray-400' }}"></div>
                    <span class="text-sm text-gray-900 dark:text-white capitalize">{{ $source }}</span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $percentage }}% ({{ number_format($count) }})
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Top Countries -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Countries</h2>
        <div class="space-y-3">
            @forelse($topCountries as $country)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-900 dark:text-white">{{ $country->country }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($country->visitors) }}</span>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400">No country data available</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Contact Analytics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Contact Sources -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Sources</h2>
        <div class="space-y-3">
            @forelse($contactSources as $source)
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <span class="text-sm text-gray-900 dark:text-white">{{ $source->source_page }}</span>
                </div>
                <span class="text-sm font-medium text-blue-600">{{ number_format($source->contacts) }}</span>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400">No contact source data available</p>
            @endforelse
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="admin-card p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance</h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Average Load Time</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $performanceStats['avg_load_time']
                    }}ms</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Total Page Views</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{
                    number_format($performanceStats['total_page_views']) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Unique Pages</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{
                    number_format($performanceStats['unique_pages']) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Conversion Rate</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $contactStats['conversion_rate']
                    }}%</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Traffic Trends Chart
const ctx = document.getElementById('traffic-chart').getContext('2d');
const trafficData = @json($trafficTrends);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trafficData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Visitors',
            data: trafficData.map(item => item.visitors),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1
        }, {
            label: 'Page Views',
            data: trafficData.map(item => item.page_views),
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(156, 163, 175, 0.1)'
                }
            },
            x: {
                grid: {
                    color: 'rgba(156, 163, 175, 0.1)'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});

function updatePeriod() {
    const period = document.getElementById('period-select').value;
    window.location.href = `{{ route('admin.analytics.index') }}?period=${period}`;
}
</script>
@endpush