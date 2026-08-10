<?php

namespace App\Http\Controllers;

use App\Models\UserMovie;
use App\Models\UserShow;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Movies\MovieNotFoundException;
use App\Services\Movies\MovieSyncService;
use App\Services\Shows\ShowNotFoundException;
use App\Services\Shows\ShowSyncService;
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

        $results = $this->catalog->search($query)['results'];

        return response()->json(
            collect($results)
                ->take(8)
                ->map(fn (array $result) => Arr::only($result, ['type', 'id', 'title', 'date', 'poster_url']))
                ->values()
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:show,movie'],
            'source' => ['required', 'in:tmdb,trakt'],
            'external_id' => ['required', 'string'],
        ]);

        if ($validated['type'] === 'movie') {
            return $this->storeMovie($request, $validated['source'], $validated['external_id']);
        }

        return $this->storeShow($request, $validated['source'], $validated['external_id']);
    }

    private function storeShow(Request $request, string $source, string $externalId): RedirectResponse
    {
        try {
            $show = $this->shows->store($source, $externalId);
        } catch (ShowNotFoundException) {
            return back()->withErrors(['external_id' => 'Could not find that show on TMDB or Trakt.']);
        }

        UserShow::firstOrCreate([
            'user_id' => $request->user()->id,
            'show_id' => $show->id,
        ], [
            'status' => 'watching',
        ]);

        return redirect()->route('shows.index')->with('status', "Now tracking \"{$show->title}\".");
    }

    private function storeMovie(Request $request, string $source, string $externalId): RedirectResponse
    {
        try {
            $movie = $this->movies->store($source, $externalId);
        } catch (MovieNotFoundException) {
            return back()->withErrors(['external_id' => 'Could not find that movie on TMDB or Trakt.']);
        }

        UserMovie::firstOrCreate([
            'user_id' => $request->user()->id,
            'movie_id' => $movie->id,
        ]);

        return redirect()->route('movies.index')->with('status', "Added \"{$movie->title}\" to your watchlist.");
    }
}
