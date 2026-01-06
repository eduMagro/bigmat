@props([
    'type' => 'text',
    'name' => null,
    'value' => '',
    'placeholder' => '',
    'label' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="block mb-1 text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

 <input type="{{ $type }}"
    @if ($name) name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}" @endif
    @if (!$name) value="{{ $value }}" @endif
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge([
        'class' => 'w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500',
    ]) }} />

</div>
