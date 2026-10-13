@props(['userMovie', 'compact' => false])

{{-- The shared movie card: watchlist, Finished shelf and the dashboard all use
     it, differing only in the buttons passed as the slot. `compact` is the
     dashboard's denser variant — smaller poster, no genres or blurb — because
     Up Next is a "what's on deck" list rather than something to browse. --}}
@php
    $detailUrl = $userMovie->movie->detailUrl();
@endphp

<x-card class="flex items-start gap-4">
    <x-show-poster :url="$userMovie->movie->posterUrl()" :alt="$userMovie->movie->title"
                   :href="$detailUrl" :class="$compact ? 'w-12 h-18' : 'w-16 h-24'" />
    <div class="min-w-0 flex-1 flex flex-col gap-2">
        <div class="min-w-0">
            <a href="{{ $detailUrl }}"
               class="block font-semibold text-gray-900 dark:text-gray-100 truncate hover:underline">{{ $userMovie->movie->title }}</a>
            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide flex items-center gap-2 flex-wrap">
                <span>
                    @if ($userMovie->movie->release_date)
                        {{ $userMovie->movie->release_date->format('Y') }}
                    @endif
                    @if (! $compact && $userMovie->movie->status)
                        · {{ $userMovie->movie->status }}
                    @endif
                    @if ($userMovie->movie->vote_average)
                        · &#9733; {{ number_format($userMovie->movie->vote_average, 1) }}
                    @endif
                    @if ($userMovie->rating)
                        · Your rating: {{ $userMovie->rating }}/{{ \App\Support\Rating::MAX }}
                    @endif
                </span>
                <x-countdown :date="$userMovie->movie->release_date" />
            </p>
            @if (! $compact && ! empty($userMovie->movie->genres))
                <p class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ implode(', ', $userMovie->movie->genres) }}</p>
            @endif
            @if ($seen = $userMovie->seenPhrase())
                <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">&#10003; {{ $seen }}</p>
            @endif
            @if (! $compact && $userMovie->movie->overview)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $userMovie->movie->overview }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    </div>
</x-card>
