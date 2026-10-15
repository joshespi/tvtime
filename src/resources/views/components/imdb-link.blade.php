@props(['imdbId' => null])

@if ($imdbUrl = \App\Support\ImdbLink::titleUrl($imdbId))
    <x-action-button href="{{ $imdbUrl }}" rel="noopener noreferrer">
        IMDb &#8599;
    </x-action-button>
@endif
