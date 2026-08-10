@props(['variant' => 'secondary'])

@php
$variants = [
    'secondary' => 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600',
    'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500',
];
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-3 py-2 text-xs font-medium rounded-md transition '.$variants[$variant]]) }}>
    {{ $slot }}
</button>
