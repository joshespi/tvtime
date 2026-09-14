<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Month-grid of episode air dates and movie release dates across everything
     * tracked, regardless of whether it's been watched — "what's coming up"
     * doesn't care what's already watched. Shows the user has paused or dropped
     * are left out entirely, though: they've said they aren't following those.
     */
    public function __invoke(Request $request): View
    {
        $userId = $request->user()->id;

        $monthParam = $request->string('month')->toString();
        $anchor = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)
            ? Carbon::createFromFormat('Y-m-d', "{$monthParam}-01")->startOfMonth()
            : Carbon::now()->startOfMonth();

        // $anchor is already start-of-month on both branches above, so it doubles
        // as the range start.
        $end = $anchor->copy()->endOfMonth();

        // The grid shows a few days from the adjacent months to fill out whole
        // weeks (see the loop below) — computed first so the queries can cover
        // that same range instead of just the calendar month, which silently
        // dropped anything airing on a leading/trailing grid day.
        $gridStart = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

        $episodesByDate = Episode::query()
            ->whereBetween('air_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->whereHas('show.userShows', fn ($query) => $query->where('user_id', $userId)->active())
            ->with('show')
            ->get()
            ->groupBy(fn (Episode $episode) => $episode->air_date->toDateString());

        $moviesByDate = Movie::query()
            ->whereBetween('release_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->whereHas('userMovies', fn ($query) => $query->where('user_id', $userId))
            ->get()
            ->groupBy(fn (Movie $movie) => $movie->release_date->toDateString());

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();

                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $anchor->month,
                    'isToday' => $cursor->isToday(),
                    'episodes' => $episodesByDate->get($key, collect()),
                    'movies' => $moviesByDate->get($key, collect()),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return view('calendar.index', [
            'weeks' => $weeks,
            'month' => $anchor,
            'prevMonth' => $anchor->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $anchor->copy()->addMonthNoOverflow()->format('Y-m'),
            'isCurrentMonth' => $anchor->isSameMonth(Carbon::now()),
        ]);
    }
}
