<x-app-layout title="My Shows">
    <x-status-banner />

    <x-catalog-search :query="$query" :results="$results"
        :action="route('shows.index')" />

    <x-list-toolbar :genre-options="$genreOptions" :genre="$genre" :sort="$sort" />

    {{-- The Tracking heading only earns its space once there's another
         shelf below to tell this one apart from. --}}
    @php($hasOtherShelves = $finished->isNotEmpty() || $inactive->isNotEmpty())

    @if ($tracked->isNotEmpty())
        @if ($hasOtherShelves)
            <x-section-heading>Tracking</x-section-heading>
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
</x-app-layout>
