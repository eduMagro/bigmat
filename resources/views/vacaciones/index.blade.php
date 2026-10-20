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

        {{-- Solicitudes de compensación de horas pendientes --}}
        <div id="seccion-compensaciones">
            <h3 class="text-xl font-semibold text-blue-700 mb-1">Compensaciones de horas pendientes</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Horas negativas: el trabajador se las toma libres y restan de su bolsa. Horas positivas: se le reconocen y suman.
                Un día con compensación aprobada cuenta en la bolsa por esas horas y no por su fichaje. La bolsa no puede quedar en negativo.
            </p>
            @if ($compensacionesPendientes->isEmpty())
                <p class="text-gray-600 dark:text-gray-400">No hay compensaciones pendientes.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Empleado</th>
                                <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Día</th>
                                <th class="px-4 py-2 text-right text-gray-800 dark:text-gray-100">Horas</th>
                                <th class="px-4 py-2 text-right text-gray-800 dark:text-gray-100">Bolsa actual</th>
                                <th class="px-4 py-2 text-right text-gray-800 dark:text-gray-100">Bolsa tras aprobar</th>
                                <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Comentario</th>
                                <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($compensacionesPendientes as $c)
                                @php
                                    $fmtSaldo = fn($v) => ((float) $v > 0 ? '+' : ((float) $v < 0 ? '−' : '')) . rtrim(rtrim(number_format(abs((float) $v), 2, ',', '.'), '0'), ',') . ' h';
                                    $claseSaldo = fn($v) => (float) $v > 0 ? 'text-emerald-600 dark:text-emerald-400' : ((float) $v < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500');
                                @endphp
                                <tr class="border-t border-gray-200 dark:border-gray-700 compensacion-row" data-compensacion-id="{{ $c->id }}">
                                    <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-100">
                                        <a href="{{ route('users.show', $c->user_id) }}" class="hover:underline">{{ $c->user->nombre_completo ?? $c->user->name }}</a>
                                        @if ($c->user->categoria)
                                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $c->user->categoria->nombre }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ $c->fecha->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-right font-semibold {{ $claseSaldo($c->horas) }}">{{ $c->etiquetaHoras() }}</td>
                                    <td class="px-4 py-2 text-right {{ $claseSaldo($c->bolsa_saldo) }}">{{ $fmtSaldo($c->bolsa_saldo) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold {{ $claseSaldo($c->bolsa_resultante) }}">{{ $fmtSaldo($c->bolsa_resultante) }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300 text-xs max-w-xs">{{ $c->comentario ?: '—' }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <button type="button" onclick="resolverCompensacion({{ $c->id }}, 'aprobar', this)"
                                            class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Aprobar</button>
                                        <button type="button" onclick="resolverCompensacion({{ $c->id }}, 'denegar', this)"
                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 ml-2">Denegar</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <script>
            async function resolverCompensacion(id, accion, btn) {
                const row = btn.closest('tr');
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                row.querySelectorAll('button').forEach((b) => (b.disabled = true));
                const textoOriginal = btn.textContent;
                btn.textContent = 'Procesando...';
                try {
                    const res = await fetch(`{{ url('/compensaciones-horas') }}/${id}/${accion}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) throw new Error(data.error || `HTTP ${res.status}`);
                    row.remove();
                    const tbody = document.querySelector('#seccion-compensaciones tbody');
                    if (tbody && !tbody.querySelector('tr')) {
                        document.querySelector('#seccion-compensaciones .overflow-x-auto').outerHTML =
                            '<p class="text-gray-600 dark:text-gray-400">No hay compensaciones pendientes.</p>';
                    }
                    if (window.Swal) Swal.fire({ icon: 'success', title: accion === 'aprobar' ? 'Aprobada' : 'Denegada', text: data.message || '', timer: 1800, showConfirmButton: false });
                } catch (e) {
                    row.querySelectorAll('button').forEach((b) => (b.disabled = false));
                    btn.textContent = textoOriginal;
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'No se pudo procesar la solicitud.' });
                    else alert(e.message || 'No se pudo procesar la solicitud.');
                }
            }
        </script>

        {{-- Calendario de vacaciones --}}
        <div class="w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h3 class="text-lg font-semibold text-blue-700">Calendario de Vacaciones</h3>
                <div class="flex items-center gap-2">
                    <label for="filtro-categoria" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Categoría:
                    </label>
                    <select id="filtro-categoria"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria }}">{{ $categoria }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
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

        /* Cuerpo de los eventos del calendario: nombre + categoría */
        .fc-event-cuerpo {
            padding: 1px 2px;
            overflow: hidden;
        }

        .fc-event-nombre {
            font-weight: 600;
            line-height: 1.15;
            white-space: normal;
        }

        .fc-event-categoria {
            font-size: 0.72em;
            font-weight: 400;
            opacity: 0.9;
            line-height: 1.1;
            white-space: normal;
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

        .usuario-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .usuario-nombre {
            font-weight: 500;
            color: #1f2937;
        }

        :is(.dark .usuario-nombre) {
            color: #f3f4f6;
        }

        .usuario-categoria {
            font-size: 12px;
            color: #6b7280;
        }

        :is(.dark .usuario-categoria) {
            color: #9ca3af;
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
