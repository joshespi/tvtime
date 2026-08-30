@props(['posterUrl' => null, 'title', 'date' => null, 'status' => null, 'voteAverage' => null, 'genres' => [], 'overview' => null])

{{-- The poster + "year · status · ★ rating" + genres + blurb header, shared by
     the tracked-show detail page and the catalog preview of an untracked title.
     Props are scalars rather than a model or a $details array so both callers
     can feed it without one of them having to fake the other's shape.

     The action row is the slot — including the trailer link — because the two
     pages order their controls differently, and owning the order here would
     force one of them to change. --}}
@php
    $year = $date ? \Illuminate\Support\Carbon::parse($date)->format('Y') : null;
@endphp

<x-card class="flex items-start gap-4">
    <x-show-poster :url="$posterUrl" :alt="$title" class="w-24 h-36" />
    <div class="min-w-0">
        <p class="text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-2">
            @if ($year)
                <span>{{ $year }}</span>
            @endif
            @if ($status)
                <span>&middot; {{ $status }}</span>
            @endif
            @if ($voteAverage)
                <span>&middot; &#9733; {{ number_format($voteAverage, 1) }}</span>
            @endif
        </p>
        @if (! empty($genres))
            <p class="text-xs text-gray-400 dark:text-gray-500 capitalize mt-1">{{ implode(', ', $genres) }}</p>
        @endif
        @if ($overview)
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $overview }}</p>
        @endif
        <div class="mt-3 flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    </div>
</x-card>
