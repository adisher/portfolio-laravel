<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['category'])
            ->latest()
            ->paginate(15);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $categories = Category::active()->get();
        $tags       = Tag::all();

        return view('admin.projects.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description'       => 'required|string',
            'featured_image'    => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'technologies'      => 'nullable|string',
            'project_url'       => 'nullable|url',
            'github_url'        => 'nullable|url',
            'client_name'       => 'nullable|string|max:255',
            'project_date'      => 'required|date',
            'status'            => 'required|in:completed,in_progress,on_hold',
            'category_id'       => 'required|exists:categories,id',
            'tags'              => 'nullable|array',
            'is_featured'       => 'boolean',
            'is_published'      => 'boolean',
            'is_own_product'    => 'boolean',
            'primary_metric_value' => 'nullable|string|max:50',
            'primary_metric_label' => 'nullable|string|max:100',
            'metrics'           => 'nullable|json',
            'challenge'         => 'nullable|string',
            'solution'          => 'nullable|string',
            'results'           => 'nullable|string',
            'role'              => 'nullable|string|max:100',
            'duration'          => 'nullable|string|max:50',
            'color_primary'     => 'nullable|string|max:7',
            'color_secondary'   => 'nullable|string|max:7',
        ]);

        if ($request->filled('technologies')) {
            $validated['technologies'] = array_filter(
                array_map('trim', explode("\n", $request->technologies))
            );
        } else {
            $validated['technologies'] = [];
        }

        // Parse metrics JSON if provided
        if ($request->filled('metrics')) {
            $validated['metrics'] = json_decode($request->metrics, true);
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('projects', 'public');
        }

        $validated['is_own_product'] = $request->boolean('is_own_product');
        $validated['is_featured']    = $request->boolean('is_featured');
        $validated['is_published']   = $request->boolean('is_published');

        $project = Project::create($validated);

        if ($request->has('tags')) {
            $project->tags()->sync($request->tags);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully');
    }

    public function show(Project $project)
    {
        return view('admin.projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $categories = Category::active()->get();
        $tags       = Tag::all();
        $project->load('images');

        return view('admin.projects.edit', compact('project', 'categories', 'tags'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description'       => 'required|string',
            'featured_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'technologies'      => 'nullable|string',
            'project_url'       => 'nullable|url',
            'github_url'        => 'nullable|url',
            'client_name'       => 'nullable|string|max:255',
            'project_date'      => 'required|date',
            'status'            => 'required|in:completed,in_progress,on_hold',
            'category_id'       => 'required|exists:categories,id',
            'tags'              => 'nullable|array',
            'is_featured'       => 'boolean',
            'is_published'      => 'boolean',
            'is_own_product'    => 'boolean',
            'primary_metric_value' => 'nullable|string|max:50',
            'primary_metric_label' => 'nullable|string|max:100',
            'metrics'           => 'nullable|json',
            'challenge'         => 'nullable|string',
            'solution'          => 'nullable|string',
            'results'           => 'nullable|string',
            'role'              => 'nullable|string|max:100',
            'duration'          => 'nullable|string|max:50',
            'color_primary'     => 'nullable|string|max:7',
            'color_secondary'   => 'nullable|string|max:7',
        ]);

        if ($request->filled('technologies')) {
            $validated['technologies'] = array_filter(
                array_map('trim', explode("\n", $request->technologies))
            );
        } else {
            $validated['technologies'] = [];
        }

        // Parse metrics JSON if provided
        if ($request->filled('metrics')) {
            $validated['metrics'] = json_decode($request->metrics, true);
        }

        $validated['is_own_product'] = $request->boolean('is_own_product');
        $validated['is_featured']    = $request->boolean('is_featured');
        $validated['is_published']   = $request->boolean('is_published');

        if ($request->hasFile('featured_image')) {
            if ($project->featured_image) {
                Storage::disk('public')->delete($project->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('projects', 'public');
        }

        $project->update($validated);

        if ($request->has('tags')) {
            $project->tags()->sync($request->tags);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully');
    }

    public function destroy(Project $project)
    {
        // Delete featured image
        if ($project->featured_image) {
            Storage::disk('public')->delete($project->featured_image);
        }

        // Delete all gallery images
        foreach ($project->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully');
    }

    /**
     * Upload gallery images for a project.
     */
    public function uploadImages(Request $request, Project $project)
    {
        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $uploaded = [];
        $maxSort  = $project->images()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $file) {
            $path  = $file->store('projects/gallery', 'public');
            $image = $project->images()->create([
                'image_path' => $path,
                'alt_text'   => $project->title,
                'sort_order' => ++$maxSort,
            ]);

            $uploaded[] = [
                'id'        => $image->id,
                'url'       => Storage::url($path),
                'alt_text'  => $image->alt_text,
                'sort_order' => $image->sort_order,
            ];
        }

        return response()->json([
            'success' => true,
            'images'  => $uploaded,
            'message' => count($uploaded) . ' image(s) uploaded successfully.',
        ]);
    }

    /**
     * Show product content editing page.
     */
    public function productContent(Project $project)
    {
        return view('admin.projects.product-content', compact('project'));
    }

    /**
     * Update product_data JSON.
     */
    public function updateProductContent(Request $request, Project $project)
    {
        $productData = [];

        // Features
        if ($request->has('features')) {
            $productData['features'] = collect($request->input('features', []))
                ->filter(fn($f) => !empty($f['title']))
                ->values()
                ->toArray();
        }

        // How it works
        if ($request->has('how_it_works')) {
            $productData['how_it_works'] = collect($request->input('how_it_works', []))
                ->filter(fn($s) => !empty($s['title']))
                ->values()
                ->toArray();
        }

        // Pricing
        if ($request->has('pricing')) {
            $productData['pricing'] = collect($request->input('pricing', []))
                ->filter(fn($t) => !empty($t['name']))
                ->map(function ($tier) {
                    $tier['features'] = array_values(array_filter($tier['features'] ?? []));
                    $tier['highlighted'] = isset($tier['highlighted']) && $tier['highlighted'];
                    $tier['price'] = $tier['price'] ?? '0';
                    return $tier;
                })
                ->values()
                ->toArray();
        }

        // FAQ
        if ($request->has('faq')) {
            $productData['faq'] = collect($request->input('faq', []))
                ->filter(fn($f) => !empty($f['question']))
                ->values()
                ->toArray();
        }

        // CTA
        $productData['cta_type']  = $request->input('cta_type', 'purchase');
        $productData['cta_url']   = $request->input('cta_url', '');
        $productData['cta_label'] = $request->input('cta_label', 'Buy Now');

        $project->update(['product_data' => $productData]);

        return redirect()->route('admin.projects.product-content', $project)
            ->with('success', 'Product content updated successfully.');
    }

    /**
     * Delete a gallery image.
     */
    public function deleteImage(Project $project, ProjectImage $image)
    {
        // Verify the image belongs to this project
        if ($image->project_id !== $project->id) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
        ]);
    }
}
