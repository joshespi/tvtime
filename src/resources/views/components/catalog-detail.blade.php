@props(['type', 'details'])

{{-- Shared shell for the "see more details" page reached from search results
     and recommendations — show.blade.php and movie.blade.php differ only in
     which TMDB date field they carry and what the action slot renders. The
     header itself is <x-title-summary>, shared with the tracked-show page. --}}
@php
    $date = $details['first_air_date'] ?? $details['release_date'] ?? null;
@endphp

<x-app-layout :title="$details['title']" width="max-w-3xl">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ $details['title'] }}
            <x-media-type-badge :type="$type" class="ms-1" />
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-status-banner />

            <a href="{{ route(\App\Support\MediaTypeStyle::indexRoute($type)) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back</a>

            <x-title-summary :poster-url="\App\Support\TmdbImage::url($details['poster_path'], \App\Support\TmdbImage::SIZE_DETAIL)"
                             :title="$details['title']" :date="$date" :status="$details['status']"
                             :vote-average="$details['vote_average']" :genres="$details['genres']"
                             :overview="$details['overview']">
                {{ $slot }}
                <x-trailer-link :trailer-key="$details['trailer_key'] ?? null" />
                <x-imdb-link :imdb-id="$details['imdb_id'] ?? null" />
            </x-title-summary>

            {{ $after ?? '' }}

            <x-cast-list :cast="$details['cast'] ?? []" />

        </div>
    </div>
</x-app-layout>
