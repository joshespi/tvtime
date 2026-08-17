@props([
    'title',
    'count' => null,
    // A line under the heading explaining what lands on this shelf.
    'description' => null,
    'open' => true,
    // Set to persist the fold state across page loads, per browser.
    'storageKey' => null,
])

{{-- Native <details> rather than Alpine: it folds before any JS has run, and
     browsers give us the keyboard and screen-reader behaviour for free. --}}
<details @if ($open) open @endif {{ $attributes->merge(['class' => 'group pt-6']) }}>
    <summary class="flex cursor-pointer items-center gap-1.5 px-1 py-1 list-none text-sm font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded [&::-webkit-details-marker]:hidden">
        <svg class="h-4 w-4 shrink-0 transition-transform duration-150 group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        {{ $title }}
        @unless (is_null($count))
            <span class="font-normal normal-case">({{ $count }})</span>
        @endunless
    </summary>

    @if ($storageKey)
        {{-- Above the content on purpose: this runs as the element is parsed, so
             a remembered "closed" is applied before the cards below are laid out
             or their posters fetched — and never flashes open. currentScript is
             still this tag at parse time, so the section finds itself without
             needing a generated id. --}}
        <script>
            (function () {
                var details = document.currentScript.parentElement;
                var key = 'collapsible:' + @js($storageKey);
                var stored = localStorage.getItem(key);

                if (stored !== null) {
                    details.open = stored === '1';
                }

                details.addEventListener('toggle', function () {
                    localStorage.setItem(key, details.open ? '1' : '0');
                });
            })();
        </script>
    @endif

    <div class="pt-3 space-y-4">
        @if ($description)
            <p class="text-xs text-gray-400 dark:text-gray-500 px-1">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</details>
