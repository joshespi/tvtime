<?php

namespace App\Http\Controllers\Concerns;

use App\Models\UserMovie;
use App\Models\UserShow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/** The "forget this title" delete, shared by the show and movie controllers. */
trait RemovesTracked
{
    protected function removeTracked(UserShow|UserMovie $tracked, string $title, string $indexRoute): RedirectResponse
    {
        Gate::authorize('delete', $tracked);

        $tracked->delete();

        return redirect()->route($indexRoute)->with('status', "\"{$title}\" removed from your list.");
    }
}
