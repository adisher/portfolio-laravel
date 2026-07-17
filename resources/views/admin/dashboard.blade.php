@extends('layouts.admin')

@section('title', 'Dashboard - Admin Panel')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-400">Welcome back! Here's what's happening with your portfolio.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Projects</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_projects'] }}</p>
                <p class="text-sm text-green-600">{{ $stats['published_projects'] }} published</p>
            </div>
        </div>
    </div>

    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Blog Posts</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_posts'] }}</p>
                <p class="text-sm text-green-600">{{ $stats['published_posts'] }} published</p>
            </div>
        </div>
    </div>

    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Messages</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_contacts'] }}</p>
                <p class="text-sm {{ $stats['unread_contacts'] > 0 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ $stats['unread_contacts'] }} unread
                </p>
            </div>
        </div>
    </div>

    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900">
                <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                    </path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Page Views</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_posts'] *
                    150) }}</p>
                <p class="text-sm text-green-600">+12% this month</p>
            </div>
        </div>
    </div>
</div>

<!-- Content Pipeline & AI Budget Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Content Pipeline Widget -->
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Content Pipeline</h3>
            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $pipelineStats['auto_publish_enabled'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $pipelineStats['auto_publish_enabled'] ? 'Auto-Publish ON' : 'Auto-Publish OFF' }}
            </span>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $pipelineStats['articles_today'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Collected Today</div>
            </div>
            <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pipelineStats['pending_review'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Pending Review</div>
            </div>
            <div class="text-center p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $pipelineStats['approved'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Ready to Publish</div>
            </div>
        </div>

        <div class="flex items-center justify-between text-sm mb-2">
            <span class="text-gray-600 dark:text-gray-400">Published Today</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $pipelineStats['published_today'] }} / {{ $pipelineStats['max_per_day'] }}</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
            <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($pipelineStats['published_today'] / max($pipelineStats['max_per_day'], 1)) * 100 }}%"></div>
        </div>

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">{{ $pipelineStats['active_sources'] }} active sources</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $pipelineStats['high_score_ready'] }} high-score ready</span>
        </div>

        <div class="mt-4 flex space-x-2">
            <a href="{{ route('admin.collected-articles.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
                Review Articles →
            </a>
        </div>
    </div>

    <!-- AI Budget Widget -->
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Budget Control</h3>
            @if($budgetStats['configured'])
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $budgetStats['is_paused'] ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                    {{ $budgetStats['is_paused'] ? 'PAUSED' : 'Active' }}
                </span>
            @else
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                    Not Configured
                </span>
            @endif
        </div>

        @if($budgetStats['configured'])
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400">This Month</span>
                    <span class="font-medium text-gray-900 dark:text-white">${{ number_format($budgetStats['current_usage'], 2) }} / ${{ number_format($budgetStats['total_budget'], 2) }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="h-3 rounded-full transition-all duration-300 {{ $budgetStats['percentage_used'] >= 100 ? 'bg-red-500' : ($budgetStats['percentage_used'] >= 80 ? 'bg-yellow-500' : 'bg-blue-500') }}" style="width: {{ min($budgetStats['percentage_used'], 100) }}%"></div>
                </div>
                <div class="text-right text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $budgetStats['percentage_used'] }}% used</div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                    <div class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($budgetStats['api_calls_month']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">API Calls (Month)</div>
                </div>
                <div class="text-center p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                    <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($budgetStats['tokens_used_month']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Tokens Used</div>
                </div>
            </div>

            @if($budgetStats['is_paused'])
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Budget exhausted. AI features paused.
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-6">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">Run migrations to enable AI budget tracking</p>
            </div>
        @endif

        <a href="{{ route('admin.usage.index') }}" class="block text-center text-sm text-teal hover:underline mt-2 pt-3 border-t border-gray-100 dark:border-gray-700">
            View full usage &amp; tools &rarr;
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <!-- Profile Quick View -->
    <div class="admin-card p-6">
        <div class="flex items-center">
            @if(Auth::user()->profile_picture)
            <img src="{{ Auth::user()->profile_picture_url }}" alt="{{ Auth::user()->name }}"
                class="w-12 h-12 rounded-full object-cover">
            @else
            <div class="w-12 h-12 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                <span class="text-lg font-bold text-gray-600 dark:text-gray-300">{{ Auth::user()->initials }}</span>
            </div>
            @endif
            <div class="ml-4 flex-1">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Welcome back!</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->name }}</p>
                @if(Auth::user()->last_login_at)
                <p class="text-xs text-gray-400">Last login: {{ Auth::user()->last_login_at->diffForHumans() }}</p>
                @endif
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.profile.show') }}"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
                View Profile →
            </a>
        </div>
    </div>

    <!-- Analytics Quick View -->
    <div class="admin-card p-6">
        @php
        $todayStats = \App\Models\Visitor::getTodayStats();
        @endphp
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Today's Analytics</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center">
                <div class="text-xl font-bold text-blue-600">{{ number_format($todayStats['visitors']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Visitors</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-green-600">{{ number_format($todayStats['page_views']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Page Views</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-purple-600">{{ gmdate('i:s', $todayStats['avg_session_duration']) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Avg. Session</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-bold text-orange-600">{{ $todayStats['bounce_rate'] }}%</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Bounce Rate</div>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.analytics.index') }}"
                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
                View Full Analytics →
            </a>
        </div>
    </div>
</div>

<!-- Sports Quick Stats -->
@if($sportsStats['live_matches'] > 0 || $sportsStats['today_matches'] > 0)
<div class="admin-card p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sports Overview</h3>
            @if($sportsStats['live_matches'] > 0)
            <span class="flex items-center space-x-1 px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                <span>{{ $sportsStats['live_matches'] }} Live</span>
            </span>
            @endif
        </div>
        <a href="{{ route('admin.sports.dashboard') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500">
            Sports Dashboard →
        </a>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div class="text-center p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $sportsStats['live_matches'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Live Now</div>
        </div>
        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $sportsStats['today_matches'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Today's Matches</div>
        </div>
        <div class="text-center p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $sportsStats['upcoming_matches'] }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Upcoming</div>
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Projects -->
    <div class="admin-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Projects</h2>
                <a href="{{ route('admin.projects.index') }}" class="text-sm text-blue-600 hover:text-blue-500">View
                    all</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentProjects->count())
            <div class="space-y-4">
                @foreach($recentProjects as $project)
                <div class="flex items-center space-x-4">
                    <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}"
                        class="w-12 h-12 rounded-lg object-cover">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->title }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project->category->name }} • {{
                            $project->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="status-badge status-{{ $project->status }}">{{ ucfirst($project->status) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No projects yet.</p>
            @endif
        </div>
    </div>

    <!-- Recent Blog Posts -->
    <div class="admin-card">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Blog Posts</h2>
                <a href="{{ route('admin.blog-posts.index') }}" class="text-sm text-blue-600 hover:text-blue-500">View
                    all</a>
            </div>
        </div>
        <div class="p-6">
            @if($recentPosts->count())
            <div class="space-y-4">
                @foreach($recentPosts as $post)
                <div class="flex items-start space-x-4">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $post->title }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $post->category->name }} • {{
                            $post->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="status-badge status-{{ $post->status }}">{{ ucfirst($post->status) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No blog posts yet.</p>
            @endif
        </div>
    </div>
</div>

<!-- Recent Messages -->
@if($recentContacts->count())
<div class="mt-6 admin-card">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Messages</h2>
            <a href="{{ route('admin.contacts.index') }}" class="text-sm text-blue-600 hover:text-blue-500">View all</a>
        </div>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($recentContacts as $contact)
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <span class="text-gray-600 dark:text-gray-300 font-medium text-sm">
                        {{ substr($contact->name, 0, 1) }}
                    </span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $contact->name }}</h3>
                        <span class="status-badge status-{{ $contact->status }}">{{ ucfirst($contact->status) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $contact->email }} • {{
                        $contact->created_at->diffForHumans() }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ Str::limit($contact->message, 100) }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Quick Actions -->
<div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="{{ route('admin.projects.create') }}" class="admin-card p-6 hover:shadow-md transition-shadow text-center">
        <svg class="w-8 h-8 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h3 class="font-medium text-gray-900 dark:text-white">New Project</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Add a new project to your portfolio</p>
    </a>

    <a href="{{ route('admin.blog-posts.create') }}"
        class="admin-card p-6 hover:shadow-md transition-shadow text-center">
        <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
            </path>
        </svg>
        <h3 class="font-medium text-gray-900 dark:text-white">Write Article</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Create a new blog post</p>
    </a>

    <a href="{{ route('home') }}" target="_blank" class="admin-card p-6 hover:shadow-md transition-shadow text-center">
        <svg class="w-8 h-8 text-purple-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
        </svg>
        <h3 class="font-medium text-gray-900 dark:text-white">View Site</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Visit your live portfolio</p>
    </a>
</div>
<div class="admin-card p-6 hover:shadow-md transition-shadow text-center my-5">
    <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-7-7 7-7m8 14l7-7-7-7"></path>
    </svg>
    <h3 class="font-medium text-gray-900 dark:text-white">Generate Sitemap</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Update XML sitemap for SEO</p>
    <a href="{{ route('admin.sitemap.generate') }}" class="text-green-600 hover:text-green-700 font-medium text-sm">
        Generate Now →
    </a>
</div>
@endsection