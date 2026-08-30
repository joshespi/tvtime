@props(['name', 'label', 'options' => [], 'selected' => null])

{{-- Auto-submitting GET select, same no-JS pattern as <x-tracking-status-select>.
     Every *other* query param on the current URL is carried forward as a hidden
     input, so filters compose without each control having to know which siblings
     exist — adding a third one needs no edit here or in the existing two. --}}
<form method="GET" class="inline-flex items-center gap-2 text-sm">
    @foreach (request()->except($name) as $key => $value)
        @if (is_scalar($value) && $value !== '')
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="{{ $name }}" class="text-gray-500 dark:text-gray-400">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}" onchange="this.form.submit()"
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <noscript><x-action-button>Apply</x-action-button></noscript>
</form>
