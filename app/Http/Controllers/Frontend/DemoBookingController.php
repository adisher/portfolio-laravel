<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\DemoBookingConfirmation;
use App\Mail\DemoBookingNotification;
use App\Models\DemoAvailability;
use App\Models\DemoBooking;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DemoBookingController extends Controller
{
    /**
     * GET /api/demo/slots?month=YYYY-MM&product_slug=xxx
     * Returns available time slots grouped by date.
     */
    public function getSlots(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $availability = DemoAvailability::settings();

        if (!$availability->is_active) {
            return response()->json([]);
        }

        $slots = $availability->getSlotsForMonth($request->month);

        return response()->json($slots);
    }

    /**
     * POST /api/demo/book
     * Creates a confirmed booking and dispatches emails.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|max:255',
            'company'       => 'nullable|string|max:150',
            'plan_interest' => 'nullable|string|max:100',
            'message'       => 'nullable|string|max:1000',
            'scheduled_at'  => 'required|string', // "YYYY-MM-DD HH:mm" in admin TZ
            'product_slug'  => 'nullable|string|max:100',
        ]);

        $availability = DemoAvailability::settings();

        if (!$availability->is_active) {
            return response()->json(['success' => false, 'message' => 'Demo bookings are currently unavailable.'], 422);
        }

        // Parse the submitted datetime in admin timezone and convert to UTC
        $tz = $availability->timezone;
        try {
            $scheduledAt = Carbon::parse($validated['scheduled_at'], $tz)->utc();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid date/time.'], 422);
        }

        // Reject past slots
        if ($scheduledAt->isPast()) {
            return response()->json(['success' => false, 'message' => 'That time slot is no longer available.'], 422);
        }

        // Verify the slot is still available (re-check on submit)
        $dateInTz    = Carbon::parse($scheduledAt)->setTimezone($tz);
        $availSlots  = $availability->getSlotsForDate($dateInTz);
        $requestedTime = $dateInTz->format('H:i');

        if (!in_array($requestedTime, $availSlots)) {
            return response()->json(['success' => false, 'message' => 'That time slot is no longer available. Please choose another.'], 422);
        }

        // Resolve product
        $project = null;
        if (!empty($validated['product_slug'])) {
            $project = Project::where('slug', $validated['product_slug'])->first();
        }

        $booking = DemoBooking::create([
            'project_id'      => $project?->id,
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'company'         => $validated['company'] ?? null,
            'plan_interest'   => $validated['plan_interest'] ?? null,
            'message'         => $validated['message'] ?? null,
            'scheduled_at'    => $scheduledAt,
            'duration_minutes'=> $availability->slot_duration,
            'status'          => 'confirmed',
        ]);

        // Send emails
        try {
            Mail::to($booking->email)->send(new DemoBookingConfirmation($booking));
            Mail::to(config('mail.from.address'))->send(new DemoBookingNotification($booking));
        } catch (\Exception $e) {
            // Don't fail the booking if email fails
            \Log::error('Demo booking email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'             => true,
            'booking_id'          => $booking->id,
            'scheduled_formatted' => $booking->scheduledAtFormatted(),
        ]);
    }

    /**
     * GET /demo/cancel/{token}
     * Cancels a booking via the token in the confirmation email.
     */
    public function cancel(string $token)
    {
        $booking = DemoBooking::where('cancellation_token', $token)->first();

        if (!$booking) {
            return view('frontend.demo-cancel', ['status' => 'not_found']);
        }

        if ($booking->status === 'cancelled') {
            return view('frontend.demo-cancel', ['status' => 'already_cancelled', 'booking' => $booking]);
        }

        if ($booking->scheduled_at->isPast()) {
            return view('frontend.demo-cancel', ['status' => 'past', 'booking' => $booking]);
        }

        $booking->update(['status' => 'cancelled']);

        return view('frontend.demo-cancel', ['status' => 'success', 'booking' => $booking]);
    }
}
