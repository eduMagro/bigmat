<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use Carbon\Carbon;

class ProduccionController extends Controller
{
    /**
     * Vista de planificacion de trabajadores
     * Muestra un calendario con los TRABAJADORES como recursos (filas)
     * y las asignaciones de turno como eventos
     */
    public function trabajadores()
    {
        // Filtrar por visibilidad del usuario actual
        $usuariosVisiblesIds = auth()->user()->getUsuariosVisiblesIds();

        // Obtener trabajadores activos - seran los recursos (filas) del calendario
        $trabajadores = User::where('estado', 'activo')
            ->visiblesPara(auth()->user())
            ->with('categoria')
            ->orderBy('name')
            ->get();

        // Obtener turnos para mostrar en la leyenda
        $turnos = Turno::activos()->ordenados()->get();

        // Horas reales fichadas (semana y mes) por trabajador
        $resumenHoras = \App\Services\ComputoHorasService::semanaYMes($trabajadores->pluck('id'));

        // Crear recursos para el calendario (cada trabajador es una fila)
        $recursos = $trabajadores->map(function ($trabajador, $index) use ($resumenHoras) {
            $h = $resumenHoras[$trabajador->id] ?? null;
            return [
                'id' => $trabajador->id,
                'title' => $trabajador->nombre_completo ?? $trabajador->name,
                'orden' => $index,
                'foto' => $trabajador->ruta_imagen ?? null,
                'categoria' => $trabajador->categoria?->nombre,
                'horas_semana' => $h['semana']['horas'] ?? 0,
                'horas_mes' => $h['mes']['horas'] ?? 0,
            ];
        })->values();

        // Rango de fechas para eventos
        $fechaHoy = Carbon::today()->subWeek();
        $fechaLimite = $fechaHoy->copy()->addDays(60);

        // Obtener asignaciones (filtradas por visibilidad)
        $asignaciones = AsignacionTurno::with(['user.categoria', 'turno'])
            ->whereBetween('fecha', [$fechaHoy, $fechaLimite])
            ->when($usuariosVisiblesIds !== null, fn($q) => $q->whereIn('user_id', $usuariosVisiblesIds))
            ->get();

        $eventos = [];

        // Colores por estado especial
        $coloresEstado = [
            'vacaciones'    => ['bg' => '#f87171', 'border' => '#dc2626'],
            'baja'          => ['bg' => '#FF8C00', 'border' => '#FF6600'],
            'justificada'   => ['bg' => '#32CD32', 'border' => '#228B22'],
            'injustificada' => ['bg' => '#DC143C', 'border' => '#B22222'],
            'curso'         => ['bg' => '#8B5CF6', 'border' => '#7C3AED'],
        ];

        // Colores pastel para eventos normales (por turno)
        $coloresPorTurno = [];
        $coloresPastel = [
            ['bg' => '#93C5FD', 'border' => '#60A5FA'], // blue
            ['bg' => '#86EFAC', 'border' => '#4ADE80'], // green
            ['bg' => '#FDE047', 'border' => '#FACC15'], // yellow
            ['bg' => '#FDA4AF', 'border' => '#FB7185'], // rose
            ['bg' => '#C4B5FD', 'border' => '#A78BFA'], // violet
            ['bg' => '#67E8F9', 'border' => '#22D3EE'], // cyan
            ['bg' => '#FDBA74', 'border' => '#FB923C'], // orange
        ];

        foreach ($turnos as $index => $turno) {
            $coloresPorTurno[$turno->id] = $turno->color
                ? ['bg' => $turno->color, 'border' => $this->darkenColor($turno->color)]
                : $coloresPastel[$index % count($coloresPastel)];
        }

        foreach ($asignaciones as $asignacion) {
            $trabajador = $asignacion->user;
            $turno = $asignacion->turno;

            if (!$trabajador) continue;

            $estado = $asignacion->estado ?? 'activo';

            // Estados especiales (vacaciones, baja...) pueden existir sin turno
            // (p. ej. fines de semana); solo se descartan las activas sin turno
            if (!$turno && $estado === 'activo') continue;

            $fechaStr = $asignacion->fecha->format('Y-m-d');

            // El resourceId es el ID del trabajador
            $resourceId = $trabajador->id;

            // Determinar color segun estado o turno
            $mostrarEstado = $estado !== 'activo';
            if ($mostrarEstado && isset($coloresEstado[$estado])) {
                $color = $coloresEstado[$estado];
            } else {
                $color = $turno ? ($coloresPorTurno[$turno->id] ?? $coloresPastel[0]) : $coloresPastel[0];
            }

            // Formatear entrada/salida reales
            $entrada = $mostrarEstado
                ? ucfirst($estado)
                : ($asignacion->entrada ? Carbon::parse($asignacion->entrada)->format('H:i') : null);

            $salida = $mostrarEstado
                ? null
                : ($asignacion->salida ? Carbon::parse($asignacion->salida)->format('H:i') : null);

            // Entrada2/Salida2 para turnos partidos
            $entrada2 = $asignacion->entrada2 ? Carbon::parse($asignacion->entrada2)->format('H:i') : null;
            $salida2 = $asignacion->salida2 ? Carbon::parse($asignacion->salida2)->format('H:i') : null;

            // Calcular start y end basados en el turno
            $horaInicio = $turno?->hora_inicio ?? '00:00:00';
            $horaFin = $turno?->hora_fin ?? '23:59:59';

            // Si el turno cruza medianoche (ej: 22:00 - 06:00), el end es al dia siguiente
            $startDateTime = $fechaStr . 'T' . substr($horaInicio, 0, 5) . ':00';
            if ($horaFin < $horaInicio) {
                // Turno nocturno: termina al dia siguiente
                $fechaSiguiente = $asignacion->fecha->copy()->addDay()->format('Y-m-d');
                $endDateTime = $fechaSiguiente . 'T' . substr($horaFin, 0, 5) . ':00';
            } else {
                $endDateTime = $fechaStr . 'T' . substr($horaFin, 0, 5) . ':00';
            }

            $eventos[] = [
                'id' => 'asig-' . $asignacion->id,
                'title' => $turno?->nombre ?? ucfirst($estado),
                'start' => $startDateTime,
                'end' => $endDateTime,
                'resourceId' => $resourceId,
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => '#000000',
                'extendedProps' => [
                    'asignacion_id' => $asignacion->id,
                    'user_id' => $trabajador->id,
                    'turno_id' => $asignacion->turno_id,
                    'turno_nombre' => $turno?->nombre ?? ucfirst($estado),
                    'estado' => $estado,
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'entrada2' => $entrada2,
                    'salida2' => $salida2,
                    'foto' => $trabajador->ruta_imagen ?? null,
                    'categoria' => $trabajador->categoria?->nombre,
                    'hora_inicio' => substr($horaInicio, 0, 5),
                    'hora_fin' => substr($horaFin, 0, 5),
                ],
            ];
        }

        // Obtener festivos
        $resourceIds = $recursos->pluck('id')->toArray();
        $festivosEventos = collect(Festivo::eventosCalendario())
            ->map(function ($e) use ($resourceIds) {
                $fecha = Carbon::parse($e['start'])->format('Y-m-d');

                return [
                    'id'              => $e['id'],
                    'title'           => $e['title'],
                    'start'           => $fecha,
                    'end'             => $fecha,
                    'resourceIds'     => $resourceIds,
                    'backgroundColor' => '#ef4444',
                    'borderColor'     => '#dc2626',
                    'textColor'       => '#ffffff',
                    'editable'        => false,
                    'classNames'      => ['evento-festivo'],
                    'extendedProps'   => [
                        'es_festivo' => true,
                        'festivo_id' => $e['extendedProps']['festivo_id'] ?? null,
                    ],
                ];
            })
            ->toArray();

        $todosEventos = array_merge($eventos, $festivosEventos);

        return view('produccion.trabajadores', compact('recursos', 'todosEventos', 'turnos', 'trabajadores'));
    }

    /**
     * Obtiene los datos del calendario via AJAX (para refrescar sin recargar)
     */
    public function datosCalendario()
    {
        $usuariosVisiblesIds = auth()->user()->getUsuariosVisiblesIds();

        $trabajadores = User::where('estado', 'activo')
            ->visiblesPara(auth()->user())
            ->with('categoria')
            ->orderBy('name')
            ->get();

        $turnos = Turno::activos()->ordenados()->get();

        $resumenHoras = \App\Services\ComputoHorasService::semanaYMes($trabajadores->pluck('id'));

        $recursos = $trabajadores->map(function ($trabajador, $index) use ($resumenHoras) {
            $h = $resumenHoras[$trabajador->id] ?? null;
            return [
                'id' => $trabajador->id,
                'title' => $trabajador->nombre_completo ?? $trabajador->name,
                'orden' => $index,
                'foto' => $trabajador->ruta_imagen ?? null,
                'categoria' => $trabajador->categoria?->nombre,
                'horas_semana' => $h['semana']['horas'] ?? 0,
                'horas_mes' => $h['mes']['horas'] ?? 0,
            ];
        })->values();

        $fechaHoy = Carbon::today()->subWeek();
        $fechaLimite = $fechaHoy->copy()->addDays(60);

        $asignaciones = AsignacionTurno::with(['user.categoria', 'turno'])
            ->whereBetween('fecha', [$fechaHoy, $fechaLimite])
            ->when($usuariosVisiblesIds !== null, fn($q) => $q->whereIn('user_id', $usuariosVisiblesIds))
            ->get();

        $eventos = [];

        $coloresEstado = [
            'vacaciones'    => ['bg' => '#f87171', 'border' => '#dc2626'],
            'baja'          => ['bg' => '#FF8C00', 'border' => '#FF6600'],
            'justificada'   => ['bg' => '#32CD32', 'border' => '#228B22'],
            'injustificada' => ['bg' => '#DC143C', 'border' => '#B22222'],
            'curso'         => ['bg' => '#8B5CF6', 'border' => '#7C3AED'],
        ];

        $coloresPorTurno = [];
        $coloresPastel = [
            ['bg' => '#93C5FD', 'border' => '#60A5FA'],
            ['bg' => '#86EFAC', 'border' => '#4ADE80'],
            ['bg' => '#FDE047', 'border' => '#FACC15'],
            ['bg' => '#FDA4AF', 'border' => '#FB7185'],
            ['bg' => '#C4B5FD', 'border' => '#A78BFA'],
            ['bg' => '#67E8F9', 'border' => '#22D3EE'],
            ['bg' => '#FDBA74', 'border' => '#FB923C'],
        ];

        foreach ($turnos as $index => $turno) {
            $coloresPorTurno[$turno->id] = $turno->color
                ? ['bg' => $turno->color, 'border' => $this->darkenColor($turno->color)]
                : $coloresPastel[$index % count($coloresPastel)];
        }

        foreach ($asignaciones as $asignacion) {
            $trabajador = $asignacion->user;
            $turno = $asignacion->turno;

            if (!$trabajador) continue;

            $estado = $asignacion->estado ?? 'activo';

            // Estados especiales (vacaciones, baja...) pueden existir sin turno
            // (p. ej. fines de semana); solo se descartan las activas sin turno
            if (!$turno && $estado === 'activo') continue;

            $fechaStr = $asignacion->fecha->format('Y-m-d');
            $resourceId = $trabajador->id;

            $mostrarEstado = $estado !== 'activo';
            if ($mostrarEstado && isset($coloresEstado[$estado])) {
                $color = $coloresEstado[$estado];
            } else {
                $color = $turno ? ($coloresPorTurno[$turno->id] ?? $coloresPastel[0]) : $coloresPastel[0];
            }

            $entrada = $mostrarEstado
                ? ucfirst($estado)
                : ($asignacion->entrada ? Carbon::parse($asignacion->entrada)->format('H:i') : null);

            $salida = $mostrarEstado
                ? null
                : ($asignacion->salida ? Carbon::parse($asignacion->salida)->format('H:i') : null);

            $entrada2 = $asignacion->entrada2 ? Carbon::parse($asignacion->entrada2)->format('H:i') : null;
            $salida2 = $asignacion->salida2 ? Carbon::parse($asignacion->salida2)->format('H:i') : null;

            $horaInicio = $turno?->hora_inicio ?? '00:00:00';
            $horaFin = $turno?->hora_fin ?? '23:59:59';

            $startDateTime = $fechaStr . 'T' . substr($horaInicio, 0, 5) . ':00';
            if ($horaFin < $horaInicio) {
                $fechaSiguiente = $asignacion->fecha->copy()->addDay()->format('Y-m-d');
                $endDateTime = $fechaSiguiente . 'T' . substr($horaFin, 0, 5) . ':00';
            } else {
                $endDateTime = $fechaStr . 'T' . substr($horaFin, 0, 5) . ':00';
            }

            $eventos[] = [
                'id' => 'asig-' . $asignacion->id,
                'title' => $turno?->nombre ?? ucfirst($estado),
                'start' => $startDateTime,
                'end' => $endDateTime,
                'resourceId' => $resourceId,
                'backgroundColor' => $color['bg'],
                'borderColor' => $color['border'],
                'textColor' => '#000000',
                'extendedProps' => [
                    'asignacion_id' => $asignacion->id,
                    'user_id' => $trabajador->id,
                    'turno_id' => $asignacion->turno_id,
                    'turno_nombre' => $turno?->nombre ?? ucfirst($estado),
                    'estado' => $estado,
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'entrada2' => $entrada2,
                    'salida2' => $salida2,
                    'foto' => $trabajador->ruta_imagen ?? null,
                    'categoria' => $trabajador->categoria?->nombre,
                    'hora_inicio' => substr($horaInicio, 0, 5),
                    'hora_fin' => substr($horaFin, 0, 5),
                ],
            ];
        }

        $resourceIds = $recursos->pluck('id')->toArray();
        $festivosEventos = collect(Festivo::eventosCalendario())
            ->map(function ($e) use ($resourceIds) {
                $fecha = Carbon::parse($e['start'])->format('Y-m-d');
                return [
                    'id'              => $e['id'],
                    'title'           => $e['title'],
                    'start'           => $fecha,
                    'end'             => $fecha,
                    'resourceIds'     => $resourceIds,
                    'backgroundColor' => '#ef4444',
                    'borderColor'     => '#dc2626',
                    'textColor'       => '#ffffff',
                    'editable'        => false,
                    'classNames'      => ['evento-festivo'],
                    'extendedProps'   => [
                        'es_festivo' => true,
                        'festivo_id' => $e['extendedProps']['festivo_id'] ?? null,
                    ],
                ];
            })
            ->toArray();

        $todosEventos = array_merge($eventos, $festivosEventos);

        return response()->json([
            'recursos' => $recursos,
            'eventos' => $todosEventos,
            'turnos' => $turnos->map(fn($t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
                'hora_inicio' => $t->hora_inicio,
                'hora_fin' => $t->hora_fin,
                'color' => $t->color ?? '#93C5FD',
            ])->values(),
        ]);
    }

    /**
     * Oscurecer un color hex para el borde
     */
    private function darkenColor($hex, $percent = 20)
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Elimina una asignacion de turno
     */
    public function eliminarAsignacion(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones_turnos,id',
        ]);

        $asignacion = AsignacionTurno::findOrFail($request->asignacion_id);
        $asignacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asignacion eliminada correctamente',
        ]);
    }

    /**
     * Actualiza las horas de fichaje de una asignacion
     */
    public function actualizarFichaje(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones_turnos,id',
            'entrada' => 'nullable|date_format:H:i',
            'salida' => 'nullable|date_format:H:i',
            'entrada2' => 'nullable|date_format:H:i',
            'salida2' => 'nullable|date_format:H:i',
        ]);

        $asignacion = AsignacionTurno::findOrFail($request->asignacion_id);

        $asignacion->update([
            'entrada' => $request->entrada,
            'salida' => $request->salida,
            'entrada2' => $request->entrada2,
            'salida2' => $request->salida2,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fichaje actualizado correctamente',
        ]);
    }

    /**
     * Crea una nueva asignacion de turno
     */
    public function crearAsignacion(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'turno_id' => 'required|exists:turnos,id',
            'fecha' => 'required|date',
        ]);

        // Verificar si ya existe una asignacion para ese usuario en esa fecha
        $existe = AsignacionTurno::where('user_id', $request->user_id)
            ->where('fecha', $request->fecha)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una asignacion para este usuario en esta fecha',
            ], 422);
        }

        $asignacion = AsignacionTurno::create([
            'user_id' => $request->user_id,
            'turno_id' => $request->turno_id,
            'fecha' => $request->fecha,
            'estado' => 'activo',
        ]);

        $user = User::with('categoria')->find($request->user_id);
        $turno = Turno::find($request->turno_id);

        // Usar color del turno si existe
        $color = $turno->color
            ? ['bg' => $turno->color, 'border' => $this->darkenColor($turno->color)]
            : ['bg' => '#93C5FD', 'border' => '#60A5FA'];

        return response()->json([
            'success' => true,
            'message' => 'Asignacion creada correctamente',
            'asignacion' => [
                'id' => $asignacion->id,
                'user_id' => $user->id,
                'user_nombre' => $user->nombre_completo ?? $user->name,
                'turno_id' => $turno->id,
                'turno_nombre' => $turno->nombre,
                'turno_hora_inicio' => substr($turno->hora_inicio ?? '00:00', 0, 5),
                'turno_hora_fin' => substr($turno->hora_fin ?? '23:59', 0, 5),
                'fecha' => $request->fecha,
                'categoria' => $user->categoria?->nombre,
                'foto' => $user->ruta_imagen ?? null,
                'color' => $color,
            ],
        ]);
    }

    /**
     * Mueve una asignacion (cambia fecha y/o turno)
     */
    public function moverAsignacion(Request $request)
    {
        $request->validate([
            'asignacion_id' => 'required|exists:asignaciones_turnos,id',
            'fecha' => 'required|date',
            'turno_id' => 'nullable|exists:turnos,id',
        ]);

        $asignacion = AsignacionTurno::findOrFail($request->asignacion_id);

        // Verificar si ya existe una asignacion para ese usuario en la nueva fecha
        $existe = AsignacionTurno::where('user_id', $asignacion->user_id)
            ->where('fecha', $request->fecha)
            ->where('id', '!=', $asignacion->id)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una asignacion para este usuario en esta fecha',
            ], 422);
        }

        $dataToUpdate = [
            'fecha' => $request->fecha,
        ];

        // Solo actualizar turno si se proporciona
        if ($request->turno_id) {
            $dataToUpdate['turno_id'] = $request->turno_id;
        }

        $asignacion->update($dataToUpdate);

        // Recargar con relaciones
        $asignacion->load(['user.categoria', 'turno']);
        $turno = $asignacion->turno;

        // Calcular color
        $color = $turno->color
            ? ['bg' => $turno->color, 'border' => $this->darkenColor($turno->color)]
            : ['bg' => '#93C5FD', 'border' => '#60A5FA'];

        return response()->json([
            'success' => true,
            'message' => 'Asignacion movida correctamente',
            'asignacion' => [
                'id' => $asignacion->id,
                'fecha' => $asignacion->fecha->format('Y-m-d'),
                'turno_id' => $turno->id,
                'turno_nombre' => $turno->nombre,
                'turno_hora_inicio' => substr($turno->hora_inicio ?? '00:00', 0, 5),
                'turno_hora_fin' => substr($turno->hora_fin ?? '23:59', 0, 5),
                'color' => $color,
            ],
        ]);
    }

    /**
     * Obtiene la lista de turnos para el formulario de creacion
     */
    public function datosFormulario()
    {
        $turnos = Turno::activos()->ordenados()->get(['id', 'nombre', 'color']);

        return response()->json([
            'turnos' => $turnos->map(fn($t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
                'color' => $t->color,
            ]),
        ]);
    }
}
