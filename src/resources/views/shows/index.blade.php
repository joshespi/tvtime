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

            {{-- The Tracking heading only earns its space once there's another
                 shelf below to tell this one apart from. --}}
            @php($hasOtherShelves = $finished->isNotEmpty() || $inactive->isNotEmpty())

            @if ($tracked->isNotEmpty())
                @if ($hasOtherShelves)
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide px-1">Tracking</h3>
                @endif
                <x-card-grid>
                    @foreach ($tracked as $userShow)
                        <x-tracked-show-card :user-show="$userShow" />
                    @endforeach
                </x-card-grid>
            @elseif (! $hasOtherShelves)
                <x-empty-state>You're not tracking any shows yet.</x-empty-state>
            @endif

            @if ($finished->isNotEmpty())
                <x-collapsible-section title="Finished" :count="$finished->count()" storage-key="shows.finished"
                    description="Caught up on everything that's aired. These move back to Tracking on their own when a new episode airs.">
                    <x-card-grid>
                        @foreach ($finished as $userShow)
                            <x-tracked-show-card :user-show="$userShow" />
                        @endforeach
                    </x-card-grid>
                </x-collapsible-section>
            @endif

            @if ($inactive->isNotEmpty())
                <x-collapsible-section title="Not Tracking" :count="$inactive->count()" :open="false" storage-key="shows.inactive"
                    description="Paused or dropped. Their watch history is kept, but they stay off your dashboard and calendar and stop sending notifications.">
                    <x-card-grid>
                        @foreach ($inactive as $userShow)
                            <x-tracked-show-card :user-show="$userShow" />
                        @endforeach
                    </x-card-grid>
                </x-collapsible-section>
            @endif

        </div>
    </div>
</x-app-layout>
