<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoBooking;
use Illuminate\Http\Request;

class DemoBookingController extends Controller
{
    public function index(Request $request)
    {
        $tab   = $request->get('tab', 'upcoming');
        $search = $request->get('search');

        $query = DemoBooking::with('project')->orderBy('scheduled_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $query = match ($tab) {
            'past'      => $query->where('scheduled_at', '<', now())->whereIn('status', ['completed', 'no_show'])->reorder()->orderByDesc('scheduled_at'),
            'cancelled' => $query->where('status', 'cancelled')->reorder()->orderByDesc('scheduled_at'),
            'all'       => $query->reorder()->orderByDesc('scheduled_at'),
            default     => $query->where('scheduled_at', '>', now())->where('status', 'confirmed'),
        };

        $bookings       = $query->paginate(20)->withQueryString();
        $upcomingCount  = DemoBooking::upcoming()->count();

        return view('admin.demo-bookings.index', compact('bookings', 'tab', 'search', 'upcomingCount'));
    }

    public function show(DemoBooking $demoBooking)
    {
        $demoBooking->load('project');

        return view('admin.demo-bookings.show', compact('demoBooking'));
    }

    public function updateStatus(Request $request, DemoBooking $demoBooking)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed,no_show',
        ]);

        $demoBooking->update(['status' => $request->status]);

        return back()->with('success', 'Booking status updated.');
    }

    public function updateNotes(Request $request, DemoBooking $demoBooking)
    {
        $request->validate(['admin_notes' => 'nullable|string|max:2000']);

        $demoBooking->update(['admin_notes' => $request->admin_notes]);

        return back()->with('success', 'Notes saved.');
    }

    public function destroy(DemoBooking $demoBooking)
    {
        $demoBooking->delete();

        return redirect()->route('admin.demo-bookings.index')->with('success', 'Booking deleted.');
    }
}
