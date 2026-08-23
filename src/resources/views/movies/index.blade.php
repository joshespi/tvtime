<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">My Movies</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <x-status-banner />

            <x-catalog-search :query="$query" :results="$results"
                :action="route('movies.index')" :suggest-url="route('search.suggest')" />

            <div class="flex items-center justify-between gap-2 px-1">
                <a href="{{ route('history.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Watch history &rarr;</a>
                <x-sort-select :sort="$sort" :query="$query" />
            </div>

            @if ($finished->isNotEmpty())
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Watchlist</h3>
            @endif

            @if ($watchlist->isNotEmpty())
                <x-card-grid>
                    @foreach ($watchlist as $userMovie)
                        <x-movie-card :user-movie="$userMovie">
                            <x-mark-watched-button :user-movie="$userMovie" variant="primary" />
                            <form method="POST" action="{{ route('movies.destroy', $userMovie) }}" onsubmit="return confirm('Remove this movie from your list?')">
                                @csrf
                                @method('DELETE')
                                <x-action-button>Remove</x-action-button>
                            </form>
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
                                <form method="POST" action="{{ route('movies.destroy', $userMovie) }}" onsubmit="return confirm('Remove this movie and its watch history?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-action-button>Remove</x-action-button>
                                </form>
                            </x-movie-card>
                        @endforeach
                    </x-card-grid>
                </x-collapsible-section>
            @endif

        </div>
    </div>
</x-app-layout>
