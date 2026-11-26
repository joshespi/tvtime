<x-catalog-detail type="movie" :details="$details">
    @if (! $userMovie)
        <x-track-button type="movie" :tmdb-id="$tmdbId" variant="primary" />
    @else
        <div class="flex flex-wrap items-center gap-3">
            <x-tracked-label>{{ $userMovie->stateLabel() }}</x-tracked-label>
            @if ($userMovie->isFinished())
                <x-finished-movie-actions :user-movie="$userMovie" />
            @else
                <x-mark-watched-button :user-movie="$userMovie" variant="primary" />
            @endif
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
