@props(['padding' => 'p-4 sm:p-5'])

{{-- The panel surface every list card, detail header and stat tile sits on.
     Extracted for the same reason as <x-card-grid>: a ring, radius or dark-mode
     tweak is one edit rather than thirty identical class strings that can
     silently drift apart. Padding is a prop rather than a merged class so a
     caller that wants different padding replaces it outright instead of
     emitting two competing values. --}}
<div {{ $attributes->merge(['class' => 'app-panel min-w-0 '.$padding]) }}>
    {{ $slot }}
</div>
