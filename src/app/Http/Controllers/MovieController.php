<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SavesRatings;
use App\Models\MovieWatch;
use App\Models\UserMovie;
use App\Services\Catalog\CatalogSearchService;
use App\Support\GenreFilter;
use App\Support\WatchlistSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MovieController extends Controller
{
    use SavesRatings;

    public function __construct(private readonly CatalogSearchService $catalog) {}

    public function index(Request $request): View
    {
        $sort = WatchlistSort::resolve($request->string('sort')->toString());

        // withCount so a re-queued movie can say it's been seen before without
        // loading every watch row behind it.
        $movies = $request->user()->movies()->with('movie')->withCount('watches')->get();

        $genresOf = fn (UserMovie $userMovie) => $userMovie->movie->genres ?? [];

        // Options are read off the full set, before filtering, so picking a
        // genre never removes it from its own dropdown — and they're what the
        // requested genre is validated against.
        $genreOptions = GenreFilter::options($movies, $genresOf);
        $genre = GenreFilter::resolve($request->string('genre')->toString(), $genreOptions);

        $movies = GenreFilter::apply($movies, $genre, $genresOf);

        [$finished, $watchlist] = $movies->partition(fn (UserMovie $userMovie) => $userMovie->isFinished());

        $watchlist = WatchlistSort::apply(
            $watchlist,
            $sort,
            fn (UserMovie $userMovie) => $userMovie->movie->release_date,
            fn (UserMovie $userMovie) => $userMovie->movie->title,
        );

        return view('movies.index', [
            'watchlist' => $watchlist,
            // The Finished shelf is always most-recently-watched first: the sort
            // control is about deciding what to watch next, which this isn't.
            'finished' => $finished->sortByDesc('watched_at')->values(),
            'sort' => $sort,
            'genre' => $genre,
            'genreOptions' => $genreOptions,
            ...$this->catalog->forRequest($request),
        ]);
    }

    public function toggleWatched(UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('update', $userMovie);

        $userMovie->isFinished() ? $userMovie->markUnwatched() : $userMovie->markWatched();

        return back();
    }

    /** Puts a finished movie back on the watchlist without losing that it was seen. */
    public function watchAgain(UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('update', $userMovie);

        $userMovie->requeue();

        return back()->with('status', "\"{$userMovie->movie->title}\" is back on your watchlist.");
    }

    /** Drops one viewing from the watch history. */
    public function destroyWatch(MovieWatch $movieWatch): RedirectResponse
    {
        Gate::authorize('delete', $movieWatch);

        $userMovie = $movieWatch->userMovie;

        $movieWatch->delete();

        $userMovie->syncWatchedAt();

        return back();
    }

    public function updateRating(Request $request, UserMovie $userMovie): RedirectResponse
    {
        return $this->saveRating($request, $userMovie);
    }

    public function destroy(UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('delete', $userMovie);

        $userMovie->delete();

        return redirect()->route('movies.index')->with('status', 'Movie removed from your list.');
    }
}
