<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Google Calendar booking page ("Book a call"), managed from Admin > Settings.
 *
 * Accepts either the short share link (https://calendar.app.google/...) or
 * the full https://calendar.google.com/calendar/appointments/schedules/...
 * URL. Only the full schedules URL accepts ?gv=true and can be framed; short
 * links redirect to a page Google refuses to embed, so those are only ever
 * opened in a new tab.
 */
class BookingLink
{
    public static function url(): ?string
    {
        $url = trim((string) Setting::get('booking_url'));

        if ($url === '') {
            return null;
        }

        // Strip gv=true so the plain link opens the full-page booking view.
        $url = preg_replace('/([?&])gv=true(&|$)/', '$1', $url);

        return rtrim($url, '?&');
    }

    public static function embedUrl(): ?string
    {
        $url = static::url();

        if (!$url || !str_contains($url, '/calendar/appointments/schedules/')) {
            return null;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'gv=true';
    }

    /**
     * Where a "Book a call" button should point: the inline scheduler on the
     * contact page when it can be embedded, otherwise Google's own page.
     */
    public static function href(): ?string
    {
        if (static::embedUrl()) {
            return route('contact') . '#book-a-call';
        }

        return static::url();
    }

    public static function opensExternally(): bool
    {
        return static::url() !== null && static::embedUrl() === null;
    }
}
