<?php

if (!function_exists('setting')) {
    /**
     * Get setting value
     */
    function setting($key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (!function_exists('settings')) {
    /**
     * Get all settings grouped
     */
    function settings()
    {
        return \App\Models\Setting::getAllGrouped();
    }
}

if (!function_exists('country_flag')) {
    /**
     * Convert ISO 3166-1 alpha-2 country code to flag emoji
     */
    // function country_flag(?string $countryCode): string
    // {
    //     if (!$countryCode || strlen($countryCode) !== 2) {
    //         return '';
    //     }

    //     $countryCode = strtoupper($countryCode);

    //     if ($countryCode === 'UK') {
    //         $countryCode = 'GB';
    //     }


    //     // Convert country code to regional indicator symbols
    //     // A = 127462, B = 127463, etc.
    //     $flag = '';
    //     for ($i = 0; $i < 2; $i++) {
    //         $flag .= mb_chr(ord($countryCode[$i]) - ord('A') + 127462, 'UTF-8');
    //     }

    //     return $flag;
    // }

    function country_flag(?string $countryCode): string
    {
        if (!$countryCode || strlen($countryCode) !== 2) {
            return '';
        }

        $countryCode = strtolower($countryCode);

        if ($countryCode === 'uk') {
            $countryCode = 'gb';
        }

        return "fi fi-{$countryCode}";
    }
}