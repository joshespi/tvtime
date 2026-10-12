@if (session('status'))
    <div class="bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 text-sm rounded-lg px-4 py-3 flex items-center justify-between gap-3">
        <span>{{ session('status') }}</span>
        @if (is_array($undo = session('undo')))
            {{-- Posts the opposite state explicitly, so Undo is exact even if the
                 original request was a toggle. --}}
            <form method="POST" action="{{ $undo['url'] }}" class="shrink-0">
                @csrf
                <input type="hidden" name="watched" value="{{ $undo['watched'] ? 1 : 0 }}">
                <button type="submit" class="font-semibold underline hover:no-underline">Undo</button>
            </form>
        @endif
    </div>
@endif
