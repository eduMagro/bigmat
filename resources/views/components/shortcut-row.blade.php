@props(['keys', 'description'])

@php
    // Detectar el tipo de separador
    $hasPlus = str_contains($keys, '+');
    $hasSlash = str_contains($keys, ' / ');
    $hasSpace = !$hasPlus && !$hasSlash && str_contains($keys, ' ');

    if ($hasSlash) {
        $parts = explode(' / ', $keys);
        $separator = '/';
    } elseif ($hasPlus) {
        $parts = explode('+', $keys);
        $separator = '+';
    } elseif ($hasSpace) {
        $parts = explode(' ', $keys);
        $separator = ' ';
    } else {
        $parts = [$keys];
        $separator = '';
    }
@endphp

<div class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
    <div class="flex items-center gap-1">
        @foreach($parts as $key)
            @if(!$loop->first && $separator)
                <span class="text-gray-400 text-xs">{{ $separator }}</span>
            @endif
            <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm text-gray-700 dark:text-gray-300 font-mono text-xs min-w-[24px] text-center">{{ trim($key) }}</kbd>
        @endforeach
    </div>
    <span class="text-gray-600 dark:text-gray-400 text-sm ml-4">{{ $description }}</span>
</div>
