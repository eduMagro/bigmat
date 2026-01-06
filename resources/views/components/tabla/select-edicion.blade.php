@props([])

<select
    {{ $attributes->merge([
        'class' =>
            'w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500',
    ]) }}>
    {{ $slot }}
</select>
