<x-catalog-detail type="movie" :details="$details" :tmdb-id="$tmdbId">
    @if (! $userMovie)
        <x-track-button type="movie" :tmdb-id="$tmdbId" variant="primary" />
    @elseif (! $userMovie->isFinished())
        <div class="flex items-center gap-3">
            <span class="text-xs text-emerald-700 dark:text-emerald-400">&#10003; On your watchlist</span>
            <x-mark-watched-button :user-movie="$userMovie" variant="primary" />
        </div>
    @else
        <div class="flex items-center gap-3">
            <span class="text-xs text-emerald-700 dark:text-emerald-400">&#10003; {{ $userMovie->seenPhrase() }}</span>
            <x-finished-movie-actions :user-movie="$userMovie" />
        </div>
    @endif
</x-catalog-detail>
