@props(['entries'])

{{-- Shared between the dashboard's trimmed preview and the full airing-soon
     list, so the card markup can't drift between the two. --}}
<x-card-grid>
    @foreach ($entries as $entry)
        <x-show-episode-card :show="$entry['show']">
            <x-slot:meta>
                <span class="basis-full">{{ $entry['episode']->label() }}</span>
                <x-countdown :date="$entry['episode']->air_date" class="mt-1" />
            </x-slot:meta>
        </x-show-episode-card>
    @endforeach
</x-card-grid>
