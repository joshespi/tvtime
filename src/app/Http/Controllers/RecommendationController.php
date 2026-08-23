<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /** Marks a recommended title "not interested" so it stops being suggested. */
    public function dismiss(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:show,movie'],
            'tmdb_id' => ['required', 'string'],
        ]);

        $request->user()->dismissedRecommendations()->firstOrCreate($validated);

        return back();
    }
}
