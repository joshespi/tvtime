<?php

namespace App\Http\Controllers;

use App\Services\Catalog\CatalogSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpNextController extends Controller
{
    public function __construct(private readonly CatalogSearchService $catalog) {}

    /**
     * Earliest unwatched aired episode per actively-watched show, plus the full
     * movie watchlist (movies have no "next episode", so unwatched = up next).
     */
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;

        $upNext = $request->user()->shows()
            ->where('status', 'watching')
            ->with(['show.episodes' => fn ($query) => $query->unwatchedAiredFor($userId)
                ->orderBy('season')
                ->orderBy('episode_number')
                ->limit(1),
            ])
            ->get()
            ->map(fn ($userShow) => [
                'show' => $userShow->show,
                'episode' => $userShow->show->episodes->first(),
            ])
            ->filter(fn ($entry) => $entry['episode'] !== null)
            ->values();

        $movieWatchlist = $request->user()->movies()
            ->whereNull('watched_at')
            ->with('movie')
            ->get()
            ->sortByDesc('created_at')
            ->values();

        return view('up-next', [
            'upNext' => $upNext,
            'movieWatchlist' => $movieWatchlist,
            ...$this->catalog->forRequest($request),
        ]);
    }
}
