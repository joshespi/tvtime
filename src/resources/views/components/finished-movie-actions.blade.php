@props(['userMovie'])

{{-- The finished-movie action pair — requeue or undo — shared by the Finished
     shelf on /movies and the catalog detail page, so the two can't drift on
     wording or which routes they post to. --}}
<form method="POST" action="{{ route('movies.watch-again', $userMovie) }}">
    @csrf
    <x-action-button variant="primary">Watch again</x-action-button>
</form>
<form method="POST" action="{{ route('movies.toggle-watched', $userMovie) }}">
    @csrf
    <input type="hidden" name="watched" value="0">
    <x-action-button>Mark unwatched</x-action-button>
</form>
