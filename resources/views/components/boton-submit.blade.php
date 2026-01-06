@props([
    'texto' => 'Enviar',
    'color' => 'blue',
])

<button type="submit" x-bind:disabled="cargando" x-bind:class="{ 'cursor-wait': cargando }"
    class="inline-flex items-center gap-2 rounded-md px-4 py-2 font-semibold text-white shadow
           bg-{{ $color }}-600 hover:bg-{{ $color }}-700 focus:outline-none focus:ring-2
           focus:ring-{{ $color }}-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">

    {{-- Spinner --}}
    <div x-show="cargando" class="flex space-x-1" aria-hidden="true">
        <span class="h-2 w-2 rounded-full bg-white animate-bounce [animation-delay:-0.3s]"></span>
        <span class="h-2 w-2 rounded-full bg-white animate-bounce [animation-delay:-0.15s]"></span>
        <span class="h-2 w-2 rounded-full bg-white animate-bounce"></span>
    </div>

    <span x-show="!cargando">{{ $texto }}</span>
    <span x-show="cargando">Cargando…</span>
</button>
