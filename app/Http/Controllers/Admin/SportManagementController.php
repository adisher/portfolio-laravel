<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\SportApiSyncLog;
use App\Models\SportMatch;
use App\Services\Sports\CricbuzzApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SportManagementController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'live_matches' => SportMatch::live()->count(),
            'today_matches' => SportMatch::today()->count(),
            'total_matches' => SportMatch::count(),
            'upcoming_matches' => SportMatch::upcoming()->count(),
        ];

        $liveMatches = SportMatch::live()
            ->with(['sport', 'homeTeam', 'awayTeam', 'tournament'])
            ->get();

        $recentSyncs = SportApiSyncLog::latest()
            ->take(10)
            ->get();

        $sports = Sport::active()
            ->withCount('matches')
            ->orderBy('sort_order')
            ->get();

        return view('admin.sports.dashboard', compact('stats', 'liveMatches', 'recentSyncs', 'sports'));
    }

    public function sync(Request $request, string $type)
    {
        $validTypes = ['matches', 'live', 'details'];
        if (!in_array($type, $validTypes)) {
            return back()->with('error', "Invalid sync type: {$type}");
        }

        $args = ['type' => $type];
        $args['--force'] = true;

        Artisan::call('sports:sync', $args);

        return back()->with('success', "Sync ({$type}) completed successfully.");
    }

    public function syncLogs()
    {
        $logs = SportApiSyncLog::latest()->paginate(25);

        return view('admin.sports.sync-logs', compact('logs'));
    }
}
