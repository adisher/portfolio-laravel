<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::latest()
            ->paginate(15);

        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return view('admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'             => 'required|in:client,colleague,user',
            'client_name'      => 'required|string|max:255',
            'client_position'  => 'nullable|string|max:255',
            'client_company'   => 'nullable|string|max:255',
            'client_role'      => 'nullable|string|max:255',
            'role_description' => 'nullable|string',
            'client_website'   => 'nullable|url|max:255',
            'client_linkedin'  => 'nullable|url|max:255',
            'testimonial'      => 'required|string',
            'client_image'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rating'           => 'required|integer|min:1|max:5',
            'country_code'     => 'nullable|string|size:2',
            'country_name'     => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:255',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'is_featured'      => 'boolean',
            'is_published'     => 'boolean',
            'sort_order'       => 'nullable|integer',
        ]);

        if ($request->hasFile('client_image')) {
            $validated['client_image'] = $request->file('client_image')->store('testimonials', 'public');
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        Testimonial::create($validated);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created successfully');
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'type'             => 'required|in:client,colleague,user',
            'client_name'      => 'required|string|max:255',
            'client_position'  => 'nullable|string|max:255',
            'client_company'   => 'nullable|string|max:255',
            'client_role'      => 'nullable|string|max:255',
            'role_description' => 'nullable|string',
            'client_website'   => 'nullable|url|max:255',
            'client_linkedin'  => 'nullable|url|max:255',
            'testimonial'      => 'required|string',
            'client_image'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rating'           => 'required|integer|min:1|max:5',
            'country_code'     => 'nullable|string|size:2',
            'country_name'     => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:255',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'is_featured'      => 'boolean',
            'is_published'     => 'boolean',
            'sort_order'       => 'nullable|integer',
        ]);

        if ($request->hasFile('client_image')) {
            if ($testimonial->client_image) {
                Storage::disk('public')->delete($testimonial->client_image);
            }
            $validated['client_image'] = $request->file('client_image')->store('testimonials', 'public');
        }

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        $testimonial->update($validated);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated successfully');
    }

    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->client_image) {
            Storage::disk('public')->delete($testimonial->client_image);
        }

        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial deleted successfully');
    }
}
