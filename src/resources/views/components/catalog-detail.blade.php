@props(['type', 'details'])

{{-- Shared shell for the "see more details" page reached from search results
     and recommendations — show.blade.php and movie.blade.php differ only in
     which TMDB date field they carry and what the action slot renders. The
     header itself is <x-title-summary>, shared with the tracked-show page. --}}
@php
    $date = $details['first_air_date'] ?? $details['release_date'] ?? null;
@endphp

<x-app-layout :title="$details['title']" width="max-w-3xl" spacing="space-y-6">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ $details['title'] }}
            <x-media-type-badge :type="$type" class="ms-1" />
        </h2>
    </x-slot>

    <x-status-banner />

    <x-text-link :href="route(\App\Support\MediaTypeStyle::indexRoute($type))">&larr; Back</x-text-link>

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
</x-app-layout>
