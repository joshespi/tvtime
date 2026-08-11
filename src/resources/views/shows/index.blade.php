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
                {{-- The heading only earns its space once there's a second shelf
                     below to tell this one apart from. --}}
                @if ($finished->isNotEmpty())
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Tracking</h3>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($tracked as $userShow)
                        <x-tracked-show-card :user-show="$userShow" />
                    @endforeach
                </div>
            @endif

            @if ($tracked->isEmpty() && $finished->isEmpty())
                <x-empty-state>You're not tracking any shows yet.</x-empty-state>
            @endif

            @if ($finished->isNotEmpty())
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide pt-6 px-1">
                    Finished <span class="font-normal normal-case">({{ $finished->count() }})</span>
                </h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 px-1 -mt-2">Caught up on everything that's aired. These move back to Tracking on their own when a new episode airs.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($finished as $userShow)
                        <x-tracked-show-card :user-show="$userShow" />
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
