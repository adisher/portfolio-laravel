<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Project;
use App\Models\Skill;
use App\Models\Solution;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProjects = Project::published()->featured()->where('is_own_product', false)->with('category')->latest()->take(6)->get();
        $ownProducts      = Project::published()->featured()->ownProducts()->with('category')->orderBy('sort_order')->get();
        $featuredSkills   = Skill::featured()->orderBy('sort_order')->get();
        $latestPosts      = BlogPost::published()->latest('published_at')->take(3)->get();
        $testimonials     = Testimonial::published()->featured()->orderBy('sort_order')->get();
        $solutions        = Solution::published()->featured()->orderBy('sort_order')->get();
        $projectCount     = Project::published()->count();

        return view('frontend.home', compact(
            'featuredProjects',
            'ownProducts',
            'featuredSkills',
            'latestPosts',
            'testimonials',
            'solutions',
            'projectCount'
        ));
    }
}
