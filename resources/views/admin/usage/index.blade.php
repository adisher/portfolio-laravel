@extends('layouts.admin')

@section('title', 'Usage & Tools')

@section('content')
@php
    $totalTokens = $claude['input'] + $claude['output'];
    $maxCost = max($trend->max('cost'), 0.0001);
    $pct = min(100, (float) ($budgetStats['usage_percent'] ?? 0));
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Usage &amp; Tools</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Claude API and external tool usage for {{ now()->format('F Y') }}.</p>
</div>

{{-- Budget --}}
<div class="admin-card p-6 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Claude Budget</h2>
        <span class="status-badge">{{ ucfirst($budgetStats['status'] ?? 'ok') }}@if(!empty($budgetStats['is_paused'])) &middot; paused @endif</span>
    </div>
    <div class="flex items-end justify-between mb-2 text-sm">
        <span class="text-gray-700 dark:text-gray-300">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($budgetStats['current_usage'] ?? 0, 4) }}</span>
            <span class="text-gray-400">/ ${{ number_format($budgetStats['total_budget'] ?? 0, 2) }}</span>
        </span>
        <span class="text-gray-500">{{ number_format($pct, 1) }}% used</span>
    </div>
    <div class="w-full h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
        <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-orange-500' : 'bg-teal') }}" style="width: {{ $pct }}%"></div>
    </div>
</div>

{{-- Claude KPI row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="admin-card p-5">
        <p class="text-xs uppercase tracking-wide text-gray-400">Cost this month</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($claude['cost'], 4) }}</p>
    </div>
    <div class="admin-card p-5">
        <p class="text-xs uppercase tracking-wide text-gray-400">Tokens</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalTokens) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ number_format($claude['input']) }} in &middot; {{ number_format($claude['output']) }} out</p>
    </div>
    <div class="admin-card p-5">
        <p class="text-xs uppercase tracking-wide text-gray-400">API calls</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($claude['calls']) }}</p>
    </div>
    <div class="admin-card p-5">
        <p class="text-xs uppercase tracking-wide text-gray-400">Failed</p>
        <p class="text-2xl font-bold {{ $claude['failed'] > 0 ? 'text-red-500' : 'text-gray-900 dark:text-white' }} mt-1">{{ number_format($claude['failed']) }}</p>
    </div>
</div>

{{-- Daily cost trend --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Daily Claude cost (last 30 days)</h2>
    @if($trend->sum('cost') > 0)
    <div class="flex items-end gap-1 h-32">
        @foreach($trend as $d)
        <div class="flex-1 group relative flex items-end h-full">
            <div class="w-full rounded-t bg-teal/70 hover:bg-teal transition-all" style="height: {{ max(2, ($d['cost'] / $maxCost) * 100) }}%"></div>
            <span class="hidden group-hover:block absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs bg-gray-900 text-white px-2 py-1 rounded">
                {{ \Illuminate\Support\Carbon::parse($d['day'])->format('M j') }}: ${{ number_format($d['cost'], 4) }}
            </span>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-sm text-gray-400">No Claude usage in the last 30 days.</p>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- By service --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">By service (this month)</h2>
        @if($byService->isEmpty())
        <p class="text-sm text-gray-400">No usage yet.</p>
        @else
        <table class="admin-table w-full text-sm">
            <thead><tr><th class="text-left">Service</th><th class="text-right">Calls</th><th class="text-right">Tokens</th><th class="text-right">Cost</th></tr></thead>
            <tbody>
                @foreach($byService as $r)
                <tr>
                    <td>{{ $r->service }}</td>
                    <td class="text-right">{{ number_format($r->calls) }}</td>
                    <td class="text-right">{{ number_format($r->input + $r->output) }}</td>
                    <td class="text-right">${{ number_format($r->cost, 4) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- By model --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">By model (this month)</h2>
        @if($byModel->isEmpty())
        <p class="text-sm text-gray-400">No usage yet.</p>
        @else
        <table class="admin-table w-full text-sm">
            <thead><tr><th class="text-left">Model</th><th class="text-right">Calls</th><th class="text-right">Cost</th></tr></thead>
            <tbody>
                @foreach($byModel as $r)
                <tr>
                    <td class="break-all">{{ $r->model }}</td>
                    <td class="text-right">{{ number_format($r->calls) }}</td>
                    <td class="text-right">${{ number_format($r->cost, 4) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Tools --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">External tools (this month)</h2>
    @if($tools->isEmpty())
    <p class="text-sm text-gray-400">No external tool usage recorded yet (Pexels, IndexNow, GSC, Safepay).</p>
    @else
    <table class="admin-table w-full text-sm">
        <thead><tr><th class="text-left">Tool</th><th class="text-right">Calls</th><th class="text-right">Units</th><th class="text-right">Success</th><th class="text-right">Cost</th></tr></thead>
        <tbody>
            @foreach($tools as $t)
            <tr>
                <td class="font-medium">{{ ucfirst($t->tool) }}</td>
                <td class="text-right">{{ number_format($t->calls) }}</td>
                <td class="text-right">{{ number_format($t->qty) }} {{ $t->unit }}</td>
                <td class="text-right">{{ $t->calls > 0 ? number_format(($t->ok / $t->calls) * 100, 0) : 0 }}%</td>
                <td class="text-right">{{ $t->cost !== null ? '$' . number_format($t->cost, 4) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Recent Claude --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Recent Claude calls</h2>
        @if($recentClaude->isEmpty())
        <p class="text-sm text-gray-400">Nothing yet.</p>
        @else
        <div class="space-y-2">
            @foreach($recentClaude as $log)
            <div class="flex items-center justify-between text-sm border-b border-gray-100 dark:border-gray-700 pb-2">
                <div>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $log->service }}</span>
                    @unless($log->success)<span class="text-red-500 text-xs ml-1">failed</span>@endunless
                    <span class="block text-xs text-gray-400">{{ $log->created_at->diffForHumans() }} &middot; {{ number_format($log->input_tokens + $log->output_tokens) }} tok</span>
                </div>
                <span class="text-gray-600 dark:text-gray-300">${{ number_format($log->cost_usd, 4) }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Recent tools --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Recent tool calls</h2>
        @if($recentTools->isEmpty())
        <p class="text-sm text-gray-400">Nothing yet.</p>
        @else
        <div class="space-y-2">
            @foreach($recentTools as $log)
            <div class="flex items-center justify-between text-sm border-b border-gray-100 dark:border-gray-700 pb-2">
                <div>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ ucfirst($log->tool) }}</span>
                    <span class="text-gray-400 text-xs">{{ $log->action }}</span>
                    @unless($log->success)<span class="text-red-500 text-xs ml-1">failed</span>@endunless
                    <span class="block text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                </div>
                <span class="text-gray-600 dark:text-gray-300">{{ number_format($log->quantity) }} {{ $log->unit }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
