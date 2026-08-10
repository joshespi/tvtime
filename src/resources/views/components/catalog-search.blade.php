@props(['query' => '', 'source' => null, 'results' => [], 'action', 'suggestUrl'])

<div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4"
     x-data="catalogSearch(@js($suggestUrl))">
    <form method="GET" action="{{ $action }}" class="flex gap-2" x-ref="form" @submit="open = false">
        <div class="relative flex-1">
            <input type="text" name="q" value="{{ $query }}" x-ref="input" x-model="query"
                   @input="onInput()"
                   @keydown.down.prevent="move(1)"
                   @keydown.up.prevent="move(-1)"
                   @keydown.enter="chooseHighlighted($event)"
                   @keydown.escape="close()"
                   @focus="if (suggestions.length) open = true"
                   @click.outside="close()"
                   autocomplete="off"
                   placeholder="Search for a show or movie…"
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">

            <ul x-show="open" x-transition.opacity.duration.100ms
                class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 rounded-md max-h-80 overflow-auto divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="(suggestion, index) in suggestions" :key="suggestion.type + suggestion.id">
                    <li @mousedown.prevent="select(suggestion)"
                        @mouseenter="highlighted = index"
                        :class="{ 'bg-gray-100 dark:bg-gray-700': highlighted === index }"
                        class="flex items-center gap-2 px-3 py-2 cursor-pointer">
                        <img :src="suggestion.poster_url" x-show="suggestion.poster_url" class="w-6 h-9 object-cover rounded shrink-0 bg-gray-100 dark:bg-gray-700">
                        <span class="min-w-0 flex-1 truncate text-sm text-gray-900 dark:text-gray-100" x-text="suggestion.title"></span>
                        <span class="shrink-0 text-[10px] text-gray-400 dark:text-gray-500" x-text="suggestion.date ? suggestion.date.slice(0, 4) : ''"></span>
                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-medium uppercase"
                              :class="suggestion.type === 'movie' ? 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'"
                              x-text="suggestion.type === 'movie' ? 'Movie' : 'TV'"></span>
                    </li>
                </template>
            </ul>
        </div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500 transition">
            Search
        </button>
    </form>
</div>

@if ($errors->any())
    <div class="bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 text-sm rounded-lg px-4 py-3">
        {{ $errors->first() }}
    </div>
@endif

@if ($source)
    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide px-1">Results via {{ $source }}</p>
@endif

@forelse ($results as $result)
    <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl px-6 py-4 flex items-start gap-4">
        <x-show-poster :url="$result['poster_url']" :alt="$result['title']" />
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
            <form method="POST" action="{{ route('search.store') }}">
                @csrf
                <input type="hidden" name="type" value="{{ $result['type'] }}">
                <input type="hidden" name="source" value="{{ $source }}">
                <input type="hidden" name="external_id" value="{{ $result['id'] }}">
                <x-action-button>{{ $result['type'] === 'movie' ? 'Add to watchlist' : 'Track' }}</x-action-button>
            </form>
        </div>
    </div>
@empty
    @if ($query !== '')
        <x-empty-state>No results for "{{ $query }}".</x-empty-state>
    @endif
@endforelse
