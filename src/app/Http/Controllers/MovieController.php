<?php

namespace App\Http\Controllers;

use App\Models\UserMovie;
use App\Services\Catalog\CatalogSearchService;
use App\Support\WatchlistSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function __construct(private readonly CatalogSearchService $catalog) {}

    public function index(Request $request): View
    {
        $sort = WatchlistSort::resolve($request->string('sort')->toString());

        $watchlist = $request->user()->movies()->whereNull('watched_at')->with('movie')->get();

        $watchlist = WatchlistSort::apply(
            $watchlist,
            $sort,
            fn (UserMovie $userMovie) => $userMovie->movie->release_date,
            fn (UserMovie $userMovie) => $userMovie->movie->title,
        );

        return view('movies.index', [
            'watchlist' => $watchlist,
            'sort' => $sort,
            ...$this->catalog->forRequest($request),
        ]);
    }

    public function toggleWatched(UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('update', $userMovie);

        $userMovie->watched_at = $userMovie->watched_at ? null : now();
        $userMovie->save();

        return back();
    }

    public function destroy(UserMovie $userMovie): RedirectResponse
    {
        Gate::authorize('delete', $userMovie);

        $userMovie->delete();

        return redirect()->route('movies.index')->with('status', 'Movie removed from your list.');
    }
}
