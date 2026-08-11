<?php

namespace App\Http\Controllers;

use App\Services\Catalog\StatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function __invoke(Request $request): View
    {
        return view('profile.stats', $this->stats->forUser($request->user()));
    }
}
