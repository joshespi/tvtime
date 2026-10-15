<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SavesRatings;
use App\Http\Controllers\Concerns\TogglesWatched;
use App\Models\MovieWatch;
use App\Models\UserMovie;
use App\Services\Catalog\CatalogSearchService;
use App\Support\GenreFilter;
use App\Support\WatchlistSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MovieController extends Controller
{
    use SavesRatings, TogglesWatched;

    public function __construct(private readonly CatalogSearchService $catalog) {}

    public function index(Request $request): View
    {
        $sort = WatchlistSort::resolve($request->string('sort')->toString());

        // withCount so a re-queued movie can say it's been seen before without
        // loading every watch row behind it.
        $movies = $request->user()->movies()->with('movie')->withCount('watches')->get();

        [$movies, $genre, $genreOptions] = GenreFilter::fromRequest(
            $request, $movies, fn (UserMovie $userMovie) => $userMovie->movie->genres,
        );

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

    /** A repeated explicit request is a no-op here, so it can't log a second viewing. */
    public function toggleWatched(Request $request, UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('update', $userMovie);

        $watched = $this->requestedWatched($request, $userMovie->isFinished());

        if ($watched !== $userMovie->isFinished()) {
            $watched ? $userMovie->markWatched() : $userMovie->markUnwatched();
        }

        return $this->withUndo(back(), "\"{$userMovie->movie->title}\"", $watched, route('movies.toggle-watched', $userMovie));
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

        // Transactional so a failure between the delete and the resync can't
        // leave the movie's watched_at flag pointing at a viewing that no
        // longer exists.
        DB::transaction(function () use ($movieWatch) {
            $userMovie = $movieWatch->userMovie;

            $movieWatch->delete();

            $userMovie->syncWatchedAt();
        });

        return back()->with('status', 'Viewing removed from your history.');
    }

    public function updateRating(Request $request, UserMovie $userMovie): RedirectResponse
    {
        return $this->saveRating($request, $userMovie);
    }

    public function destroy(UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('delete', $userMovie);

        $title = $userMovie->movie->title;
        $userMovie->delete();

        return redirect()->route('movies.index')->with('status', "\"{$title}\" removed from your list.");
    }
}
