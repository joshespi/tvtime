@props(['variant' => 'secondary', 'size' => 'md', 'href' => null])

@php
    $classes = \App\Support\ButtonStyle::classes($variant, $size);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
