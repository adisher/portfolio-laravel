<?php

namespace App\Support;

/**
 * Small text-normalisation helpers shared across the app.
 */
class Text
{
    /**
     * Remove em dashes (and the horizontal bar) from copy, per the house style
     * rule: no em dashes anywhere in titles, copy, captions, or content.
     *
     * Heuristic (in order):
     *   - A dash used as a spaced separator (a dash with a space on each side)
     *     becomes ", ".
     *   - A dash sitting tight between two word characters becomes ", ".
     *   - Anything left (a standalone, quoted, or tag-adjacent dash, e.g. an
     *     empty-value placeholder) collapses to a plain hyphen.
     *   - Duplicated punctuation the swap creates (", ." into ".") is tidied.
     *
     * en dashes (used for numeric ranges like 5 to 10) are left untouched.
     */
    public static function stripEmDashes(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        // Spaced separator (a dash with a space on each side) becomes a comma.
        $text = preg_replace('/\s+[\x{2014}\x{2015}]\s+/u', ', ', $text);

        // Tight between word characters becomes a comma. Twice for chained dashes.
        $tight = '/(\w)[\x{2014}\x{2015}](\w)/u';
        $text = preg_replace($tight, '$1, $2', $text);
        $text = preg_replace($tight, '$1, $2', $text);

        // Anything left (standalone / quoted / placeholder) → hyphen.
        $text = preg_replace('/[\x{2014}\x{2015}]/u', '-', $text);

        // Tidy punctuation collisions and doubled spaces the swap may create.
        $text = preg_replace('/,\s*([,.;:!?])/u', '$1', $text);
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);

        return $text;
    }
}
