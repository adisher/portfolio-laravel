<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;

class VisibilityController extends Controller
{
    public function index()
    {
        $grouped = FeatureFlag::orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $groupOrder = ['page', 'section', 'feature', 'nav', 'banner'];
        $grouped = collect($groupOrder)
            ->mapWithKeys(fn($g) => [$g => $grouped->get($g, collect())]);

        return view('admin.visibility.index', compact('grouped'));
    }

    public function toggle(FeatureFlag $flag)
    {
        $flag->update(['is_enabled' => ! $flag->is_enabled]);

        return response()->json([
            'success'    => true,
            'is_enabled' => $flag->is_enabled,
            'message'    => $flag->label . ' is now ' . ($flag->is_enabled ? 'visible' : 'hidden') . '.',
        ]);
    }

    public function updateMeta(Request $request, FeatureFlag $flag)
    {
        $validated = $request->validate([
            'message'   => 'nullable|string|max:300',
            'link'      => 'nullable|url|max:500',
            'link_text' => 'nullable|string|max:80',
            'color'     => 'nullable|in:teal,sunset,ocean',
        ]);

        $flag->update(['metadata' => array_merge($flag->metadata ?? [], $validated)]);

        return response()->json(['success' => true]);
    }
}
