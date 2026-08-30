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
        $anchor = preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? Carbon::createFromFormat('Y-m-d', "{$monthParam}-01")->startOfMonth()
            : Carbon::now()->startOfMonth();

        // $anchor is already start-of-month on both branches above, so it doubles
        // as the range start.
        $end = $anchor->copy()->endOfMonth();

        $episodesByDate = Episode::query()
            ->whereBetween('air_date', [$anchor->toDateString(), $end->toDateString()])
            ->whereHas('show.userShows', fn ($query) => $query->where('user_id', $userId)->active())
            ->with('show')
            ->get()
            ->groupBy(fn (Episode $episode) => $episode->air_date->toDateString());

        $moviesByDate = Movie::query()
            ->whereBetween('release_date', [$anchor->toDateString(), $end->toDateString()])
            ->whereHas('userMovies', fn ($query) => $query->where('user_id', $userId))
            ->get()
            ->groupBy(fn (Movie $movie) => $movie->release_date->toDateString());

        $weeks = [];
        $cursor = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

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
