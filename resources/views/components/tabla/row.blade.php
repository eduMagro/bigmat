@props([
    'selected' => false,
    'clickable' => true,
])

<tr {{ $attributes->merge([
    'class' => implode(' ', array_filter([
        'text-xs leading-none transition-colors',
        $clickable ? 'cursor-pointer' : '',
        $selected ? '!bg-green-100 dark:!bg-green-900' : '',
    ]))
]) }}>
    {{ $slot }}
</tr>
