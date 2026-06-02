<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $settingsGrouped = Setting::where('is_active', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        return view('admin.settings.index', compact('settingsGrouped'));
    }

    public function update(Request $request)
    {
        $settings = Setting::where('is_active', true)->get();
        
        // Build validation rules
        $rules = [];
        foreach ($settings as $setting) {
            $rules[$setting->key] = $this->getValidationRule($setting);
        }

        $validatedData = $request->validate($rules);

        foreach ($validatedData as $key => $value) {
            $setting = $settings->where('key', $key)->first();
            
            if (!$setting) continue;

            // Handle file uploads
            if ($setting->type === 'file' && $request->hasFile($key)) {
                // Delete old file
                if ($setting->value) {
                    Storage::disk('public')->delete($setting->value);
                }
                
                $value = $request->file($key)->store('settings', 'public');
            }
            // Handle boolean values
            elseif ($setting->type === 'boolean') {
                $value = $request->has($key) ? true : false;
            }
            // Handle array values
            elseif ($setting->type === 'array') {
                $value = is_array($value) ? json_encode($value) : $value;
            }

            $setting->update(['value' => $value]);
        }

        // Clear settings cache
        Setting::clearCache();

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    private function getValidationRule($setting)
    {
        $rule = [];

        switch ($setting->type) {
            case 'email':
                $rule[] = 'nullable';
                $rule[] = 'email';
                break;
            case 'url':
                $rule[] = 'nullable';
                $rule[] = 'url';
                break;
            case 'file':
                $rule[] = 'nullable';
                $rule[] = 'image';
                $rule[] = 'mimes:jpeg,png,jpg,gif,svg';
                $rule[] = 'max:2048';
                break;
            case 'boolean':
                $rule[] = 'boolean';
                break;
            case 'integer':
                $rule[] = 'nullable';
                $rule[] = 'integer';
                break;
            default:
                $rule[] = 'nullable';
                $rule[] = 'string';
                if ($setting->key === 'site_name') {
                    $rule[] = 'max:100';
                }
                break;
        }

        return $rule;
    }
}