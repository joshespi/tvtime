@props(['url' => null, 'alt' => '', 'class' => 'w-16 h-24', 'href' => null])

@if ($href)
    <a href="{{ $href }}" class="shrink-0">
@endif

@if ($url)
    <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy"
         class="{{ $class }} object-cover rounded-md shrink-0 bg-gray-100 dark:bg-gray-700">
@else
    <div class="{{ $class }} shrink-0 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-1/3 h-1/3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25V18a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18V8.25m-18 0V6a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 6v2.25m-18 0h18M8.25 21v-8.25m7.5 8.25v-8.25" />
        </svg>
    </div>
@endif

@if ($href)
    </a>
@endif
