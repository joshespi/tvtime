@props(['date' => null])

@php
    // The badge owns the whole "is there anything to count down to?" rule — a
    // missing or already-past date renders nothing, so call sites never guard.
    $days = $date ? today()->diffInDays($date->copy()->startOfDay(), false) : null;
@endphp

@if ($days !== null && $days >= 0)
    <span {{ $attributes->merge(['class' => 'shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold leading-none tabular-nums text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300']) }}>
        @if ($days === 0)
            Today
        @elseif ($days === 1)
            Tomorrow
        @else
            in {{ $days }} days
        @endif
    </span>
@endif
