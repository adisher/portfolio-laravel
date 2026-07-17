<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\ToolUsageLog;
use App\Services\AiBudgetService;

class UsageController extends Controller
{
    public function index(AiBudgetService $budget)
    {
        $budgetStats = $budget->getStats();

        // Claude summary (current month)
        $claude = [
            'cost'   => (float) AiUsageLog::currentMonth()->successful()->sum('cost_usd'),
            'calls'  => AiUsageLog::currentMonth()->count(),
            'failed' => AiUsageLog::currentMonth()->failed()->count(),
            'input'  => (int) AiUsageLog::currentMonth()->successful()->sum('input_tokens'),
            'output' => (int) AiUsageLog::currentMonth()->successful()->sum('output_tokens'),
        ];

        $byService = AiUsageLog::currentMonth()->successful()
            ->selectRaw('service, COUNT(*) as calls, SUM(input_tokens) as input, SUM(output_tokens) as output, SUM(cost_usd) as cost')
            ->groupBy('service')->orderByDesc('cost')->get();

        $byModel = AiUsageLog::currentMonth()->successful()
            ->selectRaw('model, COUNT(*) as calls, SUM(cost_usd) as cost')
            ->groupBy('model')->orderByDesc('cost')->get();

        // Daily cost trend, last 30 days, zero-filled
        $rawTrend = AiUsageLog::successful()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(cost_usd) as cost')
            ->groupBy('day')->pluck('cost', 'day');

        $trend = collect(range(0, 29))->map(function ($i) use ($rawTrend) {
            $day = now()->subDays(29 - $i)->toDateString();
            return ['day' => $day, 'cost' => (float) ($rawTrend[$day] ?? 0)];
        });

        $recentClaude = AiUsageLog::latest()->limit(15)->get();

        // Non-Claude tools (current month)
        $tools = ToolUsageLog::currentMonth()
            ->selectRaw('tool, COUNT(*) as calls, SUM(quantity) as qty, MAX(unit) as unit, SUM(cost_usd) as cost, SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as ok')
            ->groupBy('tool')->orderByDesc('calls')->get();

        $recentTools = ToolUsageLog::latest()->limit(15)->get();

        return view('admin.usage.index', compact(
            'budgetStats', 'claude', 'byService', 'byModel', 'trend', 'recentClaude', 'tools', 'recentTools'
        ));
    }
}
