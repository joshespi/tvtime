<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Shows\TmdbClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * "See more details" for a title reached from search results or recommendations
 * — both of which only ever hand back a TMDB id, since neither guarantees the
 * title has been synced into the local shows/movies tables yet.
 */
class CatalogController extends Controller
{
    /** How long a fetched detail page is reused before re-hitting TMDB. */
    private const CACHE_TTL_HOURS = 6;

    public function __construct(
        private readonly TmdbClient $tmdb,
        private readonly CatalogSearchService $catalog,
    ) {}

    public function show(Request $request, string $type, string $tmdbId): View|RedirectResponse
    {
        return $type === 'movie'
            ? $this->movie($request, $tmdbId)
            : $this->tvShow($request, $tmdbId);
    }

    /**
     * A show already being tracked has a much richer page — full season/episode
     * lists and watched-state — so send it there instead of duplicating that view
     * here with less information.
     */
    private function tvShow(Request $request, string $tmdbId): View|RedirectResponse
    {
        $userShow = $this->catalog->trackedByTmdbId($request->user()->shows(), 'show', [$tmdbId])->first();

        if ($userShow) {
            return redirect()->route('shows.show', $userShow->show_id);
        }

        $details = $this->fetch('show', $tmdbId);

        abort_if($details === null, 404);

        return view('catalog.show', ['tmdbId' => $tmdbId, 'details' => $details]);
    }

    /**
     * Movies have no separate tracked-detail page (unlike shows, there's no
     * per-episode data to show), so this page doubles as that for a movie the
     * user already tracks — $userMovie carries the watchlist/watched actions.
     *
     * A tracked movie's metadata is read from its already-synced local row
     * rather than TMDB again — MovieSyncService is the thing that keeps that
     * row current, and re-fetching independently here (on its own 6h cache)
     * would give /catalog/movie/X and /movies two separate copies of the same
     * title's overview/rating that can silently drift apart.
     */
    private function movie(Request $request, string $tmdbId): View
    {
        $userMovie = $this->catalog
            ->trackedByTmdbId($request->user()->movies()->withCount('watches'), 'movie', [$tmdbId])
            ->first();

        if ($userMovie) {
            // Not redundant with the eager load above: trackedByTmdbId() selects
            // only id+tmdb_id (all it needs to match), so the metadata columns
            // detailsFromMovie() reads have to be fetched here.
            $userMovie->load('movie');
            $details = $this->detailsFromMovie($userMovie->movie);
        } else {
            $details = $this->fetch('movie', $tmdbId);
        }

        abort_if($details === null, 404);

        return view('catalog.movie', ['tmdbId' => $tmdbId, 'details' => $details, 'userMovie' => $userMovie]);
    }

    /** @return array{title: string, poster_path: ?string, overview: ?string, status: ?string, vote_average: ?float, genres: array<int, string>, release_date: ?string, cast: array, trailer_key: ?string} */
    private function detailsFromMovie(Movie $movie): array
    {
        return [
            'title' => $movie->title,
            'poster_path' => $movie->poster_path,
            'overview' => $movie->overview,
            'status' => $movie->status,
            'vote_average' => $movie->vote_average,
            'genres' => $movie->genres ?? [],
            'release_date' => $movie->release_date?->toDateString(),
            'cast' => $movie->cast ?? [],
            'trailer_key' => $movie->trailer_key,
        ];
    }

    private function fetch(string $type, string $tmdbId): ?array
    {
        return Cache::remember(
            "catalog:{$type}:{$tmdbId}",
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => $type === 'movie' ? $this->tmdb->movie($tmdbId) : $this->tmdb->show($tmdbId)
        );
    }
}
