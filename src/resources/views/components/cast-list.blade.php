@props(['cast' => []])

@if (! empty($cast))
    <div class="mt-4">
        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Cast</h3>
        <div class="flex gap-3 overflow-x-auto pb-1">
            @foreach ($cast as $member)
                <div class="shrink-0 w-20 text-center">
                    @if ($member['profile_path'] ?? null)
                        <img src="{{ \App\Support\TmdbImage::url($member['profile_path'], 'w185') }}"
                             alt="{{ $member['name'] }}" loading="lazy"
                             class="w-16 h-16 mx-auto rounded-full object-cover bg-gray-100 dark:bg-gray-700">
                    @else
                        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-1/3 h-1/3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                    @endif
                    <p class="text-xs font-medium text-gray-900 dark:text-gray-100 mt-1 truncate" title="{{ $member['name'] }}">
                        @if ($imdbUrl = \App\Support\ImdbLink::personUrl($member['imdb_id'] ?? null))
                            <a href="{{ $imdbUrl }}" rel="noopener noreferrer" class="hover:underline">{{ $member['name'] }}</a>
                        @else
                            {{ $member['name'] }}
                        @endif
                    </p>
                    @if (! empty($member['character']))
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate" title="{{ $member['character'] }}">{{ $member['character'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
