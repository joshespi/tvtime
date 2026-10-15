@props(['type'])

<span {{ $attributes->merge(['class' => 'align-middle inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide '.\App\Support\MediaTypeStyle::classes($type)]) }}>
    {{ \App\Support\MediaTypeStyle::label($type) }}
</span>
