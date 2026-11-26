@props(['type'])

<span {{ $attributes->merge(['class' => 'align-middle '.\App\Support\MediaTypeStyle::badgeClasses($type)]) }}>
    {{ \App\Support\MediaTypeStyle::label($type) }}
</span>
