<x-app-layout title="Airing Soon">
    <x-status-banner />

    <x-text-link :href="route('dashboard')">&larr; Dashboard</x-text-link>

    @if ($airingSoon->isNotEmpty())
        <x-airing-soon-grid :entries="$airingSoon" />
    @else
        <x-empty-state>
            <p>Nothing airing soon.</p>
        </x-empty-state>
    @endif
</x-app-layout>
