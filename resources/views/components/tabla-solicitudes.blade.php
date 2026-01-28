@props(['solicitudes' => collect()])

@if ($solicitudes->isEmpty())
    <p class="text-gray-600 dark:text-gray-400">No hay solicitudes pendientes.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Empleado</th>
                    <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Vacaciones</th>
                    <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Desde</th>
                    <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Hasta</th>
                    <th class="px-4 py-2 text-left text-gray-800 dark:text-gray-100">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($solicitudes as $solicitud)
                    @php
                        $usadas = $solicitud->vacaciones_usadas ?? 0;
                        $totales = $solicitud->vacaciones_totales ?? 30;
                        $restantes = $solicitud->vacaciones_restantes ?? ($totales - $usadas);

                        // Clase de color según disponibilidad
                        if ($restantes <= 0) {
                            $badgeClass = 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
                        } elseif ($restantes <= 5) {
                            $badgeClass = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
                        } else {
                            $badgeClass = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
                        }
                    @endphp
                    <tr class="border-t border-gray-200 dark:border-gray-700 solicitud-row" data-solicitud-id="{{ $solicitud->id }}">
                        <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-100">{{ $solicitud->user->nombre_completo }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                {{ $usadas }}/{{ $totales }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">({{ $restantes }} disp.)</span>
                        </td>
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            <button type="button"
                                onclick="aprobarSolicitud({{ $solicitud->id }}, this)"
                                class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 btn-aprobar">
                                Aprobar
                            </button>
                            <button type="button"
                                onclick="denegarSolicitud({{ $solicitud->id }}, this)"
                                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 ml-2 btn-denegar">
                                Denegar
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
