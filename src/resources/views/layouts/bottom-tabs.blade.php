@php
    // Phone-only tab bar for the four places visited constantly; History and
    // Profile stay behind the hamburger. Icons are 24x24 outline paths.
    $tabs = [
        ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25'],
        ['route' => 'shows.index', 'pattern' => 'shows.*', 'label' => __('Shows'), 'icon' => 'M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z'],
        ['route' => 'movies.index', 'pattern' => 'movies.*', 'label' => __('Movies'), 'icon' => 'M3.75 4.5h16.5v15H3.75zM7.5 4.5v15M16.5 4.5v15M3.75 9h3.75M3.75 15h3.75M16.5 9h3.75M16.5 15h3.75'],
        ['route' => 'calendar.index', 'pattern' => 'calendar.*', 'label' => __('Calendar'), 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
    ];
@endphp

{{-- env(safe-area-inset-bottom) keeps the labels clear of the iPhone home
     indicator in the installed app; it is 0 everywhere else. --}}
<nav aria-label="Primary" class="sm:hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 dark:bg-gray-800/95 backdrop-blur border-t border-gray-200 dark:border-gray-700 pb-[env(safe-area-inset-bottom)]">
    <ul class="grid grid-cols-4">
        @foreach ($tabs as $tab)
            @php($active = request()->routeIs($tab['pattern']))
            <li>
                <a href="{{ route($tab['route']) }}" @if ($active) aria-current="page" @endif
                   class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium {{ $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $tab['icon'] }}" />
                    </svg>
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
