<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="font-sans text-gray-900 dark:text-gray-100 antialiased">
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-[calc(1.5rem+env(safe-area-inset-top))] sm:pt-0 bg-gray-50 dark:bg-gray-900">
            <x-theme-toggle class="absolute top-4 right-4" />

            <div>
                <a href="/" aria-label="{{ config('app.name') }} home">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-gray-400" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md dark:shadow-none ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
