<x-app-layout title="Dashboard" spacing="space-y-7 sm:space-y-9">
    <x-slot name="header">
        <div class="cinema-banner px-6 py-8 sm:px-10 sm:py-12">
            <img src="{{ asset('brand/cinema.webp') }}" alt="">
            <h1 class="font-display font-normal text-4xl tracking-tight sm:text-6xl">Dashboard</h1>
            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm font-medium">
                <a href="{{ route('shows.index') }}" class="underline decoration-white/50 underline-offset-4 hover:decoration-white">{{ trans_choice('{1} :count show with an episode ready|[0,*] :count shows with an episode ready', $upNext->count()) }}</a>
                <a href="{{ route('movies.index') }}" class="underline decoration-white/50 underline-offset-4 hover:decoration-white">{{ trans_choice('{1} :count movie to watch|[0,*] :count movies to watch', $movieWatchlist->count()) }}</a>
            </div>
        </div>
    </x-slot>

    <x-status-banner />

    <x-catalog-search :query="$query" :results="$results"
        :action="route('dashboard')" />

    @if ($upNext->isNotEmpty())
        <section aria-labelledby="dashboard-episodes" class="space-y-3">
            <x-section-heading id="dashboard-episodes">Episodes</x-section-heading>
            <x-card-grid>
                @foreach ($upNext as $entry)
                    <x-show-episode-card :show="$entry['show']">
                        <x-slot:meta>
                            <span class="basis-full">{{ $entry['episode']->label() }}</span>
                            <span class="text-xs">Aired {{ $entry['episode']->air_date?->format('M j, Y') }}</span>
                            @if ($entry['backlog'] > 0)
                                <x-text-link :href="$entry['show']->detailUrl()" size="text-xs">+{{ $entry['backlog'] }} more</x-text-link>
                            @endif
                        </x-slot:meta>
                        <x-episode-watched-button :episode="$entry['episode']" variant="secondary" />
                    </x-show-episode-card>
                @endforeach
            </x-card-grid>
        </section>
    @endif

    @if ($movieWatchlist->isNotEmpty())
        <section aria-labelledby="dashboard-movies" class="space-y-3">
            <x-section-heading id="dashboard-movies">Movies to Watch</x-section-heading>
            <x-card-grid>
                @foreach ($movieWatchlist as $userMovie)
                    <x-movie-card :user-movie="$userMovie" compact>
                        <x-mark-watched-button :user-movie="$userMovie" />
                    </x-movie-card>
                @endforeach
            </x-card-grid>
        </section>
    @endif

    @if ($airingSoon->isNotEmpty())
        <section aria-labelledby="dashboard-airing" class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <x-section-heading id="dashboard-airing">Airing Soon</x-section-heading>
                @if ($airingSoonHasMore)
                    <x-text-link :href="route('airing-soon.index')">See all <span aria-hidden="true">&rarr;</span></x-text-link>
                @endif
            </div>
            <x-airing-soon-grid :entries="$airingSoon" />
        </section>
    @endif

    @if ($query === '' && $upNext->isEmpty() && $airingSoon->isEmpty() && $movieWatchlist->isEmpty())
        <x-empty-state>
            @if ($hasTracked)
                <p class="flex items-center justify-center gap-2 font-semibold text-gray-900 dark:text-gray-100">
                    <x-completion-check />
                    You're caught up
                </p>
                <p class="mt-1">Nothing new is ready to watch.</p>
                <x-text-link :href="route('calendar.index')" class="mt-3 inline-block">View calendar</x-text-link>
            @else
                <p class="font-semibold text-gray-900 dark:text-gray-100">Add your first show or movie</p>
                <p class="mt-1">Nothing is being tracked yet. New episodes and releases will show up here once you add a title.</p>
                <x-action-button href="#catalog-search" class="mt-3">Find a show or movie</x-action-button>
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
