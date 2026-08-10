<?php

namespace App\Http\Controllers;

use App\Models\WatchStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /** How many of the most recent entries the page shows. */
    private const LIMIT = 200;

    public function __invoke(Request $request): View
    {
        // Order + limit in SQL on both sides. The merge below can only ever
        // surface LIMIT rows, so hydrating a whole account's watch history to
        // throw ~all of it away is pure waste that grows with account age.
        $movies = $request->user()->movies()
            ->whereNotNull('watched_at')
            ->with('movie')
            ->orderByDesc('watched_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($userMovie) => [
                'type' => 'movie',
                'watched_at' => $userMovie->watched_at,
                'title' => $userMovie->movie->title,
                'subtitle' => $userMovie->movie->release_date?->format('Y'),
                'poster_url' => $userMovie->movie->posterUrl(),
                'toggle_url' => route('movies.toggle-watched', $userMovie),
                'link_url' => null,
            ]);

        $episodes = $request->user()->watchStatuses()
            ->whereNotNull('watched_at')
            ->with('episode.show')
            ->orderByDesc('watched_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (WatchStatus $watchStatus) => [
                'type' => 'show',
                'watched_at' => $watchStatus->watched_at,
                'title' => $watchStatus->episode->show->title,
                'subtitle' => $watchStatus->episode->label(),
                'poster_url' => $watchStatus->episode->show->posterUrl(),
                'toggle_url' => route('episodes.toggle-watched', $watchStatus->episode),
                'link_url' => route('shows.show', $watchStatus->episode->show),
            ]);

        // Merged/sorted in PHP rather than a SQL UNION across two tables; fine at this scale.
        $history = $movies->concat($episodes)->sortByDesc('watched_at')->take(self::LIMIT)->values();

        return view('history.index', ['history' => $history]);
    }
}
