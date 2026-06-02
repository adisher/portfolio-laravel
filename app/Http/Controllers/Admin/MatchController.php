<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\SportMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $query = SportMatch::with(['sport', 'homeTeam', 'awayTeam', 'tournament']);

        if ($request->filled('sport')) {
            $query->whereHas('sport', fn ($q) => $q->where('slug', $request->sport));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $matches = $query->latest('scheduled_at')->paginate(20);
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.matches.index', compact('matches', 'sports'));
    }

    public function create()
    {
        $sports = Sport::active()->orderBy('sort_order')->get();
        $teams = Team::active()->orderBy('name')->get();
        $tournaments = Tournament::active()->orderBy('name')->get();

        return view('admin.sports.matches.create', compact('sports', 'teams', 'tournaments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sport_id' => 'required|exists:sports,id',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'status' => 'required|in:scheduled,live,completed,postponed,cancelled,abandoned',
            'match_type' => 'nullable|string|max:50',
            'venue' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'scheduled_at' => 'required|date',
            'meta_description' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');

        SportMatch::create($validated);

        return redirect()->route('admin.sports.matches.index')
            ->with('success', 'Match created successfully.');
    }

    public function edit(SportMatch $match)
    {
        $sports = Sport::active()->orderBy('sort_order')->get();
        $teams = Team::active()->orderBy('name')->get();
        $tournaments = Tournament::active()->orderBy('name')->get();

        return view('admin.sports.matches.edit', compact('match', 'sports', 'teams', 'tournaments'));
    }

    public function update(Request $request, SportMatch $match)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sport_id' => 'required|exists:sports,id',
            'tournament_id' => 'nullable|exists:tournaments,id',
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'status' => 'required|in:scheduled,live,completed,postponed,cancelled,abandoned',
            'match_type' => 'nullable|string|max:50',
            'venue' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'scheduled_at' => 'required|date',
            'result_summary' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');

        $match->update($validated);

        return redirect()->route('admin.sports.matches.index')
            ->with('success', 'Match updated successfully.');
    }

    public function destroy(SportMatch $match)
    {
        $match->delete();

        return redirect()->route('admin.sports.matches.index')
            ->with('success', 'Match deleted successfully.');
    }
}
