@props(['userShow'])

{{-- Shared by all three shelves on /shows (Tracking, Finished, Not Tracking).
     The shelf a card sits on carries how closely the show is being followed, so
     the card itself doesn't restate the status. --}}
<x-card class="flex items-start gap-4">
    <x-show-poster :url="$userShow->show->posterUrl()" :alt="$userShow->show->title"
                   :href="route('shows.show', $userShow->show)" />
    <div class="min-w-0 flex-1 flex flex-col gap-2">
        <div class="min-w-0">
            <a href="{{ route('shows.show', $userShow->show) }}" class="block font-semibold text-gray-900 dark:text-gray-100 truncate hover:underline">{{ $userShow->show->title }}</a>
            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                {{ $userShow->status->label() }}
                @if ($userShow->show->status)
                    · {{ $userShow->show->status }}
                @endif
                @if ($userShow->show->vote_average)
                    · &#9733; {{ number_format($userShow->show->vote_average, 1) }}
                @endif
                @if ($userShow->rating)
                    · Your rating: {{ $userShow->rating }}/10
                @endif
            </p>
            @if (! empty($userShow->show->genres))
                <p class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ implode(', ', $userShow->show->genres) }}</p>
            @endif
            @if ($userShow->show->nextUpcomingEpisode)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                    <span>Next: {{ $userShow->show->nextUpcomingEpisode->code() }}</span>
                    <x-countdown :date="$userShow->show->nextUpcomingEpisode->air_date" />
                </p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <x-tracking-status-select :user-show="$userShow" />
            <form method="POST" action="{{ route('shows.destroy', $userShow) }}" onsubmit="return confirm('Remove this show and its watch history? To just stop following it, set its status to Paused or Dropped instead.')">
                @csrf
                @method('DELETE')
                <x-action-button>Remove</x-action-button>
            </form>
        </div>
    </div>
</x-card>
