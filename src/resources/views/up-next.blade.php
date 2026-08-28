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
                <x-card-grid>
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
                </x-card-grid>
            @endif

            @if ($movieWatchlist->isNotEmpty())
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Movies to Watch</h3>
                <x-card-grid>
                    @foreach ($movieWatchlist as $userMovie)
                        <x-movie-card :user-movie="$userMovie" compact>
                            <x-mark-watched-button :user-movie="$userMovie" />
                        </x-movie-card>
                    @endforeach
                </x-card-grid>
            @endif

            @if ($airingSoon->isNotEmpty())
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Airing Soon</h3>
                <x-card-grid>
                    @foreach ($airingSoon as $entry)
                        <x-show-episode-card :show="$entry['show']">
                            <x-slot:meta>
                                <span>{{ $entry['episode']->label() }}</span>
                                <x-countdown :date="$entry['episode']->air_date" />
                            </x-slot:meta>
                        </x-show-episode-card>
                    @endforeach
                </x-card-grid>
            @endif

            @if ($upNext->isEmpty() && $airingSoon->isEmpty() && $movieWatchlist->isEmpty())
                <x-empty-state>
                    <p>Nothing new to watch right now.</p>
                    <a href="{{ route('shows.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">Manage your shows</a>
                </x-empty-state>
            @endif

            @if (! empty($recommendations))
                <x-collapsible-section title="Recommended for You" storage-key="dashboard.recommendations">
                    <x-card-grid>
                        @foreach ($recommendations as $result)
                            <x-catalog-result-card :result="$result" dismissible />
                        @endforeach
                    </x-card-grid>
                </x-collapsible-section>
            @endif

        </div>
    </div>
</x-app-layout>
