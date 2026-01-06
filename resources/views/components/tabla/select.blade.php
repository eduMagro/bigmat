@props(['name', 'options' => [], 'selected' => null, 'empty' => 'Seleccionar...', 'label' => null])

<div class="w-full">
    @if ($label)
        <label for="{{ $name }}" class="block mb-1 text-sm font-medium text-gray-700">
            {{ $label }}
        </label>
    @endif

    <select name="{{ $name }}" id="{{ $name }}"
        {{ $attributes->merge([
            'class' =>
                'w-full px-2 py-1 border border-gray-300 rounded text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500',
        ]) }}>
        @if ($empty !== false)
            <option value="">{{ $empty }}</option>
        @endif

        @foreach ($options as $key => $labelOption)
            <option value="{{ $key }}" {{ $key == $selected ? 'selected' : '' }}>
                {{ $labelOption }}
            </option>
        @endforeach
    </select>
</div>
