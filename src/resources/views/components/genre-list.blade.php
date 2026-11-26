@props(['genres' => []])

@if (! empty($genres))
    <p {{ $attributes->merge(['class' => 'text-xs text-gray-500 dark:text-gray-400 capitalize']) }}>{{ implode(', ', $genres) }}</p>
@endif
