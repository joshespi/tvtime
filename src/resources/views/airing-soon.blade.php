<x-app-layout title="Airing Soon">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Airing Soon</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <x-status-banner />

            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Dashboard</a>

            @if ($airingSoon->isNotEmpty())
                <x-airing-soon-grid :entries="$airingSoon" />
            @else
                <x-empty-state>
                    <p>Nothing airing soon.</p>
                </x-empty-state>
            @endif

        </div>
    </div>
</x-app-layout>
