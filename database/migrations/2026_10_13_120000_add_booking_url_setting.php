<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('settings')->where('key', 'booking_url')->exists()) {
            return;
        }

        DB::table('settings')->insert([
            'key'         => 'booking_url',
            'value'       => null,
            'type'        => 'url',
            'group'       => 'contact',
            'label'       => 'Booking Page URL',
            'description' => 'Google Calendar booking page. Paste the "Website embed > Inline" src URL (calendar.google.com/calendar/appointments/schedules/...) to show the scheduler on the contact page, or the short calendar.app.google link to open it in a new tab. Leave empty to hide all "Book a call" buttons.',
            'sort_order'  => 4,
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        Cache::forget('all_settings');
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'booking_url')->delete();
        Cache::forget('setting_booking_url');
        Cache::forget('all_settings');
    }
};
