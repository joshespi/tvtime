@props(['sort' => 'added', 'query' => ''])

<form method="GET" class="inline-flex items-center gap-2 text-sm">
    @if ($query !== '')
        <input type="hidden" name="q" value="{{ $query }}">
    @endif
    <label for="sort" class="text-gray-500 dark:text-gray-400">Sort by</label>
    <select name="sort" id="sort" onchange="this.form.submit()"
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        <option value="added" @selected($sort === 'added')>Recently added</option>
        <option value="release" @selected($sort === 'release')>Release date</option>
        <option value="title" @selected($sort === 'title')>Title (A&ndash;Z)</option>
    </select>
</form>
