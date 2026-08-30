@props(['query' => '', 'results' => [], 'action', 'suggestUrl'])

<x-card x-data="catalogSearch(@js($suggestUrl))">
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
                              :class="suggestion.type === 'movie' ? @js(\App\Support\MediaTypeStyle::classes('movie')) : @js(\App\Support\MediaTypeStyle::classes('show'))"
                              x-text="suggestion.type === 'movie' ? @js(\App\Support\MediaTypeStyle::label('movie')) : @js(\App\Support\MediaTypeStyle::label('show'))"></span>
                    </li>
                </template>
            </ul>
        </div>
        <x-action-button variant="primary">Search</x-action-button>
    </form>
</x-card>

@if ($errors->any())
    <div class="bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 text-sm rounded-lg px-4 py-3">
        {{ $errors->first() }}
    </div>
@endif

@forelse ($results as $result)
    <x-catalog-result-card :result="$result" />
@empty
    @if ($query !== '')
        <x-empty-state>No results for "{{ $query }}".</x-empty-state>
    @endif
@endforelse
