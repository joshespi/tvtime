@props(['status'])

{{-- The brief "Saved." confirmation the profile forms show after a redirect
     back with their session status key. --}}
@if (session('status') === $status)
    <p
        x-data="{ show: true }"
        x-show="show"
        x-transition
        x-init="setTimeout(() => show = false, 2000)"
        {{ $attributes->merge(['class' => 'text-sm text-gray-600 dark:text-gray-400']) }}
    >{{ __('Saved.') }}</p>
@endif
