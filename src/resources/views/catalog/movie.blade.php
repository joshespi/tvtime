<x-catalog-detail type="movie" :details="$details">
    @if (! $userMovie)
        <x-track-button type="movie" :tmdb-id="$tmdbId" variant="primary" />
    @elseif (! $userMovie->isFinished())
        {{-- Same sentence the search cards render for this title: a re-queued
             movie says it's been seen before here too, not just there. --}}
        @php($seen = $userMovie->seenPhrase())
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs text-emerald-700 dark:text-emerald-400">
                &#10003; On your watchlist{{ $seen ? " · {$seen}" : '' }}
            </span>
            <x-mark-watched-button :user-movie="$userMovie" variant="primary" />
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs text-emerald-700 dark:text-emerald-400">&#10003; {{ $userMovie->seenPhrase() }}</span>
            <x-finished-movie-actions :user-movie="$userMovie" />
        </div>
    @endif

    @if ($userMovie)
        <x-slot:after>
            <x-card>
                <x-rating-form :rating="$userMovie->rating" :note="$userMovie->note" :action="route('movies.update-rating', $userMovie)" />
            </x-card>
        </x-slot:after>
    @endif
</x-catalog-detail>
