{{-- A tracked show with one episode's details in the $meta slot, and any action
     (e.g. mark-watched) in the default slot. --}}
@props(['show'])

<x-card class="flex items-start gap-4">
    <x-show-poster :url="$show->posterUrl()" :alt="$show->title" class="w-12 h-18" :href="route('shows.show', $show)" />
    <div class="min-w-0 flex-1 flex flex-col gap-2">
        <div class="min-w-0">
            <a href="{{ route('shows.show', $show) }}" class="media-title">{{ $show->title }}</a>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm leading-snug text-gray-600 dark:text-gray-400">
                {{ $meta }}
            </p>
        </div>
        {{ $slot }}
    </div>
</x-card>
