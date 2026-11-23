<x-app-layout title="Watch History">
    <x-status-banner />

    <div class="flex justify-end px-1">
        <x-text-link :href="route('history.export')">Export CSV &darr;</x-text-link>
    </div>

    @if ($history->isNotEmpty())
        <x-card-grid>
            @foreach ($history as $entry)
                <x-card class="flex items-start gap-4">
                    <x-show-poster :url="$entry['poster_url']" :alt="$entry['title']" :href="$entry['link_url']" class="w-12 h-18" />
                    <div class="min-w-0 flex-1 flex flex-col gap-2">
                        <div class="min-w-0">
                            <a href="{{ $entry['link_url'] }}" class="media-title">{{ $entry['title'] }}</a>
                            @if ($entry['subtitle'])
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $entry['subtitle'] }}</p>
                            @endif
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-xs tabular-nums text-gray-600 dark:text-gray-400">
                                <x-media-type-badge :type="$entry['type']" />
                                Watched {{ $entry['watched_at']->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($entry['actions'] as $action)
                                <form method="POST" action="{{ $action['url'] }}">
                                    @csrf
                                    @isset($action['method'])
                                        @method($action['method'])
                                    @endisset
                                    @foreach ($action['fields'] ?? [] as $name => $value)
                                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                    @endforeach
                                    <x-action-button :variant="$action['variant'] ?? 'secondary'">{{ $action['label'] }}</x-action-button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </x-card>
            @endforeach
        </x-card-grid>

        @if ($truncated)
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">Showing your latest {{ $limit }} entries — export the CSV for the full history.</p>
        @endif
    @else
        <x-empty-state>Nothing marked watched yet.</x-empty-state>
    @endif
</x-app-layout>
