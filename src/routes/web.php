<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\UpNextController;
use App\Http\Controllers\WatchStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/manifest.webmanifest', function () {
    return response()->view('manifest')->header('Content-Type', 'application/manifest+json');
})->name('manifest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', UpNextController::class)->name('dashboard');

    Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
    Route::post('/search', [SearchController::class, 'store'])->name('search.store');

    Route::get('/shows', [ShowController::class, 'index'])->name('shows.index');
    Route::get('/shows/{show}', [ShowController::class, 'show'])->name('shows.show');
    Route::delete('/shows/{userShow}', [ShowController::class, 'destroy'])->name('shows.destroy');
    Route::post('/shows/{show}/link-source', [ShowController::class, 'linkSource'])->name('shows.link-source');
    Route::post('/shows/{show}/check-conflicts', [ShowController::class, 'checkConflicts'])->name('shows.check-conflicts');

    Route::post('/episodes/{episode}/toggle-watched', [WatchStatusController::class, 'toggle'])->name('episodes.toggle-watched');
    Route::post('/episodes/{episode}/mark-through', [WatchStatusController::class, 'markThrough'])->name('episodes.mark-through');
    Route::post('/episodes/{episode}/resolve-conflict', [ShowController::class, 'resolveConflict'])->name('episodes.resolve-conflict');

    Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
    Route::post('/movies/{userMovie}/toggle-watched', [MovieController::class, 'toggleWatched'])->name('movies.toggle-watched');
    Route::delete('/movies/{userMovie}', [MovieController::class, 'destroy'])->name('movies.destroy');

    Route::get('/history', HistoryController::class)->name('history.index');

    Route::get('/calendar', CalendarController::class)->name('calendar.index');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/donate', fn () => view('donate', ['btcAddress' => config('donate.btc_address')]))->name('donate');
});

require __DIR__.'/auth.php';
