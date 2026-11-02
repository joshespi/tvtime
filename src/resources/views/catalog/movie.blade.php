<x-catalog-detail type="movie" :details="$details">
    @if (! $userMovie)
        <x-track-button type="movie" :tmdb-id="$tmdbId" variant="primary" />
    @elseif (! $userMovie->isFinished())
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs text-emerald-700 dark:text-emerald-400">&#10003; {{ $userMovie->stateLabel() }}</span>
            <x-mark-watched-button :user-movie="$userMovie" variant="primary" />
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs text-emerald-700 dark:text-emerald-400">&#10003; {{ $userMovie->stateLabel() }}</span>
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
