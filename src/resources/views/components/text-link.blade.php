@props(['href', 'size' => 'text-sm'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => $size.' text-indigo-600 dark:text-indigo-400 hover:underline']) }}>{{ $slot }}</a>
