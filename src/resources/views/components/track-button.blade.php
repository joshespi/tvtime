@props(['type', 'tmdbId', 'variant' => 'secondary'])

{{-- The one place that knows how to render "add this TMDB title to my list" —
     search results, recommendations, and the catalog detail pages all track a
     title the same way, so they all render this instead of re-declaring the
     Alpine wiring and button states themselves. --}}
@php
    $label = $type === 'movie' ? 'Add to watchlist' : 'Track';
    $doneLabel = $type === 'movie' ? 'Added' : 'Tracking';
@endphp

<div x-data="trackButton(@js(route('search.store')), @js($type), @js($tmdbId))">
    <form method="POST" action="{{ route('search.store') }}" @submit.prevent="track()">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="external_id" value="{{ $tmdbId }}">
        <button type="submit" :disabled="status === 'loading' || status === 'done'"
                class="{{ \App\Support\ButtonStyle::base() }} gap-1 disabled:opacity-75"
                :class="status === 'done' ? @js(\App\Support\ButtonStyle::variant('success')) : @js(\App\Support\ButtonStyle::variant($variant))">
            <span x-show="status === 'idle' || status === 'error'">{{ $label }}</span>
            <span x-show="status === 'loading'">Working&hellip;</span>
            <span x-show="status === 'done'">&#10003; {{ $doneLabel }}</span>
        </button>
    </form>
    <p x-show="status === 'error'" x-text="message" class="text-xs text-red-600 dark:text-red-400 mt-1"></p>
</div>
