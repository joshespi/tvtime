<x-app-layout title="Calendar">
    <x-card padding="p-3 sm:px-5" class="flex items-center justify-between gap-2">
        <x-action-button :href="route('calendar.index', ['month' => $prevMonth])">
            &larr; <span class="sr-only sm:not-sr-only sm:ms-1">Prev</span>
        </x-action-button>

        <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-0.5 text-center">
            <x-section-heading>{{ $month->format('F Y') }}</x-section-heading>
            @unless ($isCurrentMonth)
                <x-text-link :href="route('calendar.index')">Today</x-text-link>
            @endunless
        </div>

        <x-action-button :href="route('calendar.index', ['month' => $nextMonth])">
            <span class="sr-only sm:not-sr-only sm:me-1">Next</span> &rarr;
        </x-action-button>
    </x-card>

    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 px-1">
        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm {{ \App\Support\MediaTypeStyle::classes('show') }}"></span> Episode airing</span>
        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm {{ \App\Support\MediaTypeStyle::classes('movie') }}"></span> Movie release</span>
    </div>

    {{-- Phones: a month grid of 11px labels is unreadable at 375px, so
         below `sm` the same data is a list of just the days that have
         something on them. --}}
    @php
        $agendaDays = collect($weeks)->flatten(1)->filter(
            fn ($day) => $day['inMonth'] && ($day['episodes']->isNotEmpty() || $day['movies']->isNotEmpty())
        );
    @endphp
    <div class="sm:hidden space-y-3">
        @forelse ($agendaDays as $day)
            <x-card class="flex items-start gap-4">
                <div class="w-12 shrink-0 text-center {{ $day['isToday'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300' }}">
                    <p class="text-xs uppercase tracking-wide">{{ $day['date']->format('D') }}</p>
                    <p class="text-xl font-semibold leading-tight">{{ $day['date']->day }}</p>
                </div>
                <div class="min-w-0 flex-1 space-y-1.5">
                    @foreach ($day['episodes'] as $episode)
                        <a href="{{ route('shows.show', $episode->show) }}"
                           class="block text-sm px-2 py-1 rounded hover:underline {{ \App\Support\MediaTypeStyle::classes('show') }}">
                            {{ $episode->show->title }} <span class="opacity-75">{{ $episode->code() }}</span>
                        </a>
                    @endforeach
                    @foreach ($day['movies'] as $movie)
                        <a href="{{ $movie->detailUrl() }}"
                           class="block text-sm px-2 py-1 rounded hover:underline {{ \App\Support\MediaTypeStyle::classes('movie') }}">
                            {{ $movie->title }}
                        </a>
                    @endforeach
                </div>
            </x-card>
        @empty
            <x-empty-state>Nothing airing or releasing this month.</x-empty-state>
        @endforelse
    </div>

    <x-card padding="" class="hidden sm:block overflow-hidden">
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $label)
                <div class="py-3">{{ $label }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-px bg-slate-200 dark:bg-slate-700">
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    <div class="min-w-0 min-h-28 p-2 {{ $day['isToday'] ? 'bg-indigo-50 dark:bg-[#1c2d4b]' : ($day['inMonth'] ? 'bg-white dark:bg-gray-800' : 'bg-slate-50 dark:bg-gray-900') }}">
                        <p class="text-xs font-medium mb-1 {{ $day['isToday'] ? 'inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white' : ($day['inMonth'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                            {{ $day['date']->day }}
                        </p>
                        <div class="space-y-1">
                            @foreach ($day['episodes'] as $episode)
                                <a href="{{ route('shows.show', $episode->show) }}"
                                   class="block truncate text-[11px] leading-tight px-1 py-0.5 rounded hover:underline {{ \App\Support\MediaTypeStyle::classes('show') }}"
                                   title="{{ $episode->show->title }} {{ $episode->code() }}">
                                    {{ $episode->show->title }} {{ $episode->code() }}
                                </a>
                            @endforeach
                            @foreach ($day['movies'] as $movie)
                                <a href="{{ $movie->detailUrl() }}"
                                   class="block truncate text-[11px] leading-tight px-1 py-0.5 rounded hover:underline {{ \App\Support\MediaTypeStyle::classes('movie') }}"
                                   title="{{ $movie->title }}">
                                    {{ $movie->title }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </x-card>
</x-app-layout>
