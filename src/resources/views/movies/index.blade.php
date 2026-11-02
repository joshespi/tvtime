<x-app-layout title="My Movies">
    <x-status-banner />

    <x-catalog-search :query="$query" :results="$results"
        :action="route('movies.index')" />

    <x-list-toolbar :genre-options="$genreOptions" :genre="$genre" :sort="$sort" />

    @if ($finished->isNotEmpty())
        <x-section-heading>Watchlist</x-section-heading>
    @endif

    @if ($watchlist->isNotEmpty())
        <x-card-grid>
            @foreach ($watchlist as $userMovie)
                <x-movie-card :user-movie="$userMovie">
                    <x-mark-watched-button :user-movie="$userMovie" variant="primary" />
                    <x-remove-button :action="route('movies.destroy', $userMovie)" confirm="Remove this movie from your list?" />
                </x-movie-card>
            @endforeach
        </x-card-grid>
    @else
        <x-empty-state>Your watchlist is empty.</x-empty-state>
    @endif

    @if ($finished->isNotEmpty())
        <x-collapsible-section title="Finished" :count="$finished->count()" storage-key="movies.finished">
            <x-card-grid>
                @foreach ($finished as $userMovie)
                    <x-movie-card :user-movie="$userMovie">
                        <x-finished-movie-actions :user-movie="$userMovie" />
                        <x-remove-button :action="route('movies.destroy', $userMovie)" confirm="Remove this movie and its watch history?" />
                    </x-movie-card>
                @endforeach
            </x-card-grid>
        </x-collapsible-section>
    @endif
</x-app-layout>
