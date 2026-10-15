@props(['label', 'value', 'caption' => null])

<x-card>
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</p>
    @if ($caption)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $caption }}</p>
    @endif
</x-card>
