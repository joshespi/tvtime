@props(['entries'])

{{-- Shared between the dashboard's trimmed preview and the full airing-soon
     list, so the card markup can't drift between the two. --}}
<x-card-grid>
    @foreach ($entries as $entry)
        <x-show-episode-card :show="$entry['show']">
            <x-slot:meta>
                <span>{{ $entry['episode']->label() }}</span>
                <x-countdown :date="$entry['episode']->air_date" />
            </x-slot:meta>
        </x-show-episode-card>
    @endforeach
</x-card-grid>
