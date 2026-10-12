<x-app-layout :title="$show->title">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">{{ $show->title }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-status-banner />

            <a href="{{ route('shows.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; My Shows</a>

            <x-title-summary :poster-url="$show->posterUrl('w342')" :title="$show->title"
                             :date="$show->first_air_date" :status="$show->status"
                             :vote-average="$show->vote_average" :genres="$show->genres ?? []"
                             :overview="$show->overview">
                <x-tracking-status-select :user-show="$userShow" />
                <x-trailer-link :trailer-key="$show->trailer_key" />
                @unless ($userShow->status->isActive())
                    <span class="text-xs text-gray-400 dark:text-gray-500">No dashboard entries, calendar dates or notifications.</span>
                @endunless
            </x-title-summary>

            <x-card>
                <x-rating-form :rating="$userShow->rating" :note="$userShow->note" :action="route('shows.update-rating', $userShow)" />
            </x-card>

            <x-cast-list :cast="$show->cast ?? []" />

            @forelse ($seasons as $season => $episodes)
                @php
                    $total = $episodes->count();
                    $watchedCount = $episodes->sum(fn ($episode) => (int) $episode->watched);
                    $hasAiredUnwatched = $episodes->contains(fn ($episode) => ! $episode->watched && $episode->hasAired());
                @endphp
                {{-- Native <details>, like <x-collapsible-section>: folds without JS.
                     Which season starts open is picked in the controller. --}}
                <x-card padding="">
                <details id="season-{{ $season }}" @if ($season === $openSeason) open @endif class="group">
                    <summary class="flex cursor-pointer items-center gap-3 px-6 py-3 list-none [&::-webkit-details-marker]:hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-xl">
                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-150 group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                        </svg>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Season {{ $season }}</h3>
                        <span class="text-sm {{ $watchedCount === $total ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $watchedCount }}/{{ $total }}
                        </span>
                        @if ($hasAiredUnwatched)
                            <form method="POST" action="{{ route('seasons.mark-watched', ['show' => $show, 'season' => $season]) }}" class="ms-auto">
                                @csrf
                                <x-action-button>Mark season watched</x-action-button>
                            </form>
                        @endif
                    </summary>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700 border-t border-gray-100 dark:border-gray-700">
                        @foreach ($episodes as $episode)
                            @php($upcoming = $episode->isUpcoming())
                            <li id="episode-{{ $episode->id }}" class="px-6 py-3 flex items-center justify-between gap-4 scroll-mt-4 {{ $upcoming ? 'opacity-60' : '' }}">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium {{ $episode->watched ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100' }}">
                                        E{{ $episode->episode_number }}
                                        @if ($episode->title)
                                            — {{ $episode->title }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $episode->air_date ? $episode->air_date->format('M j, Y') : 'Air date unknown' }}
                                    </p>
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    @if ($upcoming && ! $episode->watched)
                                        <x-countdown :date="$episode->air_date" />
                                    @else
                                        <form method="POST" action="{{ route('episodes.toggle-watched', $episode) }}"
                                            @if (! $episode->watched && $episode->hasUnwatchedBefore)
                                                data-mark-through-url="{{ route('episodes.mark-through', $episode) }}"
                                                onsubmit="return tvTracker.confirmMarkWatched(this)"
                                            @endif
                                        >
                                            @csrf
                                            <input type="hidden" name="watched" value="{{ $episode->watched ? 0 : 1 }}">
                                            <x-action-button :variant="$episode->watched ? 'secondary' : 'primary'">
                                                {{ $episode->watched ? 'Mark unwatched' : 'Mark watched' }}
                                            </x-action-button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </details>
                </x-card>
            @empty
                <x-empty-state>No episode data yet for this show.</x-empty-state>
            @endforelse

        </div>
    </div>

    <script>
        window.tvTracker = window.tvTracker || {};
        window.tvTracker.confirmMarkWatched = function (form) {
            if (confirm('Mark the unwatched episodes before this one as watched too?')) {
                form.action = form.dataset.markThroughUrl;
            }
            return true;
        };
    </script>
</x-app-layout>
