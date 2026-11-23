@props(['compact' => false])

<span {{ $attributes->class('inline-flex shrink-0 items-center') }}>
    @if (config('app.brand_assets'))
        <span @class(['block w-full', 'sm:hidden lg:block' => $compact])>
            <img src="{{ asset('brand/wordmark.webp') }}" alt="" width="160" height="38" class="block h-auto w-full dark:hidden">
            <img src="{{ asset('brand/wordmark-dark.webp') }}" alt="" width="160" height="38" hidden class="h-auto w-full dark:block">
        </span>
        @if ($compact)
            <img src="{{ asset('icon-192.png') }}" alt="" width="36" height="36" hidden class="rounded-md dark:bg-gray-200 sm:block lg:hidden">
        @endif
    @else
        <img src="{{ asset('icon-192.png') }}" alt="" width="32" height="32" class="shrink-0 rounded-md dark:bg-gray-200">
        <span @class(['ms-2 truncate font-bold', 'sm:hidden lg:inline' => $compact])>{{ config('app.name') }}</span>
    @endif
</span>
