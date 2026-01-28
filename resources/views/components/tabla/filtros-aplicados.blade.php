@props(['filtros' => []])

<div>
    @if (count($filtros))
        <div class="bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 text-blue-800 dark:text-blue-200 text-sm px-4 py-2 rounded shadow-sm mt-2 mb-4">
            <strong class="font-semibold">Filtros aplicados:</strong>
            <span>{!! implode(', ', $filtros) !!}</span>
        </div>
    @endif
</div>
