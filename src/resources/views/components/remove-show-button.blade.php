@props(['userShow'])

{{-- One place for the show-removal route and its warning copy. --}}
<x-remove-button :action="route('shows.destroy', $userShow)" confirm="Remove this show and its watch history? To just stop following it, set its status to Paused or Dropped instead." />
