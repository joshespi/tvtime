@props(['userMovie', 'variant' => 'secondary'])

{{-- The whole "I watched this" control — form, route and caption together, so
     the watchlist and the dashboard can't drift on the wording or post to
     different places. The caption owns the rewatch case: a movie that's been
     seen before is being watched *again*. --}}
<form method="POST" action="{{ route('movies.toggle-watched', $userMovie) }}">
    @csrf
    <x-action-button :variant="$variant">
        {{ $userMovie->timesWatched() ? 'Mark watched again' : 'Mark watched' }}
    </x-action-button>
</form>
