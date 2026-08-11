<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\UserMovie;
use App\Notifications\MovieReleased;
use App\Services\Movies\MovieSyncService;
use App\Support\DedupedNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:check-movie-releases')]
#[Description('Refresh metadata for every tracked movie and notify users whose watchlisted movies release today')]
class CheckMovieReleases extends Command
{
    public function handle(MovieSyncService $movies): void
    {
        $tracked = Movie::query()->whereHas('userMovies')->get();

        foreach ($tracked as $movie) {
            try {
                $movies->refreshMetadata($movie);
            } catch (Throwable $e) {
                Log::error('Failed to refresh metadata for movie', ['movie_id' => $movie->id, 'message' => $e->getMessage()]);
            }
        }

        $this->info("Refreshed metadata for {$tracked->count()} movie(s).");

        $releasingToday = $tracked->filter(
            fn (Movie $movie) => $movie->release_date?->isToday()
        );

        $alreadySent = DedupedNotifier::alreadySent($releasingToday);

        // One grouped query for every movie's watchlisters, rather than one per
        // movie inside the loop below.
        $watchlistedByMovie = UserMovie::query()
            ->whereIn('movie_id', $releasingToday->pluck('id'))
            ->inWatchlist()
            ->with('user')
            ->get()
            ->groupBy('movie_id');

        $notified = 0;

        foreach ($releasingToday as $movie) {
            foreach ($watchlistedByMovie->get($movie->id, collect()) as $userMovie) {
                if (DedupedNotifier::notifyOnce($alreadySent, $userMovie->user, new MovieReleased($movie), $movie)) {
                    $notified++;
                }
            }
        }

        $this->info("Sent {$notified} notification(s).");
    }
}
