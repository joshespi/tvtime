@props(['result', 'dismissible' => false])

@php
    $detailUrl = route('catalog.show', ['type' => $result['type'], 'tmdbId' => $result['id']]);
@endphp

<x-card class="flex items-start gap-4">
    <x-show-poster :url="$result['poster_url']" :alt="$result['title']" :href="$detailUrl" class="w-16 h-24 sm:w-[4.5rem] sm:h-[6.75rem]" />
    <div class="min-w-0 flex-1 flex flex-col gap-3 self-stretch">
        <div class="min-w-0">
            <a href="{{ $detailUrl }}" class="media-title">{{ $result['title'] }}</a>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs tabular-nums text-gray-600 dark:text-gray-400">
                <x-media-type-badge :type="$result['type']" />
                @if ($result['date'])
                    <span>{{ \Illuminate\Support\Carbon::parse($result['date'])->format('Y') }}</span>
                @endif
                @if ($result['vote_average'])
                    <span><span aria-hidden="true" class="text-amber-600 dark:text-amber-300">&#9733;</span> <span class="sr-only">Community rating</span> {{ number_format($result['vote_average'], 1) }}</span>
                @endif
            </p>
            <x-genre-list :genres="$result['genres']" />
            @if ($result['overview'])
                <p class="mt-2 line-clamp-2 text-sm leading-snug text-gray-600 dark:text-gray-400">{{ $result['overview'] }}</p>
            @endif
        </div>
        <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1">
            @if ($tracked = $result['tracked'] ?? null)
                {{-- Already in the user's lists: say so instead of offering to add it
                     again, which previously read as if it had never been watched. --}}
                <x-tracked-label>{{ $tracked['label'] }}</x-tracked-label>
                @if ($action = $tracked['action'] ?? null)
                    @if ($action['post'])
                        <form method="POST" action="{{ $action['url'] }}">
                            @csrf
                            <x-action-button>{{ $action['label'] }}</x-action-button>
                        </form>
                    @else
                        <x-text-link :href="$action['url']" size="text-xs">{{ $action['label'] }}</x-text-link>
                    @endif
                @endif
            @else
                <x-track-button :type="$result['type']" :tmdb-id="$result['id']" />
                @if ($dismissible)
                    {{-- Plain form, no Alpine — "not interested" just reloads the
                         section with the card gone, same as every other list action. --}}
                    <form method="POST" action="{{ route('recommendations.dismiss') }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $result['type'] }}">
                        <input type="hidden" name="tmdb_id" value="{{ $result['id'] }}">
                        <button type="submit" class="flex min-h-10 items-center rounded px-1 text-xs text-gray-600 hover:text-gray-900 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:text-gray-400 dark:hover:text-gray-200 sm:min-h-8">
                            Not interested
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</x-card>
