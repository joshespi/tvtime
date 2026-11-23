<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @php($description = 'Track TV shows and movies, see what’s up next, and get notified about new episodes and releases.')
        <meta name="description" content="{{ $description }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ config('app.name') }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ asset('brand/social-card.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="TV Tracker logo with the tagline Track what you watch">
        <meta name="twitter:card" content="summary_large_image">
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

        <header class="border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" aria-label="{{ config('app.name') }} home">
                    <x-application-logo class="w-24 sm:w-40" />
                </a>

                <nav class="flex items-center gap-1.5 whitespace-nowrap sm:gap-2">
                    <x-theme-toggle />

                    @auth
                        <x-action-button variant="primary" :href="url('/dashboard')">Dashboard</x-action-button>
                    @else
                        <x-action-button :href="route('login')">Log in</x-action-button>
                        @if (Route::has('register'))
                            <x-action-button variant="primary" :href="route('register')">Sign up</x-action-button>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 sm:pt-20 pb-12 sm:pb-16 text-center">
                <h1 class="text-4xl sm:text-5xl font-bold tracking-tight">
                    Keep track of what you're<br class="hidden sm:block"> actually watching.
                </h1>
                <p class="mt-5 max-w-2xl mx-auto text-lg text-gray-600 dark:text-gray-400">
                    {{ $description }}
                    Free to use, with no ads or analytics.
                </p>
                <div class="mt-8 flex items-center justify-center gap-3">
                    @auth
                        <x-action-button variant="primary" size="lg" :href="url('/dashboard')">Go to your dashboard</x-action-button>
                    @else
                        @if (Route::has('register'))
                            <x-action-button variant="primary" size="lg" :href="route('register')">Get started — it's free</x-action-button>
                        @endif
                        <x-action-button size="lg" :href="route('login')">Log in</x-action-button>
                    @endauth
                </div>

                <figure class="mt-10 sm:mt-14 mx-auto max-w-sm sm:max-w-4xl">
                    <picture class="block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg">
                        <source media="(min-width: 640px)" srcset="{{ asset('brand/dashboard-preview.webp') }}" width="1040" height="620">
                        <img src="{{ asset('brand/dashboard-preview-mobile.webp') }}" width="400" height="680"
                             alt="TV Tracker dashboard showing upcoming episodes, a movie watchlist, and Mark watched buttons."
                             class="block w-full h-auto">
                    </picture>
                    <figcaption class="mt-3 text-sm text-gray-500 dark:text-gray-400">Dashboard shown with sample data</figcaption>
                </figure>
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
                        <h3 class="mt-4 font-semibold">Track shows and movies</h3>
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                            Save titles to your watchlist and mark movies, episodes, or whole seasons watched.
                        </p>
                    </x-card>

                    <x-card padding="p-6" class="rounded-xl">
                        <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold">Get release reminders</h3>
                        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                            Get a notification when a show you follow airs a new episode or a movie on your watchlist comes out.
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
                            Prefer to run your own copy?
                            <a href="https://github.com/joshespi/tvtime" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">The code is on GitHub</a>.
                        </p>
                    </x-card>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-200 dark:border-gray-800">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ config('app.name', 'TV Tracker') }} &middot;
                <a href="https://github.com/joshespi/tvtime" rel="noopener" class="hover:underline">open source</a>
            </div>
        </footer>

    </body>
</html>
