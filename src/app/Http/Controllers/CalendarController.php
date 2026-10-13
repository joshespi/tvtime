<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Movie;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
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
        $monthParam = $request->string('month')->toString();
        $anchor = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)
            ? Carbon::createFromFormat('Y-m-d', "{$monthParam}-01")->startOfMonth()
            : Carbon::now()->startOfMonth();

        // $anchor is already start-of-month on both branches above, so it doubles
        // as the range start.
        $end = $anchor->copy()->endOfMonth();

        // The grid shows a few days from the adjacent months to fill out whole
        // weeks — computed first so the queries can cover that same range
        // instead of just the calendar month, which silently dropped anything
        // airing on a leading/trailing grid day.
        $gridStart = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

        // IN over the user's own ids rather than whereHas() through show.userShows,
        // so the episode query stays an index range on (air_date, show_id) instead
        // of a nested EXISTS per catalog episode.
        $episodesByDate = Episode::query()
            ->airingBetween($gridStart, $gridEnd)
            ->whereIn('show_id', $request->user()->shows()->active()->select('show_id'))
            ->with('show')
            ->get()
            ->groupBy(fn (Episode $episode) => $episode->air_date->toDateString());

        $moviesByDate = Movie::query()
            ->releasingBetween($gridStart, $gridEnd)
            ->whereIn('id', $request->user()->movies()->select('movie_id'))
            ->get()
            ->groupBy(fn (Movie $movie) => $movie->release_date->toDateString());

        $days = collect(CarbonPeriod::create($gridStart, $gridEnd))->map(fn (CarbonInterface $day) => [
            'date' => $day,
            'inMonth' => $day->month === $anchor->month,
            'isToday' => $day->isToday(),
            'episodes' => $episodesByDate->get($day->toDateString(), collect()),
            'movies' => $moviesByDate->get($day->toDateString(), collect()),
        ]);

        return view('calendar.index', [
            'weeks' => $days->chunk(7)->map->values(),
            'month' => $anchor,
            'prevMonth' => $anchor->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $anchor->copy()->addMonthNoOverflow()->format('Y-m'),
            'isCurrentMonth' => $anchor->isSameMonth(Carbon::now()),
        ]);
    }
}
