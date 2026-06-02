<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductPage;
use App\Models\Project;
use Illuminate\Http\Request;

class ProductPageController extends Controller
{
    public function index(Project $project)
    {
        $pages = $project->productPages()->orderBy('sort_order')->get();

        return view('admin.product-pages.index', compact('project', 'pages'));
    }

    public function create(Project $project)
    {
        return view('admin.product-pages.create', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'type'         => 'required|in:setup,deploy,custom',
            'is_published' => 'boolean',
            'sort_order'   => 'integer',
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['content'] = $this->buildContent($request);

        $project->productPages()->create($validated);

        return redirect()->route('admin.projects.product-pages.index', $project)
            ->with('success', 'Product page created successfully.');
    }

    public function edit(Project $project, ProductPage $productPage)
    {
        return view('admin.product-pages.edit', compact('project', 'productPage'));
    }

    public function update(Request $request, Project $project, ProductPage $productPage)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'type'         => 'required|in:setup,deploy,custom',
            'is_published' => 'boolean',
            'sort_order'   => 'integer',
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['content'] = $this->buildContent($request);

        $productPage->update($validated);

        return redirect()->route('admin.projects.product-pages.index', $project)
            ->with('success', 'Product page updated successfully.');
    }

    public function destroy(Project $project, ProductPage $productPage)
    {
        $productPage->delete();

        return redirect()->route('admin.projects.product-pages.index', $project)
            ->with('success', 'Product page deleted successfully.');
    }

    private function buildContent(Request $request): array
    {
        $type = $request->input('type');

        return match ($type) {
            'setup' => [
                'heading' => $request->input('content_heading', ''),
                'message' => $request->input('content_message', ''),
                'options' => collect($request->input('options', []))
                    ->filter(fn($o) => !empty($o['title']))
                    ->map(fn($o) => [
                        'title'        => $o['title'] ?? '',
                        'description'  => $o['description'] ?? '',
                        'icon'         => $o['icon'] ?? 'star',
                        'button_label' => $o['button_label'] ?? '',
                        'button_url'   => $o['button_url'] ?? '',
                        'recommended'  => isset($o['recommended']) && $o['recommended'],
                    ])
                    ->values()
                    ->toArray(),
            ],
            'deploy' => [
                'heading'         => $request->input('content_heading', ''),
                'steps'           => collect($request->input('steps', []))
                    ->filter(fn($s) => !empty($s['title']))
                    ->map(fn($s) => [
                        'title'        => $s['title'] ?? '',
                        'description'  => $s['description'] ?? '',
                        'items'        => $s['items'] ?? '',
                        'button_label' => $s['button_label'] ?? '',
                        'button_url'   => $s['button_url'] ?? '',
                        'guidance'     => $s['guidance'] ?? '',
                        'note'         => $s['note'] ?? '',
                    ])
                    ->values()
                    ->toArray(),
                'support_heading' => $request->input('support_heading', ''),
                'support_message' => $request->input('support_message', ''),
                'support_url'     => $request->input('support_url', ''),
            ],
            default => [
                'markdown' => $request->input('content_markdown', ''),
            ],
        };
    }
}
