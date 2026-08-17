@props(['result'])

<div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4"
     x-data="trackButton(@js(route('search.store')), @js($result['type']), @js($result['id']))">
    {{-- ?? null tolerates search results cached before poster_url moved into TmdbClient::mapResult(). --}}
    <x-show-poster :url="$result['poster_url'] ?? null" :alt="$result['title']" />
    <div class="min-w-0 flex-1 flex flex-col gap-2">
        <div class="min-w-0">
            <p class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                {{ $result['title'] }}
                <x-media-type-badge :type="$result['type']" />
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                @if ($result['date'])
                    <span>{{ \Illuminate\Support\Carbon::parse($result['date'])->format('Y') }}</span>
                @endif
                @if ($result['vote_average'])
                    <span>&middot; &#9733; {{ number_format($result['vote_average'], 1) }}</span>
                @endif
            </p>
            @if (! empty($result['genres']))
                <p class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ implode(', ', $result['genres']) }}</p>
            @endif
            @if ($result['overview'])
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $result['overview'] }}</p>
            @endif
        </div>
        @if ($tracked = $result['tracked'] ?? null)
            {{-- Already in the user's lists: say so instead of offering to add it
                 again, which previously read as if it had never been watched. --}}
            <div class="flex items-center gap-3">
                <span class="text-xs text-emerald-700 dark:text-emerald-400">&#10003; {{ $tracked['label'] }}</span>
                @if ($action = $tracked['action'] ?? null)
                    @if ($action['post'])
                        <form method="POST" action="{{ $action['url'] }}">
                            @csrf
                            <x-action-button>{{ $action['label'] }}</x-action-button>
                        </form>
                    @else
                        <a href="{{ $action['url'] }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">{{ $action['label'] }}</a>
                    @endif
                @endif
            </div>
        @else
            <div>
                <form method="POST" action="{{ route('search.store') }}" @submit.prevent="track()">
                    @csrf
                    <input type="hidden" name="type" value="{{ $result['type'] }}">
                    <input type="hidden" name="external_id" value="{{ $result['id'] }}">
                    <button type="submit" :disabled="status === 'loading' || status === 'done'"
                            class="{{ \App\Support\ButtonStyle::BASE }} gap-1 disabled:opacity-75"
                            :class="status === 'done' ? @js(\App\Support\ButtonStyle::variant('success')) : @js(\App\Support\ButtonStyle::variant('secondary'))">
                        <span x-show="status === 'idle' || status === 'error'">{{ $result['type'] === 'movie' ? 'Add to watchlist' : 'Track' }}</span>
                        <span x-show="status === 'loading'">Working&hellip;</span>
                        <span x-show="status === 'done'">&#10003; {{ $result['type'] === 'movie' ? 'Added' : 'Tracking' }}</span>
                    </button>
                </form>
                <p x-show="status === 'error'" x-text="message" class="text-xs text-red-600 dark:text-red-400 mt-1"></p>
            </div>
        @endif
    </div>
</div>
