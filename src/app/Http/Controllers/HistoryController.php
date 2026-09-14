<?php

namespace App\Http\Controllers;

use App\Models\MovieWatch;
use App\Models\WatchStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    /** How many of the most recent entries the page shows. */
    private const LIMIT = 200;

    public function __invoke(Request $request): View
    {
        // Each entry carries an `actions` list rather than one nullable url key
        // per button, so the two entry types only declare what they actually
        // offer and the view renders them with one loop.
        $history = $this->entries($request, self::LIMIT)->map(fn (array $entry) => [
            ...Arr::except($entry, ['poster', 'link', 'actions']),
            'poster_url' => $entry['poster'](),
            'link_url' => $entry['link'](),
            'actions' => $entry['actions'](),
        ]);

        return view('history.index', ['history' => $history]);
    }

    /** Every entry, unlimited — the page caps at LIMIT for load time, but an export is a one-off request the user chose to make. */
    public function export(Request $request): StreamedResponse
    {
        // Only the four CSV columns are read here, so the posters, routes and
        // action lists the page needs are never built — at export scale that's
        // three route() calls per row that would be thrown straight away.
        $history = $this->entries($request, null);

        return response()->streamDownload(function () use ($history) {
            // escape: '' opts out of PHP's proprietary backslash escaping, which
            // isn't part of RFC 4180 and confuses Excel/Sheets on a value that
            // contains one. Passing it explicitly is also required from PHP 8.4
            // on, where relying on the default is deprecated.
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Type', 'Title', 'Details', 'Watched At'], escape: '');

            foreach ($history as $entry) {
                fputcsv($out, [
                    ucfirst($entry['type']),
                    $this->csvSafe($entry['title']),
                    $this->csvSafe($entry['subtitle']),
                    $entry['watched_at']->format('Y-m-d H:i:s'),
                ], escape: '');
            }

            fclose($out);
        }, 'watch-history-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Titles/subtitles come from TMDB, not the user, but a title that happens to
     * start with =/+/-/@ is a valid formula prefix in Excel/Sheets — prefixing it
     * with a tab defuses that without visibly changing how the cell reads.
     */
    private function csvSafe(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=+\-@]/', $value) ? "\t{$value}" : $value;
    }

    /**
     * Order + limit in SQL on both sides. The merge below can only ever
     * surface $limit rows, so hydrating a whole account's watch history to
     * throw ~all of it away is pure waste that grows with account age.
     * $limit null (the export) skips that and returns everything.
     *
     * Movies come from movie_watches rather than user_movies.watched_at, so
     * a rewatch is its own entry and re-queueing a movie doesn't erase the
     * fact that it was watched.
     *
     * The view-only fields (poster, link, actions) are returned as closures
     * rather than values so the export, which reads none of them, doesn't pay
     * to build them for every row of an uncapped history. __invoke() swaps each
     * for its resolved value before handing the rows to the view.
     */
    private function entries(Request $request, ?int $limit): Collection
    {
        $movieQuery = $request->user()->movieWatches()
            ->with('userMovie.movie')
            ->orderByDesc('movie_watches.watched_at');

        $episodeQuery = $request->user()->watchStatuses()
            ->whereNotNull('watched_at')
            ->with('episode.show')
            ->orderByDesc('watched_at');

        if ($limit !== null) {
            $movieQuery->limit($limit);
            $episodeQuery->limit($limit);
        }

        $movies = $movieQuery->get()->map(fn (MovieWatch $watch) => [
            'type' => 'movie',
            'watched_at' => $watch->watched_at,
            'title' => $watch->userMovie->movie->title,
            'subtitle' => $watch->userMovie->movie->release_date?->format('Y'),
            'poster' => fn () => $watch->userMovie->movie->posterUrl(),
            'link' => fn () => route('catalog.show', ['type' => 'movie', 'tmdbId' => $watch->userMovie->movie->tmdb_id]),
            'actions' => fn () => array_values(array_filter([
                $watch->userMovie->isFinished()
                    ? ['label' => 'Watch again', 'url' => route('movies.watch-again', $watch->userMovie), 'variant' => 'primary']
                    : null,
                ['label' => 'Remove entry', 'url' => route('movie-watches.destroy', $watch), 'method' => 'DELETE'],
            ])),
        ]);

        $episodes = $episodeQuery->get()->map(fn (WatchStatus $watchStatus) => [
            'type' => 'show',
            'watched_at' => $watchStatus->watched_at,
            'title' => $watchStatus->episode->show->title,
            'subtitle' => $watchStatus->episode->label(),
            'poster' => fn () => $watchStatus->episode->show->posterUrl(),
            'link' => fn () => route('shows.show', $watchStatus->episode->show),
            'actions' => fn () => [
                ['label' => 'Mark unwatched', 'url' => route('episodes.toggle-watched', $watchStatus->episode)],
            ],
        ]);

        // Merged/sorted in PHP rather than a SQL UNION across two tables; fine at this scale.
        $history = $movies->concat($episodes)->sortByDesc('watched_at');

        return $limit !== null ? $history->take($limit)->values() : $history->values();
    }
}
