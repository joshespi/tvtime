@props(['active'])

<a {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full px-2 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 focus-visible:outline-indigo-500 dark:text-gray-300 dark:hover:bg-gray-700 lg:px-4 aria-[current=page]:bg-indigo-50 aria-[current=page]:text-indigo-700 dark:aria-[current=page]:bg-indigo-900 dark:aria-[current=page]:text-indigo-100']) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
