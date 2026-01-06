@props(['solicitudes' => collect()])

@if ($solicitudes->isEmpty())
    <p class="text-gray-600">No hay solicitudes pendientes.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Empleado</th>
                    <th class="px-4 py-2 text-left">Desde</th>
                    <th class="px-4 py-2 text-left">Hasta</th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($solicitudes as $solicitud)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $solicitud->user->nombre_completo }}</td>
                        <td class="px-4 py-2">{{ $solicitud->fecha_inicio }}</td>
                        <td class="px-4 py-2">{{ $solicitud->fecha_fin }}</td>
                        <td class="px-4 py-2">
                            <form action="{{ route('vacaciones.editarAprobar', $solicitud->id) }}" method="POST"
                                class="inline">
                                @csrf
                                <button type="submit"
                                    class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                    ✅ Aprobar
                                </button>
                            </form>
                            <form action="{{ route('vacaciones.editarDenegar', $solicitud->id) }}" method="POST"
                                class="inline ml-2">
                                @csrf
                                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                    ❌ Denegar
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
