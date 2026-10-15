{{-- Standalone on purpose: an error page can't assume a session, a logged-in
     user or even a database, so it takes nothing from the app layouts except
     the compiled CSS and the theme bootstrap. Links use url() rather than
     route() for the same reason. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>@yield('heading') · {{ config('app.name', 'TV Tracker') }}</title>
        @include('partials.icons')
        @include('partials.theme-init')
        {{-- A missing or broken Vite build must not turn the error page itself
             into a second error, so fall back to unstyled-but-readable HTML. --}}
        @php
            try {
                $stylesheet = app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css']);
            } catch (\Throwable) {
                $stylesheet = null;
            }
        @endphp
        {{ $stylesheet }}
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <main class="min-h-screen flex flex-col items-center justify-center px-6 pt-[env(safe-area-inset-top)] pb-[env(safe-area-inset-bottom)] text-center">
            <a href="{{ url('/') }}" class="mb-8 flex items-center gap-2 text-lg font-semibold">
                <img src="/icon-192.png" alt="" class="h-8 w-8 rounded-md">
                {{ config('app.name', 'TV Tracker') }}
            </a>
            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">@yield('code')</p>
            <h1 class="mt-2 text-2xl font-semibold">@yield('heading')</h1>
            <p class="mt-3 max-w-md text-gray-500 dark:text-gray-400">@yield('message')</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                @yield('actions')
            </div>
        </main>
    </body>
</html>
