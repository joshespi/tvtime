{{-- The one shelf layout every card list uses — shows, movies, episodes, search
     results and history. Extracted so a breakpoint change is one edit rather
     than ten identical class strings that can silently drift apart. --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4']) }}>
    {{ $slot }}
</div>
