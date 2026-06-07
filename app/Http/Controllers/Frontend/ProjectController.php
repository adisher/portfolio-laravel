<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::published()
            ->with(['category', 'tags'])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $categories = Category::active()
            ->forProjects()
            ->withCount(['projects' => function ($query) {
                $query->where('is_published', true);
            }])
            ->having('projects_count', '>', 0)
            ->get();

        return view('frontend.portfolio', compact('projects', 'categories'));
    }

    public function show($slug)
    {
        // Own products have their own dedicated page, redirect there
        $ownProduct = Project::published()
            ->ownProducts()
            ->where('slug', $slug)
            ->first();

        if ($ownProduct) {
            return redirect()->route('products.show', $slug);
        }

        $project = Project::published()
            ->notOwnProducts()
            ->with(['category', 'tags', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProjects = Project::published()
            ->notOwnProducts()
            ->where('category_id', $project->category_id)
            ->where('id', '!=', $project->id)
            ->take(3)
            ->get();

        return view('frontend.project-detail', compact('project', 'relatedProjects'));
    }

    public function category($slug)
    {
        $category = Category::active()->forProjects()->where('slug', $slug)->firstOrFail();

        $projects = Project::published()
            ->notOwnProducts()
            ->where('category_id', $category->id)
            ->with(['category', 'tags'])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get other categories for the "Explore Other Categories" section
        $relatedCategories = Category::active()
            ->forProjects()
            ->where('id', '!=', $category->id)
            ->withCount(['projects' => function ($query) {
                $query->where('is_published', true);
            }])
            ->having('projects_count', '>', 0)
            ->take(6)
            ->get();

        return view('frontend.portfolio-category', compact('category', 'projects', 'relatedCategories'));
    }
}
