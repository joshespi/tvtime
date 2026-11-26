{{-- The green "✓ already in your lists" note. --}}
<span {{ $attributes->merge(['class' => 'text-xs text-emerald-700 dark:text-emerald-400']) }}>&#10003; {{ $slot }}</span>
