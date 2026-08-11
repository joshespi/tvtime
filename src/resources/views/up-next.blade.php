<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Up Next</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <x-status-banner />

            <x-catalog-search :query="$query" :results="$results"
                :action="route('dashboard')" :suggest-url="route('search.suggest')" />

            @if ($upNext->isNotEmpty())
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Episodes</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($upNext as $entry)
                        <x-show-episode-card :show="$entry['show']">
                            <x-slot:meta>
                                <span>{{ $entry['episode']->label() }}</span>
                                <span class="text-gray-400 dark:text-gray-500">· aired {{ $entry['episode']->air_date?->format('M j, Y') }}</span>
                            </x-slot:meta>
                            <form method="POST" action="{{ route('episodes.toggle-watched', $entry['episode']) }}">
                                @csrf
                                <x-action-button>Mark watched</x-action-button>
                            </form>
                        </x-show-episode-card>
                    @endforeach
                </div>
            @endif

            @if ($airingSoon->isNotEmpty())
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Airing Soon</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($airingSoon as $entry)
                        <x-show-episode-card :show="$entry['show']">
                            <x-slot:meta>
                                <span>{{ $entry['episode']->label() }}</span>
                                <x-countdown :date="$entry['episode']->air_date" />
                            </x-slot:meta>
                        </x-show-episode-card>
                    @endforeach
                </div>
            @endif

            @if ($movieWatchlist->isNotEmpty())
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Movies to Watch</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($movieWatchlist as $userMovie)
                        <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
                            <x-show-poster :url="$userMovie->movie->posterUrl()" :alt="$userMovie->movie->title" class="w-12 h-18" />
                            <div class="min-w-0 flex-1 flex flex-col gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $userMovie->movie->title }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                        @if ($userMovie->movie->release_date)
                                            <span>{{ $userMovie->movie->release_date->format('Y') }}</span>
                                        @endif
                                        @if ($userMovie->movie->vote_average)
                                            <span>· &#9733; {{ number_format($userMovie->movie->vote_average, 1) }}</span>
                                        @endif
                                        <x-countdown :date="$userMovie->movie->release_date" />
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('movies.toggle-watched', $userMovie) }}">
                                    @csrf
                                    <x-action-button>Mark watched</x-action-button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($upNext->isEmpty() && $airingSoon->isEmpty() && $movieWatchlist->isEmpty())
                <x-empty-state>
                    <p>Nothing new to watch right now.</p>
                    <a href="{{ route('shows.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Manage your shows</a>
                </x-empty-state>
            @endif

            @if (! empty($recommendations))
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Recommended for You</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($recommendations as $result)
                        <x-catalog-result-card :result="$result" />
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
