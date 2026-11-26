@props(['userShow'])

{{-- Auto-submitting GET-style select, same pattern as <x-query-select>: no JS
     dependency, and no separate "save" button to hunt for. --}}
<form method="POST" action="{{ route('shows.update-status', $userShow) }}" class="inline-flex">
    @csrf
    @method('PATCH')
    <label for="status-{{ $userShow->id }}" class="sr-only">Tracking status for {{ $userShow->show->title }}</label>
    <select name="status" id="status-{{ $userShow->id }}" onchange="this.form.submit()"
            class="form-control text-xs py-2 pl-3 pr-8">
        @foreach (\App\Support\TrackingStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected($userShow->status === $status)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <noscript><x-action-button class="ml-2">Save</x-action-button></noscript>
</form>
