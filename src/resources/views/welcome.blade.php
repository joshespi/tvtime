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
            <section class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-2 lg:items-center lg:gap-14 lg:px-8 lg:py-20">
                <div>
                    <h1 class="font-display font-normal max-w-xl text-5xl leading-[1.02] tracking-tight sm:text-7xl">
                        Keep track of what you're <em class="text-indigo-700 dark:text-indigo-300">actually watching.</em>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-relaxed text-gray-600 dark:text-gray-300">{{ $description }} Free to use, with no ads or analytics.</p>
                    <div class="mt-7 flex flex-wrap items-center gap-3">
                        @auth
                            <x-action-button variant="primary" size="lg" :href="url('/dashboard')">Go to your dashboard</x-action-button>
                        @else
                            @if (Route::has('register'))
                                <x-action-button variant="primary" size="lg" :href="route('register')">Get started — it's free</x-action-button>
                            @endif
                            <x-action-button size="lg" :href="route('login')">Log in</x-action-button>
                        @endauth
                    </div>
                </div>
                <img src="{{ asset('brand/cinema.webp') }}" width="1200" height="800" alt="Rows of red velvet cinema seats" fetchpriority="high" class="aspect-[3/2] w-full rounded-lg object-cover lg:aspect-[5/4]">
            </section>

            <!-- Features -->
            <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
                @php
                    $features = [
                        ['icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'title' => 'Track shows and movies',
                         'body' => 'Save titles to your watchlist and mark movies, episodes, or whole seasons watched.'],
                        ['icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0', 'title' => 'Get release reminders',
                         'body' => 'Get a notification when a show you follow airs a new episode or a movie on your watchlist comes out.'],
                        ['icon' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z', 'title' => 'Open source & ad-free',
                         'body' => 'Prefer to run your own copy?', 'link' => 'https://github.com/joshespi/tvtime'],
                    ];
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    @foreach ($features as $feature)
                        <x-card padding="p-6" class="rounded-xl">
                            <div class="h-10 w-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" />
                                </svg>
                            </div>
                            <h3 class="font-display font-normal mt-5 text-2xl">{{ $feature['title'] }}</h3>
                            <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
                                {{ $feature['body'] }}
                                @isset($feature['link'])
                                    <x-text-link :href="$feature['link']" rel="noopener" size="">The code is on GitHub</x-text-link>.
                                @endisset
                            </p>
                        </x-card>
                    @endforeach
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
