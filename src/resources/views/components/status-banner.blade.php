@if (session('status'))
    <div class="bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 text-sm rounded-lg px-4 py-3">
        {{ session('status') }}
    </div>
@endif
