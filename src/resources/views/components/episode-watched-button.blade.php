@props(['episode', 'watched' => false, 'markThrough' => false, 'variant' => null])

{{-- The episode counterpart of <x-mark-watched-button>: form, route and caption
     together so the dashboard and the show page can't drift. $markThrough opts
     into the "mark earlier episodes too?" prompt on the show page. --}}
<form method="POST" action="{{ route('episodes.toggle-watched', $episode) }}"
    @if ($markThrough)
        data-mark-through-url="{{ route('episodes.mark-through', $episode) }}"
        onsubmit="return tvTracker.confirmMarkWatched(this)"
    @endif
>
    @csrf
    <input type="hidden" name="watched" value="{{ $watched ? 0 : 1 }}">
    <x-action-button :variant="$variant ?? ($watched ? 'secondary' : 'primary')">
        {{ $watched ? 'Mark unwatched' : 'Mark watched' }}
    </x-action-button>
</form>
