@props(['query' => '', 'results' => [], 'action'])

<x-card padding="p-2 sm:p-3" x-data="catalogSearch({{ \Illuminate\Support\Js::from(route('search.suggest')) }})">
    <form method="GET" action="{{ $action }}" role="search" class="flex gap-2 sm:gap-3" x-ref="form" @submit="open = false">
        <div class="relative min-w-0 flex-1">
            <span aria-hidden="true" class="pointer-events-none absolute start-4 top-1/2 h-3.5 w-3.5 -translate-y-1/2 rounded-full border-[1.5px] border-current text-slate-500 after:absolute after:-bottom-0.5 after:-end-1 after:h-px after:w-1.5 after:rotate-45 after:bg-current dark:text-slate-400"></span>
            <input type="text" id="catalog-search" name="q" value="{{ $query }}" x-ref="input" x-model="query"
                   @input="onInput()"
                   @keydown.down.prevent="move(1)"
                   @keydown.up.prevent="move(-1)"
                   @keydown.enter="chooseHighlighted($event)"
                   @keydown.escape="close()"
                   @focus="if (suggestions.length) open = true"
                   @click.outside="close()"
                   autocomplete="off"
                   aria-label="Search for a show or movie"
                   role="combobox" aria-autocomplete="list" aria-controls="catalog-suggestions"
                   :aria-expanded="open.toString()"
                   :aria-activedescendant="highlighted >= 0 ? 'catalog-suggestion-' + highlighted : null"
                   placeholder="Search shows or movies"
                   class="h-12 w-full scroll-mt-6 rounded-xl border-slate-200 bg-gray-50 ps-10 pe-3 text-base text-gray-900 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-slate-400 sm:text-sm">

            <ul id="catalog-suggestions" role="listbox" x-cloak x-show="open" x-transition.opacity.duration.100ms
                class="absolute z-10 mt-2 max-h-80 w-full overflow-auto rounded-xl bg-white shadow-lg ring-1 ring-slate-200 dark:bg-gray-800 dark:ring-slate-700">
                <template x-for="(suggestion, index) in suggestions" :key="suggestion.type + suggestion.id">
                    <li role="option" :id="'catalog-suggestion-' + index" :aria-selected="(highlighted === index).toString()"
                        @mousedown.prevent="select(suggestion)"
                        @mouseenter="highlighted = index"
                        :class="{ 'bg-gray-100 dark:bg-gray-700': highlighted === index }"
                        class="flex items-center gap-2 px-3 py-2 cursor-pointer">
                        <img alt="" :src="suggestion.poster_url" x-show="suggestion.poster_url" class="w-6 h-9 object-cover rounded shrink-0 bg-gray-100 dark:bg-gray-700">
                        <span class="min-w-0 flex-1 truncate text-sm text-gray-900 dark:text-gray-100" x-text="suggestion.title"></span>
                        <span class="shrink-0 text-[10px] text-gray-500 dark:text-gray-400" x-text="suggestion.date ? suggestion.date.slice(0, 4) : ''"></span>
                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-medium uppercase"
                              :class="suggestion.type === 'movie' ? @js(\App\Support\MediaTypeStyle::classes('movie')) : @js(\App\Support\MediaTypeStyle::classes('show'))"
                              x-text="suggestion.type === 'movie' ? @js(\App\Support\MediaTypeStyle::label('movie')) : @js(\App\Support\MediaTypeStyle::label('show'))"></span>
                    </li>
                </template>
            </ul>
        </div>
        <x-action-button variant="primary" size="lg">Search</x-action-button>
    </form>
</x-card>

@if ($errors->any())
    <x-status-banner variant="error">{{ $errors->first() }}</x-status-banner>
@endif

@forelse ($results as $result)
    <x-catalog-result-card :result="$result" />
@empty
    @if ($query !== '')
        <x-empty-state>No results for "{{ $query }}".</x-empty-state>
    @endif
@endforelse
