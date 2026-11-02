@props(['genreOptions', 'genre', 'sort'])

{{-- Watch-history link plus the genre/sort filters shared by /shows and /movies. --}}
<div class="flex items-center justify-between gap-2 px-1 flex-wrap">
    <x-text-link :href="route('history.index')">Watch history &rarr;</x-text-link>
    <div class="flex flex-wrap items-center gap-3">
        <x-query-select name="genre" label="Genre" :options="$genreOptions" :selected="$genre" />
        <x-query-select name="sort" label="Sort by" :options="\App\Support\WatchlistSort::options()" :selected="$sort" />
    </div>
</div>
