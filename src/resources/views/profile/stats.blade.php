<x-app-layout title="Stats" heading="Your Stats">
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <a href="{{ route('profile.edit') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to profile</a>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-stat-tile label="Episodes Watched" :value="$episodesWatchedTotal" :caption="$episodesWatchedThisYear.' this year'" />
                <x-stat-tile label="Movies Watched" :value="$moviesWatchedTotal" :caption="$moviesWatchedThisYear.' this year'" />
                <x-stat-tile label="Longest Streak" :value="$longestStreak" :caption="'day'.($longestStreak === 1 ? '' : 's').' in a row'" />
                <x-stat-tile label="Member Since" :value="$memberSince->format('Y')" :caption="$memberSince->format('M j, Y')" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-card>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Shows</h3>
                    @if ($showStatusCounts->isNotEmpty())
                        <ul class="mt-2 space-y-1">
                            @foreach ($showStatusCounts as $status => $count)
                                <li class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300">{{ \App\Support\TrackingStatus::from($status)->label() }}</span>
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nothing tracked yet.</p>
                    @endif
                </x-card>

                <x-card>
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Top Genres</h3>
                    @if ($topGenres->isNotEmpty())
                        <ul class="mt-2 space-y-1">
                            @foreach ($topGenres as $genre => $count)
                                <li class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300">{{ $genre }}</span>
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Not enough data yet.</p>
                    @endif
                </x-card>
            </div>

        </div>
    </div>
</x-app-layout>
