<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

// Temporary diagnostic endpoint for the Android-installed-PWA force-close bug:
// app.js beacons uncaught errors/rejections here via navigator.sendBeacon, which
// still fires even if the page is being torn down, so a crash-causing exception
// lands in laravel.log instead of being lost with no console access on-device.
class ClientErrorController extends Controller
{
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:500'],
            'line' => ['nullable', 'integer'],
            'column' => ['nullable', 'integer'],
            'stack' => ['nullable', 'string', 'max:4000'],
            'url' => ['nullable', 'string', 'max:500'],
            'standalone' => ['nullable', 'boolean'],
        ]);

        Log::channel('single')->warning('client-error', $validated + [
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
        ]);

        return response()->noContent();
    }
}
