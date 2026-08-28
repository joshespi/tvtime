@props(['type', 'details', 'tmdbId'])

{{-- Shared shell for the "see more details" page reached from search results
     and recommendations — show.blade.php and movie.blade.php differ only in
     which TMDB date field they carry and what the action slot renders. --}}
@php
    $date = $details['first_air_date'] ?? $details['release_date'] ?? null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ $details['title'] }}
            <x-media-type-badge :type="$type" />
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-status-banner />

            <a href="{{ url()->previous() }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back</a>

            <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
                <x-show-poster :url="\App\Support\TmdbImage::url($details['poster_path'], 'w342')" :alt="$details['title']" class="w-24 h-36" />
                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-2">
                        @if ($date)
                            <span>{{ \Illuminate\Support\Carbon::parse($date)->format('Y') }}</span>
                        @endif
                        @if ($details['status'])
                            <span>&middot; {{ $details['status'] }}</span>
                        @endif
                        @if ($details['vote_average'])
                            <span>&middot; &#9733; {{ number_format($details['vote_average'], 1) }}</span>
                        @endif
                    </p>
                    @if (! empty($details['genres']))
                        <p class="text-xs text-gray-400 dark:text-gray-500 capitalize mt-1">{{ implode(', ', $details['genres']) }}</p>
                    @endif
                    @if ($details['overview'])
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $details['overview'] }}</p>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        {{ $slot }}
                        <x-trailer-link :trailer-key="$details['trailer_key'] ?? null" />
                    </div>
                </div>
            </div>

            <x-cast-list :cast="$details['cast'] ?? []" />

        </div>
    </div>
</x-app-layout>
