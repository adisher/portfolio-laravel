<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class DemoAvailability extends Model
{
    protected $table = 'demo_availability';

    protected $fillable = [
        'days_of_week', 'start_time', 'end_time',
        'slot_duration', 'buffer_minutes', 'timezone',
        'max_per_day', 'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'is_active'    => 'boolean',
    ];

    /**
     * Always return or create the singleton settings row.
     */
    public static function settings(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'days_of_week'   => [1, 2, 3, 4, 5],
                'start_time'     => '10:00:00',
                'end_time'       => '17:00:00',
                'slot_duration'  => 30,
                'buffer_minutes' => 15,
                'timezone'       => 'Asia/Karachi',
                'max_per_day'    => 4,
                'is_active'      => true,
            ]
        );
    }

    /**
     * Get available time slots for a given calendar month.
     *
     * Returns: [ 'YYYY-MM-DD' => ['10:00', '10:30', ...], ... ]
     */
    public function getSlotsForMonth(string $yearMonth): array
    {
        if (!$this->is_active) {
            return [];
        }

        $tz    = $this->timezone;
        $now   = Carbon::now($tz);
        $start = Carbon::parse($yearMonth . '-01', $tz)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        // Don't return past months
        if ($end->lt($now)) {
            return [];
        }

        $result = [];

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $slots = $this->getSlotsForDate($date, $now);
            if (!empty($slots)) {
                $result[$date->format('Y-m-d')] = $slots;
            }
        }

        return $result;
    }

    /**
     * Get available time slots for a specific date (Carbon, in admin timezone).
     *
     * Returns: ['10:00', '10:30', ...]
     */
    public function getSlotsForDate(Carbon $date, ?Carbon $now = null): array
    {
        $tz  = $this->timezone;
        $now = $now ?? Carbon::now($tz);

        // Check if weekday is active (Carbon: Mon=1, Sun=7)
        $dayOfWeek = (int) $date->format('N'); // 1=Mon … 7=Sun
        if (!in_array($dayOfWeek, $this->days_of_week ?? [])) {
            return [];
        }

        // Check for full-day block
        $fullBlock = DemoBlockedDate::where('blocked_date', $date->format('Y-m-d'))
            ->whereNull('start_time')
            ->exists();

        if ($fullBlock) {
            return [];
        }

        // Partial blocks for this date
        $partialBlocks = DemoBlockedDate::where('blocked_date', $date->format('Y-m-d'))
            ->whereNotNull('start_time')
            ->get();

        // Booked slots for this date (confirmed bookings)
        $bookedTimes = DemoBooking::whereDate('scheduled_at', $date->format('Y-m-d'))
            ->whereIn('status', ['confirmed'])
            ->pluck('scheduled_at')
            ->map(fn($dt) => Carbon::parse($dt)->setTimezone($tz)->format('H:i'))
            ->toArray();

        // Check max_per_day
        if (count($bookedTimes) >= $this->max_per_day) {
            return [];
        }

        // Generate all slots
        $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $this->start_time, $tz);
        $slotEnd   = Carbon::parse($date->format('Y-m-d') . ' ' . $this->end_time, $tz);

        $available = [];
        $current   = $slotStart->copy();

        while ($current->copy()->addMinutes($this->slot_duration)->lte($slotEnd)) {
            $timeStr = $current->format('H:i');

            // Skip past slots (require at least 1 hour lead time)
            if ($current->lt($now->copy()->addHour())) {
                $current->addMinutes($this->slot_duration);
                continue;
            }

            // Skip blocked time ranges
            $blocked = false;
            foreach ($partialBlocks as $block) {
                $blockStart = Carbon::parse($date->format('Y-m-d') . ' ' . $block->start_time, $tz);
                $blockEnd   = Carbon::parse($date->format('Y-m-d') . ' ' . $block->end_time, $tz);
                if ($current->gte($blockStart) && $current->lt($blockEnd)) {
                    $blocked = true;
                    break;
                }
            }
            if ($blocked) {
                $current->addMinutes($this->slot_duration);
                continue;
            }

            // Skip already booked + buffer zone
            $slotConflict = false;
            foreach ($bookedTimes as $booked) {
                $bookedCarbon = Carbon::parse($date->format('Y-m-d') . ' ' . $booked, $tz);
                $diff = abs($current->diffInMinutes($bookedCarbon));
                if ($diff < ($this->slot_duration + $this->buffer_minutes)) {
                    $slotConflict = true;
                    break;
                }
            }

            if (!$slotConflict) {
                $available[] = $timeStr;
            }

            $current->addMinutes($this->slot_duration);
        }

        return $available;
    }
}
