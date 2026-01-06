@props([
    'type' => 'text',
    'name' => null,
    'value' => '',
    'placeholder' => '',
])

<input type="{{ $type }}" @if ($name) name="{{ $name }}" @endif
    @if ($attributes->has('id')) id="{{ $attributes->get('id') }}"
    @elseif($name)
        id="{{ $name }}" @endif
    value="{{ $name ? old($name, $value) : $value }}" placeholder="{{ $placeholder }}"
    {{ $attributes->merge([
        'class' =>
            'w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500',
        'style' => 'height:2cm; padding:0.75rem 1rem; font-size:1.5rem;',
    ]) }} />
