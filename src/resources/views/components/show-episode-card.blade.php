{{-- A tracked show with one episode's details in the $meta slot, and any action
     (e.g. mark-watched) in the default slot. --}}
@props(['show'])

<x-card class="flex items-start gap-4">
    <x-show-poster :url="$show->posterUrl()" :alt="$show->title" class="w-12 h-18" :href="route('shows.show', $show)" />
    <div class="min-w-0 flex-1 flex flex-col gap-2">
        <div class="min-w-0">
            <a href="{{ route('shows.show', $show) }}" class="block font-semibold text-gray-900 dark:text-gray-100 truncate hover:underline">{{ $show->title }}</a>
            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                {{ $meta }}
            </p>
        </div>
        {{ $slot }}
    </div>
</x-card>
