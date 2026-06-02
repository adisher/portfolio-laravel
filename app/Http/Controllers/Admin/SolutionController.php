<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Solution;
use Illuminate\Http\Request;

class SolutionController extends Controller
{
    public function index()
    {
        $solutions = Solution::orderBy('sort_order')->paginate(15);

        return view('admin.solutions.index', compact('solutions'));
    }

    public function create()
    {
        return view('admin.solutions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'icon'         => 'nullable|string',
            'keywords'     => 'nullable|string',
            'accent_color' => 'required|in:teal,sunset,blend',
            'is_featured'  => 'boolean',
            'is_published' => 'boolean',
            'sort_order'   => 'nullable|integer',
        ]);

        $validated['keywords'] = $request->keywords
            ? array_map('trim', explode(',', $request->keywords))
            : null;

        $validated['is_featured']  = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        Solution::create($validated);

        return redirect()->route('admin.solutions.index')->with('success', 'Solution created successfully.');
    }

    public function edit(Solution $solution)
    {
        return view('admin.solutions.edit', compact('solution'));
    }

    public function update(Request $request, Solution $solution)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'icon'         => 'nullable|string',
            'keywords'     => 'nullable|string',
            'accent_color' => 'required|in:teal,sunset,blend',
            'is_featured'  => 'boolean',
            'is_published' => 'boolean',
            'sort_order'   => 'nullable|integer',
        ]);

        $validated['keywords'] = $request->keywords
            ? array_map('trim', explode(',', $request->keywords))
            : null;

        $validated['is_featured']  = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        $solution->update($validated);

        return redirect()->route('admin.solutions.index')->with('success', 'Solution updated successfully.');
    }

    public function destroy(Solution $solution)
    {
        $solution->delete();

        return redirect()->route('admin.solutions.index')->with('success', 'Solution deleted successfully.');
    }
}
