@use('App\Support\Provider')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">{{ $show->title }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <x-status-banner />

            <a href="{{ route('shows.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; My Shows</a>

            <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
                <x-show-poster :url="$show->posterUrl('w342')" :alt="$show->title" class="w-24 h-36" />
                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-2">
                        @if ($show->first_air_date)
                            <span>{{ $show->first_air_date->format('Y') }}</span>
                        @endif
                        @if ($show->status)
                            <span>&middot; {{ $show->status }}</span>
                        @endif
                        @if ($show->vote_average)
                            <span>&middot; &#9733; {{ number_format($show->vote_average, 1) }}</span>
                        @endif
                    </p>
                    @if (! empty($show->genres))
                        <p class="text-xs text-gray-400 dark:text-gray-500 capitalize mt-1">{{ implode(', ', $show->genres) }}</p>
                    @endif
                    @if ($show->overview)
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $show->overview }}</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 space-y-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Data sources</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Resolved via <span class="font-medium capitalize">{{ $show->resolved_source }}</span>
                    &middot; TMDB {{ $show->tmdb_id ? 'linked' : 'not linked' }}
                    &middot; Trakt {{ $show->trakt_id ? 'linked' : 'not linked' }}
                </p>

                @if ($show->tmdb_id && $show->trakt_id)
                    <form method="POST" action="{{ route('shows.check-conflicts', $show) }}">
                        @csrf
                        <x-action-button>Check for air-date conflicts</x-action-button>
                    </form>
                @else
                    <form method="GET" action="{{ route('shows.show', $show) }}" class="flex gap-2">
                        <x-text-input type="text" name="link_query" :value="$linkQuery" class="flex-1 text-sm"
                                      placeholder="Search {{ $secondary?->label() ?? 'the other source' }} for this show" />
                        <x-action-button>Search</x-action-button>
                    </form>

                    @if ($linkQuery !== '')
                        @forelse ($linkResults as $result)
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate text-gray-700 dark:text-gray-200">
                                    {{ $result['title'] }}
                                    @if ($result['date'])
                                        <span class="text-gray-400 dark:text-gray-500">({{ substr($result['date'], 0, 4) }})</span>
                                    @endif
                                </span>
                                <form method="POST" action="{{ route('shows.link-source', $show) }}">
                                    @csrf
                                    <input type="hidden" name="source" value="{{ $secondary?->value }}" />
                                    <input type="hidden" name="external_id" value="{{ $result['id'] }}" />
                                    <x-action-button variant="primary">Link</x-action-button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 dark:text-gray-500">No results.</p>
                        @endforelse
                    @endif
                @endif
            </div>

            @if (session('conflicts'))
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-amber-300 dark:ring-amber-700 sm:rounded-xl px-6 py-4 space-y-3">
                    <h3 class="font-semibold text-amber-700 dark:text-amber-400 text-sm">Air-date conflicts found</h3>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach (session('conflicts') as $conflict)
                            <li class="py-3 flex items-center justify-between gap-4">
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $conflict['code'] }}</span>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('episodes.resolve-conflict', $conflict['episode_id']) }}">
                                        @csrf
                                        <input type="hidden" name="air_date" value="{{ $conflict['current_date'] }}" />
                                        <x-action-button>
                                            Keep {{ $conflict['current_date'] }} ({{ Provider::from($conflict['current_source'])->label() }})
                                        </x-action-button>
                                    </form>
                                    <form method="POST" action="{{ route('episodes.resolve-conflict', $conflict['episode_id']) }}">
                                        @csrf
                                        <input type="hidden" name="air_date" value="{{ $conflict['other_date'] }}" />
                                        <x-action-button variant="primary">
                                            Use {{ $conflict['other_date'] }} ({{ Provider::from($conflict['other_source'])->label() }})
                                        </x-action-button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @forelse ($seasons as $season => $episodes)
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl">
                    <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Season {{ $season }}</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($episodes as $episode)
                            <li class="px-6 py-3 flex items-center justify-between gap-4">
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
                                    <form method="POST" action="{{ route('episodes.toggle-watched', $episode) }}"
                                        @if (! $episode->watched && $episode->hasUnwatchedBefore)
                                            data-mark-through-url="{{ route('episodes.mark-through', $episode) }}"
                                            onsubmit="return tvTracker.confirmMarkWatched(this)"
                                        @endif
                                    >
                                        @csrf
                                        <x-action-button :variant="$episode->watched ? 'secondary' : 'primary'">
                                            {{ $episode->watched ? 'Mark unwatched' : 'Mark watched' }}
                                        </x-action-button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
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
