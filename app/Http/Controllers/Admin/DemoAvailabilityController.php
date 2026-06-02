<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoAvailability;
use App\Models\DemoBlockedDate;
use Illuminate\Http\Request;

class DemoAvailabilityController extends Controller
{
    public function edit()
    {
        $availability = DemoAvailability::settings();
        $blockedDates = DemoBlockedDate::orderBy('blocked_date')->get();

        return view('admin.demo-availability.edit', compact('availability', 'blockedDates'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'days_of_week'   => 'required|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
            'slot_duration'  => 'required|integer|in:15,30,45,60',
            'buffer_minutes' => 'required|integer|min:0|max:60',
            'timezone'       => 'required|string|max:50',
            'max_per_day'    => 'required|integer|min:1|max:20',
            'is_active'      => 'boolean',
        ]);

        $validated['is_active']      = $request->boolean('is_active');
        $validated['days_of_week']   = array_map('intval', $validated['days_of_week']);
        $validated['start_time']     = $validated['start_time'] . ':00';
        $validated['end_time']       = $validated['end_time'] . ':00';

        DemoAvailability::settings()->update($validated);

        return back()->with('success', 'Availability settings saved.');
    }

    public function addBlockedDate(Request $request)
    {
        $validated = $request->validate([
            'blocked_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'nullable|date_format:H:i',
            'end_time'     => 'nullable|date_format:H:i|after:start_time',
            'reason'       => 'nullable|string|max:200',
        ]);

        // Don't allow duplicates
        $exists = DemoBlockedDate::where('blocked_date', $validated['blocked_date'])
            ->whereNull('start_time')
            ->exists();

        if (!$exists) {
            DemoBlockedDate::create($validated);
        }

        return back()->with('success', 'Date blocked.');
    }

    public function removeBlockedDate(DemoBlockedDate $blockedDate)
    {
        $blockedDate->delete();

        return back()->with('success', 'Block removed.');
    }
}
