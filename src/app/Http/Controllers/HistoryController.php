<?php

namespace App\Http\Controllers;

use App\Models\MovieWatch;
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
        //
        // Movies come from movie_watches rather than user_movies.watched_at, so
        // a rewatch is its own entry and re-queueing a movie doesn't erase the
        // fact that it was watched.
        //
        // Each entry carries an `actions` list rather than one nullable url key
        // per button, so the two entry types only declare what they actually
        // offer and the view renders them with one loop.
        $movies = $request->user()->movieWatches()
            ->with('userMovie.movie')
            ->orderByDesc('movie_watches.watched_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (MovieWatch $watch) => [
                'type' => 'movie',
                'watched_at' => $watch->watched_at,
                'title' => $watch->userMovie->movie->title,
                'subtitle' => $watch->userMovie->movie->release_date?->format('Y'),
                'poster_url' => $watch->userMovie->movie->posterUrl(),
                'link_url' => null,
                'actions' => array_values(array_filter([
                    $watch->userMovie->isFinished()
                        ? ['label' => 'Watch again', 'url' => route('movies.watch-again', $watch->userMovie), 'variant' => 'primary']
                        : null,
                    ['label' => 'Remove entry', 'url' => route('movie-watches.destroy', $watch), 'method' => 'DELETE'],
                ])),
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
                'link_url' => route('shows.show', $watchStatus->episode->show),
                'actions' => [
                    ['label' => 'Mark unwatched', 'url' => route('episodes.toggle-watched', $watchStatus->episode)],
                ],
            ]);

        // Merged/sorted in PHP rather than a SQL UNION across two tables; fine at this scale.
        $history = $movies->concat($episodes)->sortByDesc('watched_at')->take(self::LIMIT)->values();

        return view('history.index', ['history' => $history]);
    }
}
