@props(['padding' => 'px-6 py-4'])

{{-- The panel surface every list card, detail header and stat tile sits on.
     Extracted for the same reason as <x-card-grid>: a ring, radius or dark-mode
     tweak is one edit rather than thirty identical class strings that can
     silently drift apart. Padding is a prop rather than a merged class so a
     caller that wants different padding replaces it outright instead of
     emitting two competing values. --}}
<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 rounded-xl '.$padding]) }}>
    {{ $slot }}
</div>
