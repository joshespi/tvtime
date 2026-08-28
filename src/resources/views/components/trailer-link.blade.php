@props(['trailerKey'])

@if ($trailerKey)
    <x-action-button variant="secondary" href="https://www.youtube.com/watch?v={{ $trailerKey }}"
        target="_blank" rel="noopener noreferrer">
        &#9654;&#65039; Watch trailer
    </x-action-button>
@endif
