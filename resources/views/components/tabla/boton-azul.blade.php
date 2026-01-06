@props(['href' => '#', 'type' => 'button'])

<a href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge([
        'class' =>
            'inline-block text-white bg-blue-600 hover:bg-blue-700 font-semibold px-4 py-2 rounded shadow text-sm transition',
    ]) }}>
    {{ $slot }}
</a>
