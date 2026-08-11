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
    public const BASE = 'inline-flex items-center px-3 py-2 text-xs font-medium rounded-md transition';

    private const VARIANTS = [
        'secondary' => 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600',
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500',
        'success' => 'border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    ];

    public static function variant(string $variant): string
    {
        return self::VARIANTS[$variant] ?? self::VARIANTS['secondary'];
    }

    public static function classes(string $variant): string
    {
        return self::BASE.' '.self::variant($variant);
    }
}
