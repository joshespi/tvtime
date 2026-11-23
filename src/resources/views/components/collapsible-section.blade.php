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
<details @if ($open) open @endif {{ $attributes->merge(['class' => 'group border-t border-slate-200 pt-3 dark:border-slate-700/80']) }}>
    <summary class="section-label flex min-h-9 cursor-pointer list-none items-center gap-2 rounded hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:text-indigo-300 [&::-webkit-details-marker]:hidden">
        <svg class="h-4 w-4 shrink-0 text-indigo-600 motion-safe:transition-transform group-open:rotate-90 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
        </svg>
        {{ $title }}
        @unless (is_null($count))
            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium tabular-nums text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $count }}</span>
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
                // localStorage can throw (private browsing / blocked site data)
                // rather than just returning null — either way this falls back
                // to the $open default instead of taking the page down.
                var stored = null;
                try {
                    stored = localStorage.getItem(key);
                } catch (e) {}

                if (stored !== null) {
                    details.open = stored === '1';
                }

                details.addEventListener('toggle', function () {
                    try {
                        localStorage.setItem(key, details.open ? '1' : '0');
                    } catch (e) {}
                });
            })();
        </script>
    @endif

    <div class="pt-3 space-y-4">
        @if ($description)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
        {{ $slot }}
    </div>
</details>
