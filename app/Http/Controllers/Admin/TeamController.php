<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = Team::with('sport');

        if ($request->filled('sport')) {
            $query->whereHas('sport', fn ($q) => $q->where('slug', $request->sport));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        $teams = $query->orderBy('name')->paginate(25);
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.teams.index', compact('teams', 'sports'));
    }

    public function create()
    {
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.teams.create', compact('sports'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'abbreviation' => 'nullable|string|max:10',
            'sport_id' => 'required|exists:sports,id',
            'logo' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|max:5',
            'primary_color' => 'nullable|string|max:10',
        ]);

        Team::create($validated);

        return redirect()->route('admin.sports.teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function edit(Team $team)
    {
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.teams.edit', compact('team', 'sports'));
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'abbreviation' => 'nullable|string|max:10',
            'sport_id' => 'required|exists:sports,id',
            'logo' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|max:5',
            'primary_color' => 'nullable|string|max:10',
        ]);

        $team->update($validated);

        return redirect()->route('admin.sports.teams.index')
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team)
    {
        $team->delete();

        return redirect()->route('admin.sports.teams.index')
            ->with('success', 'Team deleted successfully.');
    }
}
