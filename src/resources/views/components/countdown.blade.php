@props(['date' => null])

@php
    // The badge owns the whole "is there anything to count down to?" rule — a
    // missing or already-past date renders nothing, so call sites never guard.
    $days = $date ? today()->diffInDays($date->copy()->startOfDay(), false) : null;
@endphp

@if ($days !== null && $days >= 0)
    <span {{ $attributes->merge(['class' => 'shrink-0 text-[10px] font-medium uppercase tracking-wide px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400']) }}>
        @if ($days === 0)
            Today
        @elseif ($days === 1)
            Tomorrow
        @else
            in {{ $days }} days
        @endif
    </span>
@endif
