<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

        <header class="border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2 font-semibold text-lg">
                    <x-application-logo class="h-7 w-auto fill-current text-indigo-600 dark:text-indigo-400" />
                    {{ config('app.name', 'TV Tracker') }}
                </a>

                <nav class="flex items-center gap-2">
                    <x-theme-toggle />

                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500 transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500 transition">
                                Sign up
                            </a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
                <h1 class="text-4xl sm:text-5xl font-bold tracking-tight">
                    Keep track of what you're<br class="hidden sm:block"> actually watching.
                </h1>
                <p class="mt-5 max-w-2xl mx-auto text-lg text-gray-600 dark:text-gray-400">
                    A simple show tracker, hosted here for free. Follow your shows, mark episodes as you watch,
                    and get notified the moment something new airs — no ads, no algorithm, no tracking.
                </p>
                <div class="mt-8 flex items-center justify-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-500 transition">
                            Go to your dashboard
                        </a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-500 transition">
                                Get started — it's free
                            </a>
                        @endif
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center px-5 py-2.5 rounded-md text-sm font-semibold text-gray-700 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            Log in
                        </a>
                    @endauth
                </div>
            </section>

            <!-- Features -->
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <x-card padding="p-6" class="rounded-xl">
                        <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold">Track your shows</h3>
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                            Search and follow any show, mark episodes and whole seasons watched, and pick up right where you left off.
                        </p>
                    </x-card>

                    <x-card padding="p-6" class="rounded-xl">
                        <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold">Get notified on new episodes</h3>
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                            A daily check keeps your episode data fresh and emails you when something you follow airs.
                        </p>
                    </x-card>

                    <x-card padding="p-6" class="rounded-xl">
                        <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold">Open source &amp; ad-free</h3>
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                            No ads, no analytics, no third parties. Prefer to run your own copy?
                            <a href="https://github.com/joshespi/tvtime" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">The code is on GitHub</a>.
                        </p>
                    </x-card>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-200 dark:border-gray-800">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                {{ config('app.name', 'TV Tracker') }} &middot;
                <a href="https://github.com/joshespi/tvtime" rel="noopener" class="hover:underline">open source</a>
            </div>
        </footer>

    </body>
</html>
