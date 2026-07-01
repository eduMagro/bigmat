<x-app-layout>
    <x-slot name="title">Calendario de Vacaciones</x-slot>

    <div class="w-full max-w-7xl mx-auto py-6 space-y-8" id="contenedorCalendarios">

        {{-- Solicitudes pendientes --}}
        <div>
            <h3 class="text-xl font-semibold text-blue-700 mb-4">Solicitudes pendientes</h3>
            @if ($solicitudesPendientes->isEmpty())
                <p class="text-gray-600 dark:text-gray-400">No hay solicitudes pendientes.</p>
            @else
                <x-tabla-solicitudes :solicitudes="$solicitudesPendientes" />
            @endif
        </div>

        {{-- Calendario de vacaciones --}}
        <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-blue-700 mb-4">Calendario de Vacaciones</h3>
            <div id="calendario-vacaciones"></div>
        </div>
    </div>

    {{-- Configuración para el módulo del calendario (URLs + eventos de respaldo) --}}
    <script type="application/json" id="vacaciones-config">
        {!! json_encode([
            'urls' => [
                'sobrantes' => url('/vacaciones/sobrantes-anio-anterior'),
                'vacaciones' => url('/vacaciones'),
                'usuariosConVacaciones' => url('/vacaciones/usuarios-con-vacaciones'),
                'asignarDirecto' => url('/vacaciones/asignar-directo'),
                'eventos' => url('/vacaciones/eventos'),
                'reprogramar' => url('/vacaciones/reprogramar'),
                'eliminarEvento' => url('/vacaciones/eliminar-evento'),
                'users' => url('/users'),
            ],
            'eventosFallback' => $eventos,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @vite('resources/js/vistas/vacaciones/index.js')

    <style>
        /* Estilos para seleccion de rango */
        .fc .bg-select-range {
            background: rgba(99, 102, 241, 0.25) !important;
            border-radius: 4px;
        }

        .fc .bg-select-endpoint {
            background: rgba(99, 102, 241, 0.45) !important;
        }

        .fc .bg-select-endpoint-left {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            border-left: 3px solid rgba(99, 102, 241, 0.8);
            box-shadow: -4px 0 8px rgba(99, 102, 241, 0.4);
        }

        .fc .bg-select-endpoint-right {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: 3px solid rgba(99, 102, 241, 0.8);
            box-shadow: 4px 0 8px rgba(99, 102, 241, 0.4);
        }

        .fc .fc-daygrid-day-bg {
            overflow: visible;
        }

        .fc .bg-select-range,
        .fc .bg-select-endpoint {
            pointer-events: none !important;
        }

        /* Estilos para el selector de usuario en SweetAlert */
        .usuario-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.15s;
        }

        :is(.dark .usuario-item) {
            border-bottom-color: #374151;
        }

        .usuario-item:hover {
            background-color: #f3f4f6;
        }

        :is(.dark .usuario-item:hover) {
            background-color: #374151;
        }

        .usuario-item.selected {
            background-color: #dbeafe;
            border-color: #3b82f6;
        }

        :is(.dark .usuario-item.selected) {
            background-color: #1e3a5f;
            border-color: #3b82f6;
        }

        .usuario-item:last-child {
            border-bottom: none;
        }

        .usuario-nombre {
            font-weight: 500;
            color: #1f2937;
        }

        :is(.dark .usuario-nombre) {
            color: #f3f4f6;
        }

        .usuario-vacaciones {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 600;
        }

        .vacaciones-ok {
            background-color: #d1fae5;
            color: #065f46;
        }

        .vacaciones-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .vacaciones-full {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .lista-usuarios {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 12px;
        }

        :is(.dark .lista-usuarios) {
            border-color: #374151;
            background-color: #1f2937;
        }

        .buscador-usuarios {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        :is(.dark .buscador-usuarios) {
            border-color: #374151;
            background-color: #1f2937;
            color: #f3f4f6;
        }

        .buscador-usuarios:focus {
            outline: none;
            border-color: #3b82f6;
        }

        :is(.dark .buscador-usuarios:focus) {
            border-color: #3b82f6;
        }

        .info-seleccion {
            background: linear-gradient(135deg, #1e3a5f 0%, #111827 100%);
            color: white;
            margin: -20px -20px 20px -20px;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
    </style>



</x-app-layout>
