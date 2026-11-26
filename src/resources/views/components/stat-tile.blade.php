@props(['label', 'value', 'caption' => null])

<x-card>
    <p class="eyebrow text-gray-500 dark:text-gray-400">{{ $label }}</p>
    <p class="font-display font-normal mt-2 text-4xl tabular-nums text-gray-900 dark:text-gray-100">{{ $value }}</p>
    @if ($caption)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $caption }}</p>
    @endif
</x-card>
