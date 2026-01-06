@props([
    'texto' => 'Enviar',
    'color' => 'blue',
])

<button type="submit" :disabled="cargando"
    class="inline-flex items-center gap-2 rounded-md px-4 py-2 font-semibold text-white shadow
           bg-{{ $color }}-600 hover:bg-{{ $color }}-700 focus:outline-none focus:ring-2
           focus:ring-{{ $color }}-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">

    <svg x-show="cargando" class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 24 24" role="status" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.536-3.536A9 9 0 103 12h4z" />
    </svg>

    <span x-text="cargando ? 'Cargando…' : '{{ $texto }}'"></span>
</button>
