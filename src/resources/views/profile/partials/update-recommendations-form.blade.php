<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Recommendations') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Show a "Recommended for you" section on the dashboard, based on what you track.') }}
        </p>
    </header>

    <form method="POST" action="{{ route('profile.recommendations') }}" class="mt-4">
        @csrf
        @method('PATCH')

        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="recommendations_enabled" value="1"
                   @checked($user->recommendations_enabled)
                   onchange="this.form.submit()"
                   class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500">
            Show recommendations on my dashboard
        </label>

        <x-saved-flash status="recommendations-updated" class="mt-2" />
    </form>
</section>
