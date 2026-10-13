<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ClientErrorController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UpNextController;
use App\Http\Controllers\WatchStatusController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/manifest.webmanifest', function () {
    return response()->view('manifest')->header('Content-Type', 'application/manifest+json');
})->name('manifest');

Route::post('/client-errors', [ClientErrorController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('client-errors.store');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', UpNextController::class)->name('dashboard');
    Route::get('/airing-soon', [UpNextController::class, 'airingSoon'])->name('airing-soon.index');

    Route::get('/search/suggest', [SearchController::class, 'suggest'])
        ->middleware('throttle:30,1')
        ->name('search.suggest');
    Route::post('/search', [SearchController::class, 'store'])->name('search.store');

    Route::get('/catalog/{type}/{tmdbId}', [CatalogController::class, 'show'])
        ->where('type', 'show|movie')
        ->name('catalog.show');

    Route::post('/recommendations/dismiss', [RecommendationController::class, 'dismiss'])->name('recommendations.dismiss');

    Route::get('/shows', [ShowController::class, 'index'])->name('shows.index');
    Route::get('/shows/{show}', [ShowController::class, 'show'])->name('shows.show');
    Route::patch('/shows/{userShow}/status', [ShowController::class, 'updateStatus'])->name('shows.update-status');
    Route::patch('/shows/{userShow}/rating', [ShowController::class, 'updateRating'])->name('shows.update-rating');
    Route::delete('/shows/{userShow}', [ShowController::class, 'destroy'])->name('shows.destroy');

    Route::post('/episodes/{episode}/toggle-watched', [WatchStatusController::class, 'toggle'])->name('episodes.toggle-watched');
    Route::post('/episodes/{episode}/mark-through', [WatchStatusController::class, 'markThrough'])->name('episodes.mark-through');
    Route::post('/shows/{show}/seasons/{season}/mark-watched', [WatchStatusController::class, 'markSeason'])
        ->whereNumber('season')
        ->name('seasons.mark-watched');

    Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
    Route::post('/movies/{userMovie}/toggle-watched', [MovieController::class, 'toggleWatched'])->name('movies.toggle-watched');
    Route::post('/movies/{userMovie}/watch-again', [MovieController::class, 'watchAgain'])->name('movies.watch-again');
    Route::patch('/movies/{userMovie}/rating', [MovieController::class, 'updateRating'])->name('movies.update-rating');
    Route::delete('/movies/{userMovie}', [MovieController::class, 'destroy'])->name('movies.destroy');
    Route::delete('/movie-watches/{movieWatch}', [MovieController::class, 'destroyWatch'])->name('movie-watches.destroy');

    Route::get('/history', HistoryController::class)->name('history.index');
    Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');

    Route::get('/calendar', CalendarController::class)->name('calendar.index');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/recommendations', [ProfileController::class, 'updateRecommendations'])->name('profile.recommendations');
    Route::get('/profile/stats', StatsController::class)->name('profile.stats');

    Route::get('/donate', fn () => view('donate', ['btcAddress' => config('donate.btc_address')]))->name('donate');
});

require __DIR__.'/auth.php';
