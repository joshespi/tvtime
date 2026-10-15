@props(['variant' => 'success'])

{{-- Shows its slot when given one, else the flashed status — never for the
     error variant, which would otherwise paint a success message red. --}}
@php
    $hasSlot = trim((string) $slot) !== '';
    $fromSession = ! $hasSlot && $variant !== 'error';
    $message = $hasSlot ? $slot : ($fromSession ? session('status') : null);
    $classes = $variant === 'error'
        ? 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400'
        : 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400';
@endphp

@if ($message)
    <div {{ $attributes->merge(['class' => "{$classes} text-sm rounded-lg px-4 py-3 flex items-center justify-between gap-3"]) }}>
        <span>{{ $message }}</span>
        @if ($fromSession && is_array($undo = session('undo')))
            {{-- Posts the opposite state explicitly, so Undo is exact even if the
                 original request was a toggle. --}}
            <form method="POST" action="{{ $undo['url'] }}" class="shrink-0">
                @csrf
                <input type="hidden" name="watched" value="{{ $undo['watched'] ? 1 : 0 }}">
                <button type="submit" class="font-semibold underline hover:no-underline">Undo</button>
            </form>
        @endif
    </div>
@endif
