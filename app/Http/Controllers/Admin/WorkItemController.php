<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Http\Request;

class WorkItemController extends Controller
{
    public function index()
    {
        $workItems = WorkItem::with('project')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.work-items.index', compact('workItems'));
    }

    public function create()
    {
        $projects = Project::orderBy('title')->get(['id', 'title']);
        return view('admin.work-items.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        WorkItem::create($data);

        return redirect()->route('admin.work-items.index')
            ->with('success', 'Work item created.');
    }

    public function show(WorkItem $workItem)
    {
        $workItem->load('project');
        return view('admin.work-items.show', compact('workItem'));
    }

    public function edit(WorkItem $workItem)
    {
        $projects = Project::orderBy('title')->get(['id', 'title']);
        return view('admin.work-items.edit', compact('workItem', 'projects'));
    }

    public function update(Request $request, WorkItem $workItem)
    {
        $data = $this->validateData($request);
        $workItem->update($data);

        return redirect()->route('admin.work-items.index')
            ->with('success', 'Work item updated.');
    }

    public function destroy(WorkItem $workItem)
    {
        $workItem->delete();

        return redirect()->route('admin.work-items.index')
            ->with('success', 'Work item deleted.');
    }

    /**
     * Validate and normalize the form data, cleaning the list fields.
     */
    protected function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:product,service,project,skill',
            'project_id'      => 'nullable|exists:projects,id',
            'active'          => 'nullable|boolean',
            'sort_order'      => 'nullable|integer',
            'tagline'         => 'nullable|string|max:500',
            'target_audience' => 'nullable|string',
            'how_it_helps'    => 'nullable|string',
            'call_to_action'  => 'nullable|string',
            'tech_stack'      => 'nullable|string|max:500',
            'url'             => 'nullable|url|max:500',
            'notes'           => 'nullable|string',
            'pain_points'     => 'nullable|array',
            'objections'      => 'nullable|array',
            'key_outcomes'    => 'nullable|array',
            'proof_links'     => 'nullable|array',
            'differentiators' => 'nullable|array',
            'target_keywords' => 'nullable|array',
            'article_angles'  => 'nullable|array',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['sort_order'] = (int) ($request->input('sort_order', 0));

        foreach (['pain_points', 'objections', 'key_outcomes', 'proof_links', 'differentiators', 'target_keywords', 'article_angles'] as $field) {
            $validated[$field] = $this->cleanList($request->input($field, []));
        }

        return $validated;
    }

    /**
     * Drop empty entries and re-index a list of strings.
     */
    protected function cleanList($items): array
    {
        if (!is_array($items)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', $items), fn($v) => $v !== ''));
    }
}
