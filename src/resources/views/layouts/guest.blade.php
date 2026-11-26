<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased">
        <div class="relative min-h-screen flex flex-col justify-center items-center px-4 py-20 bg-gray-50 dark:bg-gray-900">
            {{-- Offset by the safe area: the installed iOS app draws under a translucent status bar. --}}
            <x-theme-toggle class="absolute top-[calc(1rem+env(safe-area-inset-top))] right-4" />

            <div class="app-panel grid w-full max-w-3xl overflow-hidden sm:grid-cols-[2fr_3fr]">
                <div class="relative hidden bg-gray-900 sm:block">
                    <img src="{{ asset('brand/cinema.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                </div>
                <div class="px-6 py-8 sm:p-10">
                    <a href="/" aria-label="{{ config('app.name') }} home">
                        <x-application-logo class="w-32" />
                    </a>
                    <h1 class="font-display font-normal mb-6 mt-8 text-4xl tracking-tight">{{ $title }}</h1>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
