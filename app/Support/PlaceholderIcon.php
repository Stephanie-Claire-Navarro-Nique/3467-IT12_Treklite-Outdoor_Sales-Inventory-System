<?php

namespace App\Support;

/**
 * Black-and-white line-art icons shown when a product has no photo.
 * Picks the icon from the category name (keyword match, case-insensitive); unknown categories get a generic picture.
 * Inline SVG: no files, no network, instant.
 */
class PlaceholderIcon
{
    /** keyword pattern => icon key (first match wins, so keep specific ones first) */
    private const KEYWORDS = [
        'cap|hat|beanie|visor|headwear'                         => 'cap',
        'slipper|sandal|flip|footwear'                          => 'slipper',
        'shoe|boot|sneaker'                                     => 'shoe',
        'apparel|cloth|shirt|jacket|pant|short|wear|top|jersey' => 'apparel',
        'bag|pack|pouch|luggage'                                => 'bag',
        'tent|shelter|tarp|hammock'                             => 'tent',
        'sleep|mat|pillow'                                      => 'sleeping',
        'cook|stove|kitchen|pot|food'                           => 'cooking',
        'light|lamp|lantern|torch|flash'                        => 'light',
        'bottle|drink|hydration|flask|tumbler'                  => 'bottle',
        'textile|towel|fabric|blanket'                          => 'towel',
        'accessor|pole|compass|gear|tool|knife'                 => 'compass',
    ];

    private const SLIPPER = '<path d="M20 9c-8 0-10 11-8 21 1 10 2 21 8 24 6 2 8-5 7-15-1-12 1-30-7-30z"/><path d="M20 22l-6 12M20 22l7 10" stroke-width="4"/>';

    /** Shapes drawn in a 64x64 box with a 2.5 black stroke and no fill. */
    private const SHAPES = [
        'cap'      => '<path d="M13 38C12 22 24 14 37 14c11 0 18 11 17 24"/><path d="M13 38l28 5c8 0 13-2 13-5"/><path d="M13 38c-6 0-9 4-7 8 3 3 9 1 13 1 9 2 16 0 22-4"/><path d="M31 15c-6 7-8 17-5 26M39 14c4 8 5 18 2 29"/><circle cx="36" cy="13" r="2.2"/>',
        'shoe'     => '<path d="M9 16h14v11c4 6 16 6 23 12 7 2 10 4 10 9v3H9z"/><path d="M9 44h47M23 27l4-4M29 31l4-4"/>',
        'apparel'  => '<path d="M22 10L9 18l6 11 6-4v29h22V25l6 4 6-11-13-8c-2 6-18 6-20 0z"/>',
        'bag'      => '<path d="M20 22c0-9 24-9 24 0l2 31c0 2-2 3-4 3H22c-2 0-4-1-4-3z"/><path d="M27 14c0-7 10-7 10 0M22 38h20v16H22zM24 30h16"/>',
        'tent'     => '<path d="M6 52L32 12l26 40z"/><path d="M23 52l9-22 9 22M3 52h58M32 12v18"/>',
        'sleeping' => '<path d="M24 8c-8 0-10 8-8 16l2 26c0 6 6 8 14 8s14-2 14-8l2-26c2-8 0-16-8-16z"/><path d="M32 14v44M22 20h20"/>',
        'cooking'  => '<path d="M15 28h34v18c0 4-3 6-6 6H21c-3 0-6-2-6-6z"/><path d="M13 28h38M27 24h10M15 33H7M49 33h8M22 58h20"/>',
        'light'    => '<circle cx="32" cy="32" r="12"/><circle cx="32" cy="32" r="5"/><path d="M4 30h16M44 30h16M32 8v8M15 11l4 6M49 11l-4 6"/>',
        'bottle'   => '<path d="M26 7h12v7H26z"/><path d="M24 14h16c2 4 4 8 4 14v26c0 2-2 4-4 4H24c-2 0-4-2-4-4V28c0-6 2-10 4-14z"/><path d="M20 32h24M20 44h24"/>',
        'towel'    => '<path d="M9 20h46v26H9z"/><path d="M9 27h46M9 39h46M9 46v6M17 46v6M25 46v6M33 46v6M41 46v6M49 46v6"/>',
        'compass'  => '<circle cx="32" cy="32" r="22"/><path d="M32 14v4M32 46v4M14 32h4M46 32h4"/><path d="M40 24l-5 11-11 5 5-11z"/>',
        'default'  => '<rect x="9" y="13" width="46" height="38" rx="4"/><path d="M9 43l14-14 11 11 7-7 14 14"/><circle cx="43" cy="25" r="4"/>',
    ];

    public static function key(?string $category): string
    {
        $c = mb_strtolower(trim((string) $category));

        if ($c !== '') {
            foreach (self::KEYWORDS as $pattern => $key) {
                if (preg_match('/(' . $pattern . ')/u', $c)) {
                    return $key;
                }
            }
        }

        return 'default';
    }

    /** Ready-to-print <svg> (it fills its box and takes the text colour). */
    public static function svg(?string $category): string
    {
        $key   = self::key($category);
        $shape = $key === 'slipper'
            ? '<g>' . self::SLIPPER . '</g><g transform="translate(24 0)">' . self::SLIPPER . '</g>'
            : self::SHAPES[$key];

        return '<svg class="ph-svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.5" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $shape . '</svg>';
    }
}
