<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Watch History</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <x-status-banner />

            @if ($history->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($history as $entry)
                        <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
                            <x-show-poster :url="$entry['poster_url']" :alt="$entry['title']" :href="$entry['link_url'] ?: null" class="w-12 h-18" />
                            <div class="min-w-0 flex-1 flex flex-col gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                        @if ($entry['link_url'])
                                            <a href="{{ $entry['link_url'] }}" class="hover:underline">{{ $entry['title'] }}</a>
                                        @else
                                            {{ $entry['title'] }}
                                        @endif
                                        <x-media-type-badge :type="$entry['type']" />
                                    </p>
                                    @if ($entry['subtitle'])
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $entry['subtitle'] }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                        Watched {{ $entry['watched_at']->format('M j, Y') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @foreach ($entry['actions'] as $action)
                                        <form method="POST" action="{{ $action['url'] }}">
                                            @csrf
                                            @isset($action['method'])
                                                @method($action['method'])
                                            @endisset
                                            <x-action-button :variant="$action['variant'] ?? 'secondary'">{{ $action['label'] }}</x-action-button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state>Nothing marked watched yet.</x-empty-state>
            @endif

        </div>
    </div>
</x-app-layout>
