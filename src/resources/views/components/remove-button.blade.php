@props(['action', 'confirm'])

{{-- DELETE form with a confirm() prompt. The prompt text goes through Js::from
     so quotes in it can't break out of the onsubmit attribute. --}}
<form method="POST" action="{{ $action }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from($confirm) }})">
    @csrf
    @method('DELETE')
    <x-action-button>Remove</x-action-button>
</form>
