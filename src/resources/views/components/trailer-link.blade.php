@props(['trailerKey' => null])

@if ($trailerKey)
    <x-action-button href="https://www.youtube.com/watch?v={{ $trailerKey }}" rel="noopener noreferrer">
        &#9654;&#65039; Trailer &#8599;
    </x-action-button>
@endif
