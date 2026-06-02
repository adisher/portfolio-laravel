<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function edit()
    {
        $data = [
            'hero_bio'      => Setting::where('key', 'about_hero_bio')->first(),
            'resume_url'    => Setting::where('key', 'about_resume_url')->first(),
            'personal_bio'  => Setting::where('key', 'about_personal_bio')->first(),
            'interests'     => Setting::where('key', 'about_interests')->first(),
            'stats'         => Setting::where('key', 'about_stats')->first(),
            'skills'        => Setting::where('key', 'about_skills')->first(),
            'experience'    => Setting::where('key', 'about_experience')->first(),
            'values'        => Setting::where('key', 'about_values')->first(),
        ];

        return view('admin.about.edit', compact('data'));
    }

    public function update(Request $request)
    {
        // Simple text fields
        Setting::set('about_hero_bio',     $request->input('hero_bio', ''),     'text');
        Setting::set('about_resume_url',   $request->input('resume_url', ''),   'url');
        Setting::set('about_personal_bio', $request->input('personal_bio', ''), 'text');
        Setting::set('about_interests',    $request->input('interests', ''),    'string');

        // Stats: array of {value, label, color}
        $stats = [];
        foreach ($request->input('stats', []) as $stat) {
            if (!empty($stat['label'])) {
                $stats[] = [
                    'value' => $stat['value'] ?? '',
                    'label' => $stat['label'],
                    'color' => $stat['color'] ?? 'teal',
                ];
            }
        }
        Setting::set('about_stats', json_encode($stats), 'array');

        // Skills: array of {title, description, color, tags[]}
        $skills = [];
        foreach ($request->input('skills', []) as $skill) {
            if (!empty($skill['title'])) {
                $rawTags = $skill['tags'] ?? '';
                $tags = is_array($rawTags)
                    ? $rawTags
                    : array_filter(array_map('trim', explode(',', $rawTags)));
                $skills[] = [
                    'title'       => $skill['title'],
                    'description' => $skill['description'] ?? '',
                    'color'       => $skill['color'] ?? 'teal',
                    'tags'        => array_values($tags),
                ];
            }
        }
        Setting::set('about_skills', json_encode($skills), 'array');

        // Experience: array of {period, title, company, description, color, tags[]}
        $experience = [];
        foreach ($request->input('experience', []) as $exp) {
            if (!empty($exp['title'])) {
                $rawTags = $exp['tags'] ?? '';
                $tags = is_array($rawTags)
                    ? $rawTags
                    : array_filter(array_map('trim', explode(',', $rawTags)));
                $experience[] = [
                    'period'      => $exp['period'] ?? '',
                    'title'       => $exp['title'],
                    'company'     => $exp['company'] ?? '',
                    'description' => $exp['description'] ?? '',
                    'color'       => $exp['color'] ?? 'teal',
                    'tags'        => array_values($tags),
                ];
            }
        }
        Setting::set('about_experience', json_encode($experience), 'array');

        // Values: array of {title, description, color}
        $values = [];
        foreach ($request->input('values', []) as $val) {
            if (!empty($val['title'])) {
                $values[] = [
                    'title'       => $val['title'],
                    'description' => $val['description'] ?? '',
                    'color'       => $val['color'] ?? 'teal',
                ];
            }
        }
        Setting::set('about_values', json_encode($values), 'array');

        return redirect()->route('admin.about.edit')
            ->with('success', 'About page updated successfully.');
    }
}
