@props(['userShow'])

{{-- Auto-submitting GET-style select, same pattern as <x-query-select>: no JS
     dependency, and no separate "save" button to hunt for. --}}
<form method="POST" action="{{ route('shows.update-status', $userShow) }}" class="inline-flex">
    @csrf
    @method('PATCH')
    <label for="status-{{ $userShow->id }}" class="sr-only">Tracking status for {{ $userShow->show->title }}</label>
    <select name="status" id="status-{{ $userShow->id }}" onchange="this.form.submit()"
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs py-2 pl-3 pr-8">
        @foreach (\App\Support\TrackingStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected($userShow->status === $status)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <noscript><x-action-button class="ml-2">Save</x-action-button></noscript>
</form>
