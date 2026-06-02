<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function index(Request $request)
    {
        $query = Tournament::with('sport')->withCount('matches');

        if ($request->filled('sport')) {
            $query->whereHas('sport', fn ($q) => $q->where('slug', $request->sport));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $tournaments = $query->orderBy('name')->paginate(25);
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.tournaments.index', compact('tournaments', 'sports'));
    }

    public function create()
    {
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.tournaments.create', compact('sports'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'sport_id' => 'required|exists:sports,id',
            'logo' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'season' => 'nullable|string|max:20',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured');

        Tournament::create($validated);

        return redirect()->route('admin.sports.tournaments.index')
            ->with('success', 'Tournament created successfully.');
    }

    public function edit(Tournament $tournament)
    {
        $sports = Sport::active()->orderBy('sort_order')->get();

        return view('admin.sports.tournaments.edit', compact('tournament', 'sports'));
    }

    public function update(Request $request, Tournament $tournament)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'sport_id' => 'required|exists:sports,id',
            'logo' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'season' => 'nullable|string|max:20',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured');

        $tournament->update($validated);

        return redirect()->route('admin.sports.tournaments.index')
            ->with('success', 'Tournament updated successfully.');
    }

    public function destroy(Tournament $tournament)
    {
        $tournament->delete();

        return redirect()->route('admin.sports.tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }
}
