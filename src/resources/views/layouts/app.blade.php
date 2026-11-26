<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
            @include('layouts.navigation')

            <main class="flex-1 min-w-0">
                <div class="{{ $width }} mx-auto px-4 pt-7 sm:px-6 sm:pt-10 lg:px-8">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h1 class="masthead page-title">{{ $heading ?? $title }}</h1>
                    @endisset

                    <div class="pt-5 pb-8 sm:pt-6 sm:pb-10 {{ $spacing }}">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="pt-6 pb-[calc(5.5rem+env(safe-area-inset-bottom))] sm:pb-6 text-center text-xs text-gray-500 dark:text-gray-400 space-x-2">
                <a href="{{ route('donate') }}" class="hover:underline">Donate</a>
                <span>&middot;</span>
                <span>v{{ config('app.version') }}</span>
            </footer>

            @include('layouts.bottom-tabs')
        </div>
    </body>
</html>
