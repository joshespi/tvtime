@props(['type'])

<span {{ $attributes->merge(['class' => 'ms-1 align-middle inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium uppercase tracking-wide '.($type === 'movie' ? 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400')]) }}>
    {{ $type === 'movie' ? 'Movie' : 'TV' }}
</span>
