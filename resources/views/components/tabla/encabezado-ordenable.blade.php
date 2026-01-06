@props(['campo', 'sortActual', 'orderActual', 'texto', 'padding' => 'p-2'])

<th class="{{ $padding }} border cursor-pointer hover:bg-blue-600 transition-colors select-none"
    wire:click="sortBy('{{ $campo }}')">
    <div class="flex items-center justify-center gap-1">
        <span>{{ $texto }}</span>
        <span class="inline-flex flex-col text-xs leading-none">
            @if($sortActual === $campo)
                @if($orderActual === 'asc')
                    <span class="text-white">▲</span>
                    <span class="text-blue-300 opacity-50">▼</span>
                @else
                    <span class="text-blue-300 opacity-50">▲</span>
                    <span class="text-white">▼</span>
                @endif
            @else
                <span class="text-blue-300 opacity-40">▲</span>
                <span class="text-blue-300 opacity-40">▼</span>
            @endif
        </span>
    </div>
</th>
