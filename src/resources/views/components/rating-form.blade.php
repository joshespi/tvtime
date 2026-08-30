@props(['rating' => null, 'note' => null, 'action'])

{{-- Unlike <x-tracking-status-select>, this can't auto-submit on change — a
     textarea needs a deliberate save, so both fields share one explicit
     Save button instead. --}}
<form method="POST" action="{{ $action }}" class="flex flex-wrap items-start gap-3">
    @csrf
    @method('PATCH')
    <div>
        <label for="rating" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Your rating</label>
        <select name="rating" id="rating"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            @php($max = \App\Support\Rating::MAX)
            <option value="" @selected($rating === null)>Not rated</option>
            @for ($i = 1; $i <= $max; $i++)
                <option value="{{ $i }}" @selected($rating === $i)>{{ $i }}/{{ $max }}</option>
            @endfor
        </select>
    </div>
    <div class="flex-1 min-w-[12rem]">
        <label for="note" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Notes</label>
        <textarea name="note" id="note" rows="1" maxlength="{{ \App\Support\Rating::MAX_NOTE_LENGTH }}"
                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ $note }}</textarea>
    </div>
    <x-action-button class="mt-5">Save</x-action-button>
</form>
