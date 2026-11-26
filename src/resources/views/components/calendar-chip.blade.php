@props(['entry', 'compact' => false])

{{-- One calendar entry (see CalendarController): a full-width row in the
     phone agenda, an 11px truncated chip in the desktop month grid. --}}
@php($label = trim($entry['title'].' '.$entry['code']))
<a href="{{ $entry['url'] }}"
   @if ($compact) title="{{ $label }}" @endif
   class="block rounded hover:underline {{ $compact ? 'truncate text-[11px] leading-tight px-1 py-0.5' : 'text-sm px-2 py-1' }} {{ \App\Support\MediaTypeStyle::classes($entry['type']) }}">
    @if ($compact)
        {{ $label }}
    @else
        {{ $entry['title'] }} @if ($entry['code'])<span class="opacity-75">{{ $entry['code'] }}</span>@endif
    @endif
</a>
