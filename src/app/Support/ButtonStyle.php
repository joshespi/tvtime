<?php

namespace App\Support;

/**
 * The tailwind button palette, in one place.
 *
 * <x-action-button> covers the normal case where the variant is known while
 * rendering. It lives here rather than inside that component because the catalog
 * track button picks its variant client-side (Alpine swaps to `success` once the
 * title is tracked) and so needs the class strings as values — previously it
 * carried its own copy, which is exactly the drift this prevents.
 */
class ButtonStyle
{
    private const BASE = 'inline-flex items-center justify-center font-medium rounded-md transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed';

    private const VARIANTS = [
        'secondary' => 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600',
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500',
        'success' => 'border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
        'danger' => 'bg-red-600 text-white hover:bg-red-500 focus-visible:ring-red-500',
        'dark' => 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-white',
    ];

    /** Each size owns its whole box, so none has to out-specify another. */
    private const SIZES = [
        // Roughly 40px tall on phones for a thumb-sized target, compact from `sm` up.
        'md' => 'px-3 py-2.5 sm:py-2 text-sm sm:text-xs',
        'lg' => 'px-5 py-3 text-sm',
    ];

    /** Everything but the colours, for a caller that swaps its variant client-side. */
    public static function base(string $size = 'md'): string
    {
        return self::BASE.' '.(self::SIZES[$size] ?? self::SIZES['md']);
    }

    public static function variant(string $variant): string
    {
        return self::VARIANTS[$variant] ?? self::VARIANTS['secondary'];
    }

    public static function classes(string $variant, string $size = 'md'): string
    {
        return self::base($size).' '.self::variant($variant);
    }
}
