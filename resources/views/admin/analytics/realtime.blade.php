@extends('layouts.admin')

@section('title', 'Real-time Analytics - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Real-time Analytics</h1>
            <p class="text-gray-600 dark:text-gray-400">Live visitor activity and current performance</p>
        </div>
        <a href="{{ route('admin.analytics.index') }}" class="btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to Analytics
        </a>
    </div>
</div>

<!-- Real-time Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="admin-card p-4 text-center">
        <div class="flex items-center justify-center mb-2">
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-2"></div>
            <span class="text-sm text-gray-500 dark:text-gray-400">Active Now</span>
        </div>
        <div class="text-2xl font-bold text-green-600" id="active-visitors">{{ $activeVisitors }}</div>
    </div>

    <div class="admin-card p-4 text-center">
       <div class="text-2xl font-bold text-blue-600">{{ number_format($todayStats['visitors']) }}</div>
       <div class="text-sm text-gray-500 dark:text-gray-400">Today's Visitors</div>
   </div>
   
   <div class="admin-card p-4 text-center">
       <div class="text-2xl font-bold text-purple-600">{{ number_format($todayStats['page_views']) }}</div>
       <div class="text-sm text-gray-500 dark:text-gray-400">Today's Views</div>
   </div>
   
   <div class="admin-card p-4 text-center">
       <div class="text-2xl font-bold text-orange-600">{{ $todayStats['bounce_rate'] }}%</div>
       <div class="text-sm text-gray-500 dark:text-gray-400">Bounce Rate</div>
   </div>
</div>

<!-- Hourly Traffic -->
<div class="admin-card p-6 mb-8">
   <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Today's Hourly Traffic</h2>
   <div class="h-64">
       <canvas id="hourly-chart"></canvas>
   </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
   <!-- Recent Page Views -->
   <div class="admin-card p-6">
       <div class="flex justify-between items-center mb-4">
           <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Page Views</h2>
           <button onclick="refreshActivity()" class="text-blue-600 hover:text-blue-500 text-sm">
               <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
               </svg>
               Refresh
           </button>
       </div>
       
       <div id="recent-activity" class="space-y-3 max-h-96 overflow-y-auto">
           @foreach($recentPageViews as $view)
           <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
               <div class="flex-1">
                   <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                       {{ $view['title'] }}
                   </div>
                   <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                       {{ $view['url'] }}
                   </div>
               </div>
               <div class="text-right">
                   <div class="text-xs text-gray-500 dark:text-gray-400">{{ $view['time'] }}</div>
                   <div class="text-xs text-gray-600 dark:text-gray-300">
                       {{ $view['country'] ? $view['country'] . ' •' : '' }} {{ $view['device'] }}
                   </div>
               </div>
           </div>
           @endforeach
       </div>
   </div>

   <!-- Live Visitor Map (Simplified) -->
   <div class="admin-card p-6">
       <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Visitor Locations</h2>
       <div class="space-y-2">
           @php
           $locations = collect($recentPageViews)->groupBy('country')->map(function($views) {
               return $views->count();
           })->sortDesc()->take(10);
           @endphp
           
           @forelse($locations as $country => $count)
           <div class="flex items-center justify-between">
               <div class="flex items-center">
                   <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                   <span class="text-sm text-gray-900 dark:text-white">{{ $country ?: 'Unknown' }}</span>
               </div>
               <span class="text-sm text-gray-600 dark:text-gray-400">{{ $count }} active</span>
           </div>
           @empty
           <p class="text-gray-500 dark:text-gray-400">No recent activity</p>
           @endforelse
       </div>
   </div>
</div>

<!-- Auto-refresh notification -->
<div id="refresh-indicator" class="fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg hidden">
   <div class="flex items-center">
       <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
           <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
           <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
       </svg>
       Updating...
   </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Hourly Traffic Chart
const hourlyCtx = document.getElementById('hourly-chart').getContext('2d');
const hourlyData = @json($hourlyTraffic);

// Create 24-hour array with zeros
const hours = Array.from({length: 24}, (_, i) => i);
const hourlyViews = hours.map(hour => {
   const data = hourlyData.find(item => item.hour == hour);
   return data ? data.views : 0;
});

new Chart(hourlyCtx, {
   type: 'bar',
   data: {
       labels: hours.map(h => h.toString().padStart(2, '0') + ':00'),
       datasets: [{
           label: 'Page Views',
           data: hourlyViews,
           backgroundColor: 'rgba(59, 130, 246, 0.6)',
           borderColor: 'rgb(59, 130, 246)',
           borderWidth: 1
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
               display: false
           }
       }
   }
});

// Auto-refresh functionality
let refreshInterval;

function startAutoRefresh() {
   refreshInterval = setInterval(() => {
       refreshActivity();
   }, 30000); // Refresh every 30 seconds
}

function stopAutoRefresh() {
   if (refreshInterval) {
       clearInterval(refreshInterval);
   }
}

function refreshActivity() {
   const indicator = document.getElementById('refresh-indicator');
   indicator.classList.remove('hidden');
   
   fetch('{{ route("admin.analytics.realtime") }}', {
       headers: {
           'X-Requested-With': 'XMLHttpRequest'
       }
   })
   .then(response => response.text())
   .then(html => {
       // Parse the response and update specific sections
       const parser = new DOMParser();
       const doc = parser.parseFromString(html, 'text/html');
       
       // Update active visitors count
       const activeVisitors = doc.querySelector('#active-visitors');
       if (activeVisitors) {
           document.getElementById('active-visitors').textContent = activeVisitors.textContent;
       }
       
       // Update recent activity
       const recentActivity = doc.querySelector('#recent-activity');
       if (recentActivity) {
           document.getElementById('recent-activity').innerHTML = recentActivity.innerHTML;
       }
       
       indicator.classList.add('hidden');
   })
   .catch(error => {
       console.error('Error refreshing data:', error);
       indicator.classList.add('hidden');
   });
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', function() {
   startAutoRefresh();
});

// Stop auto-refresh when page is not visible
document.addEventListener('visibilitychange', function() {
   if (document.hidden) {
       stopAutoRefresh();
   } else {
       startAutoRefresh();
   }
});

// Stop auto-refresh when leaving page
window.addEventListener('beforeunload', function() {
   stopAutoRefresh();
});
</script>
@endpush