<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">My Movies</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <x-status-banner />

            <x-catalog-search :query="$query" :source="$source" :results="$results"
                :action="route('movies.index')" :suggest-url="route('search.suggest')" />

            <div class="flex items-center justify-between gap-2 px-1">
                <a href="{{ route('history.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Watch history &rarr;</a>
                <x-sort-select :sort="$sort" :query="$query" />
            </div>

            @if ($watchlist->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($watchlist as $userMovie)
                        <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
                            <x-show-poster :url="$userMovie->movie->posterUrl()" :alt="$userMovie->movie->title" />
                            <div class="min-w-0 flex-1 flex flex-col gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $userMovie->movie->title }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                        @if ($userMovie->movie->release_date)
                                            {{ $userMovie->movie->release_date->format('Y') }}
                                        @endif
                                        @if ($userMovie->movie->status)
                                            · {{ $userMovie->movie->status }}
                                        @endif
                                        @if ($userMovie->movie->vote_average)
                                            · &#9733; {{ number_format($userMovie->movie->vote_average, 1) }}
                                        @endif
                                    </p>
                                    @if (! empty($userMovie->movie->genres))
                                        <p class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ implode(', ', $userMovie->movie->genres) }}</p>
                                    @endif
                                    @if ($userMovie->movie->overview)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $userMovie->movie->overview }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('movies.toggle-watched', $userMovie) }}">
                                        @csrf
                                        <x-action-button variant="primary">Mark watched</x-action-button>
                                    </form>
                                    <form method="POST" action="{{ route('movies.destroy', $userMovie) }}" onsubmit="return confirm('Remove this movie from your list?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-action-button>Remove</x-action-button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state>Your watchlist is empty.</x-empty-state>
            @endif

        </div>
    </div>
</x-app-layout>
