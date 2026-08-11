<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Your Stats</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <a href="{{ route('profile.edit') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to profile</a>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Episodes Watched</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $episodesWatchedTotal }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $episodesWatchedThisYear }} this year</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Movies Watched</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $moviesWatchedTotal }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $moviesWatchedThisYear }} this year</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Longest Streak</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $longestStreak }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">day{{ $longestStreak === 1 ? '' : 's' }} in a row</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Member Since</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $memberSince->format('Y') }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $memberSince->format('M j, Y') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Shows</h3>
                    @if ($showStatusCounts->isNotEmpty())
                        <ul class="mt-2 space-y-1">
                            @foreach ($showStatusCounts as $status => $count)
                                <li class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300 capitalize">{{ $status }}</span>
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">Nothing tracked yet.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4">
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
                        <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">Not enough data yet.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
