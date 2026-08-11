<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">My Shows</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <x-status-banner />

            <x-catalog-search :query="$query" :results="$results"
                :action="route('shows.index')" :suggest-url="route('search.suggest')" />

            <div class="flex items-center justify-between gap-2 px-1">
                <a href="{{ route('history.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Watch history &rarr;</a>
                <x-sort-select :sort="$sort" :query="$query" />
            </div>

            @if ($tracked->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($tracked as $userShow)
                        <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
                            <x-show-poster :url="$userShow->show->posterUrl()" :alt="$userShow->show->title"
                                           :href="route('shows.show', $userShow->show)" />
                            <div class="min-w-0 flex-1 flex flex-col gap-2">
                                <div class="min-w-0">
                                    <a href="{{ route('shows.show', $userShow->show) }}" class="block font-semibold text-gray-900 dark:text-gray-100 truncate hover:underline">{{ $userShow->show->title }}</a>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                        {{ $userShow->status }}
                                        @if ($userShow->show->status)
                                            · {{ $userShow->show->status }}
                                        @endif
                                        @if ($userShow->show->vote_average)
                                            · &#9733; {{ number_format($userShow->show->vote_average, 1) }}
                                        @endif
                                    </p>
                                    @if (! empty($userShow->show->genres))
                                        <p class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ implode(', ', $userShow->show->genres) }}</p>
                                    @endif
                                    @if ($userShow->show->nextUpcomingEpisode)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                            <span>Next: {{ $userShow->show->nextUpcomingEpisode->code() }}</span>
                                            <x-countdown :date="$userShow->show->nextUpcomingEpisode->air_date" />
                                        </p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('shows.destroy', $userShow) }}" onsubmit="return confirm('Stop tracking this show?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-action-button>Remove</x-action-button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state>You're not tracking any shows yet.</x-empty-state>
            @endif

        </div>
    </div>
</x-app-layout>
