<?php

namespace App\Http\Controllers;

use App\Models\UserMovie;
use App\Models\UserShow;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Movies\MovieNotFoundException;
use App\Services\Movies\MovieSyncService;
use App\Services\Shows\ShowNotFoundException;
use App\Services\Shows\ShowSyncService;
use App\Support\TrackingStatus;
use App\Support\WatchCount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SearchController extends Controller
{
    public function __construct(
        private readonly CatalogSearchService $catalog,
        private readonly ShowSyncService $shows,
        private readonly MovieSyncService $movies,
    ) {}

    /**
     * Backs the typeahead dropdown; full result cards are rendered server-side
     * by the embedding page via CatalogSearchService::forRequest() instead.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->catalog->search($query);

        return response()->json(
            collect($results)
                ->take(8)
                ->map(fn (array $result) => Arr::only($result, ['type', 'id', 'title', 'date', 'poster_url']))
                ->values()
        );
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:show,movie'],
            'external_id' => ['required', 'string'],
        ]);

        if ($validated['type'] === 'movie') {
            return $this->storeMovie($request, $validated['external_id']);
        }

        return $this->storeShow($request, $validated['external_id']);
    }

    private function storeShow(Request $request, string $externalId): RedirectResponse|JsonResponse
    {
        try {
            $show = $this->shows->store($externalId);
        } catch (ShowNotFoundException) {
            return $this->respondError($request, 'Could not find that show on TMDB.');
        }

        UserShow::firstOrCreate([
            'user_id' => $request->user()->id,
            'show_id' => $show->id,
        ], [
            'status' => TrackingStatus::Watching,
        ]);

        return $this->respondSuccess($request, "Now tracking \"{$show->title}\".", 'shows.index');
    }

    private function storeMovie(Request $request, string $externalId): RedirectResponse|JsonResponse
    {
        try {
            $movie = $this->movies->store($externalId);
        } catch (MovieNotFoundException) {
            return $this->respondError($request, 'Could not find that movie on TMDB.');
        }

        $userMovie = UserMovie::firstOrCreate([
            'user_id' => $request->user()->id,
            'movie_id' => $movie->id,
        ]);

        // Adding a movie that was already watched is a rewatch, not a no-op:
        // without this the button would report success while the movie stayed
        // on the Finished shelf and never reappeared on the watchlist.
        if ($userMovie->isFinished()) {
            $watches = $userMovie->timesWatched();
            $userMovie->requeue();

            return $this->respondSuccess(
                $request,
                "\"{$movie->title}\" is back on your watchlist — you've watched it ".WatchCount::phrase($watches).' before.',
                'movies.index',
            );
        }

        return $this->respondSuccess($request, "Added \"{$movie->title}\" to your watchlist.", 'movies.index');
    }

    private function respondError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['message' => $message], 422)
            : back()->withErrors(['external_id' => $message]);
    }

    private function respondSuccess(Request $request, string $message, string $route): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : redirect()->route($route)->with('status', $message);
    }
}
