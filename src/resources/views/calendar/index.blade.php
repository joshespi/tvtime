<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Calendar</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <div class="flex items-center justify-between">
                <a href="{{ route('calendar.index', ['month' => $prevMonth]) }}"
                   class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    &larr; <span class="hidden sm:inline ms-1">Prev</span>
                </a>

                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $month->format('F Y') }}</h3>
                    @unless ($isCurrentMonth)
                        <a href="{{ route('calendar.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Today</a>
                    @endunless
                </div>

                <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}"
                   class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    <span class="hidden sm:inline me-1">Next</span> &rarr;
                </a>
            </div>

            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 px-1">
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-400"></span> Episode airing</span>
                <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-purple-400"></span> Movie release</span>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 sm:rounded-xl overflow-hidden">
                <div class="grid grid-cols-7 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-700">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $label)
                        <div class="py-2">{{ $label }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 divide-x divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($weeks as $week)
                        @foreach ($week as $day)
                            <div class="min-h-28 p-2 {{ $day['inMonth'] ? '' : 'bg-gray-50 dark:bg-gray-900/40' }}">
                                <p class="text-xs font-medium mb-1 {{ $day['isToday'] ? 'inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white' : ($day['inMonth'] ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                                    {{ $day['date']->day }}
                                </p>
                                <div class="space-y-1">
                                    @foreach ($day['episodes'] as $episode)
                                        <a href="{{ route('shows.show', $episode->show) }}"
                                           class="block truncate text-[11px] leading-tight px-1 py-0.5 rounded bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 hover:underline"
                                           title="{{ $episode->show->title }} {{ $episode->code() }}">
                                            {{ $episode->show->title }} {{ $episode->code() }}
                                        </a>
                                    @endforeach
                                    @foreach ($day['movies'] as $movie)
                                        <p class="truncate text-[11px] leading-tight px-1 py-0.5 rounded bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400"
                                           title="{{ $movie->title }}">
                                            {{ $movie->title }}
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
