<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The watched/unwatched toggle shared by episodes and movies. A toggle flips the
 * current state unless the request names the state it wants (`watched=1|0`).
 * The forms always name it: a double-tap or a retried request then lands on the
 * same state instead of cancelling itself out, and the Undo button in
 * <x-status-banner> can say exactly what to restore.
 */
trait TogglesWatched
{
    /** @param  bool|Closure(): bool  $current  The state now — a closure when finding it out costs a query, so it's only run when needed. */
    protected function requestedWatched(Request $request, bool|Closure $current): bool
    {
        $request->validate(['watched' => ['nullable', 'boolean']]);

        return $request->has('watched') ? $request->boolean('watched') : ! value($current);
    }

    /** Flashes the confirmation and the `undo` payload <x-status-banner> renders. */
    protected function withUndo(RedirectResponse $response, string $what, bool $watched, string $toggleUrl): RedirectResponse
    {
        return $response
            ->with('status', "{$what} marked ".($watched ? 'watched' : 'unwatched').'.')
            ->with('undo', ['url' => $toggleUrl, 'watched' => ! $watched]);
    }
}
