<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Rating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The personal rating/note write, shared by the show and movie controllers.
 * Bounds come from App\Support\Rating, which <x-rating-form> reads too, so the
 * form can't offer a value this then rejects.
 */
trait SavesRatings
{
    /** Sets or clears the user's rating/note for a tracked title — independent of TMDB's own vote_average. */
    protected function saveRating(Request $request, Model $tracked): RedirectResponse
    {
        Gate::authorize('update', $tracked);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,'.Rating::MAX],
            'note' => ['nullable', 'string', 'max:'.Rating::MAX_NOTE_LENGTH],
        ]);

        $rating = $validated['rating'] ?? null;
        $note = $validated['note'] ?? null;

        $tracked->update(['rating' => $rating, 'note' => $note]);

        return back()->with('status', $rating === null && $note === null
            ? 'Your rating was cleared.'
            : 'Your rating was saved.');
    }
}
