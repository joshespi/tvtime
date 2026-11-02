<x-app-layout title="Dashboard">
    <x-status-banner />

    <x-catalog-search :query="$query" :results="$results"
        :action="route('dashboard')" />

    @if ($upNext->isNotEmpty())
        <x-section-heading>Episodes</x-section-heading>
        <x-card-grid>
            @foreach ($upNext as $entry)
                <x-show-episode-card :show="$entry['show']">
                    <x-slot:meta>
                        <span>{{ $entry['episode']->label() }}</span>
                        <span class="text-gray-500 dark:text-gray-400">· aired {{ $entry['episode']->air_date?->format('M j, Y') }}</span>
                        @if ($entry['backlog'] > 0)
                            <x-text-link :href="route('shows.show', $entry['show'])" size="text-xs">+{{ $entry['backlog'] }} more</x-text-link>
                        @endif
                    </x-slot:meta>
                    <form method="POST" action="{{ route('episodes.toggle-watched', $entry['episode']) }}">
                        @csrf
                        <input type="hidden" name="watched" value="1">
                        <x-action-button>Mark watched</x-action-button>
                    </form>
                </x-show-episode-card>
            @endforeach
        </x-card-grid>
    @endif

    @if ($movieWatchlist->isNotEmpty())
        <x-section-heading>Movies to Watch</x-section-heading>
        <x-card-grid>
            @foreach ($movieWatchlist as $userMovie)
                <x-movie-card :user-movie="$userMovie" compact>
                    <x-mark-watched-button :user-movie="$userMovie" />
                </x-movie-card>
            @endforeach
        </x-card-grid>
    @endif

    @if ($airingSoon->isNotEmpty())
        <div class="flex items-center justify-between px-1">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Airing Soon</h3>
            @if ($airingSoonHasMore)
                <x-text-link :href="route('airing-soon.index')" size="text-xs">See all</x-text-link>
            @endif
        </div>
        <x-airing-soon-grid :entries="$airingSoon" />
    @endif

    @if ($upNext->isEmpty() && $airingSoon->isEmpty() && $movieWatchlist->isEmpty())
        <x-empty-state>
            @if ($hasTracked)
                <p>Nothing new to watch right now.</p>
                <x-text-link :href="route('shows.index')">Manage your shows</x-text-link>
            @else
                <p class="font-medium text-gray-700 dark:text-gray-200">Welcome! Nothing is being tracked yet.</p>
                <p>Use the search box above to find a show or movie and add it — new episodes and releases will show up here.</p>
            @endif
        </x-empty-state>
    @endif

    @if (! empty($recommendations))
        {{-- Tracking a recommendation needs a full reload (a fresh show
             list and a re-rolled shelf, both server-rendered) — but a
             plain reload would dump a mobile user back at the top. The
             "tracked" event is generic (track-button just announces
             success), only this shelf reacts to it, and setting the
             hash before reloading lets the browser's native anchor
             scroll land back here without any extra bookkeeping. --}}
        @php $recommendationsSectionId = 'recommendations-section'; @endphp
        <div x-data @tracked="window.location.hash = @js($recommendationsSectionId); window.location.reload()">
            <x-collapsible-section :id="$recommendationsSectionId" title="Recommended for You" storage-key="dashboard.recommendations">
                <x-card-grid>
                    @foreach ($recommendations as $result)
                        <x-catalog-result-card :result="$result" dismissible />
                    @endforeach
                </x-card-grid>
            </x-collapsible-section>
        </div>
    @endif
</x-app-layout>
