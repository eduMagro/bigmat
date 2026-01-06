@props(['filtros' => []])

<div>
    @if (count($filtros))
        <div class="bg-blue-100 border border-blue-300 text-blue-800 text-sm px-4 py-2 rounded shadow-sm mt-2 mb-4">
            <strong class="font-semibold">Filtros aplicados:</strong>
            <span>{!! implode(', ', $filtros) !!}</span>
        </div>
    @endif
</div>
