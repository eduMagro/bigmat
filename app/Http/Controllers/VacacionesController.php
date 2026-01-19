<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use App\Models\User;
use App\Models\VacacionesSolicitud;
use App\Models\Alerta;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class VacacionesController extends Controller
{

    public function index()
    {
        // Todas las solicitudes pendientes
        $solicitudesPendientes = VacacionesSolicitud::with('user')
            ->where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get();

        // Todas las vacaciones asignadas
        $vacaciones = AsignacionTurno::with(['user', 'turno'])
            ->where('estado', 'vacaciones')
            ->get();

        // Festivos
        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'id' => 'festivo-' . $festivo->fecha,
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800',
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => false
            ];
        })->toArray();

        // Eventos de vacaciones
        $eventos = $vacaciones->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->nombre_completo,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true,
                'extendedProps' => [
                    'user_id' => $asignacion->user->id,
                ],
            ];
        })->toArray();

        // Unir eventos con festivos
        $eventos = array_merge($eventos, $festivos);

        // Eventos de solicitudes pendientes (mismo estilo que mi-perfil)
        $eventosSolicitudes = $solicitudesPendientes->flatMap(function ($solicitud) {
            return collect(CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin)->toArray())
                ->map(function ($fecha) use ($solicitud) {
                    $fechaStr = $fecha->format('Y-m-d');
                    return [
                        'id' => 'sol-' . $solicitud->id . '-' . $fechaStr,
                        'title' => $solicitud->user->nombre_completo . ' (pendiente)',
                        'start' => $fechaStr,
                        'allDay' => true,
                        'backgroundColor' => '#fcdde8',
                        'borderColor' => '#f9a8d4',
                        'textColor' => '#000000',
                        'extendedProps' => [
                            'solicitud_id' => $solicitud->id,
                            'user_id' => $solicitud->user_id,
                            'estado' => 'pendiente',
                            'es_solicitud_vacaciones' => true,
                        ],
                    ];
                });
        })->values()->toArray();

        $eventos = array_merge($eventos, $eventosSolicitudes);

        return view('vacaciones.index', [
            'eventos' => $eventos,
            'solicitudesPendientes' => $solicitudesPendientes,
            'totalSolicitudesPendientes' => $solicitudesPendientes->count(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            // 1) Validación
            $validated = $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            $nuevaInicio = Carbon::parse($validated['fecha_inicio']);
            $nuevaFin = Carbon::parse($validated['fecha_fin']);

            // 2) Validar que haya al menos un día laborable (excluir fines de semana, festivos y días ya con vacaciones)
            $rango = CarbonPeriod::create($nuevaInicio, $nuevaFin);
            $festivos = Festivo::whereBetween('fecha', [
                    $nuevaInicio->copy()->subDays(10),
                    $nuevaFin->copy()->addDays(10)
                ])
                ->pluck('fecha')
                ->map(fn($f) => Carbon::parse($f)->format('Y-m-d'))
                ->toArray();

            // Obtener días que ya tienen estado "vacaciones" para este usuario
            $diasYaConVacaciones = AsignacionTurno::where('user_id', auth()->id())
                ->whereBetween('fecha', [$nuevaInicio, $nuevaFin])
                ->where('estado', 'vacaciones')
                ->pluck('fecha')
                ->map(fn($f) => Carbon::parse($f)->format('Y-m-d'))
                ->toArray();

            $diasSolicitables = [];
            foreach ($rango as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');

                // Saltar días que ya tienen vacaciones aprobadas
                if (in_array($fechaStr, $diasYaConVacaciones)) {
                    continue;
                }
                $diasSolicitables[] = $fechaStr;
            }

            if (empty($diasSolicitables)) {
                return response()->json([
                    'error' => 'El rango seleccionado no contiene días disponibles (ya tienes vacaciones aprobadas en esas fechas).',
                ], 400);
            }

            // 3) Validar que no se supere el límite de días de vacaciones
            $user = auth()->user();
            $inicioAño = Carbon::now()->startOfYear();

            // Días ya aprobados este año
            $diasYaAsignados = $user->asignacionesTurnos()
                ->where('estado', 'vacaciones')
                ->where('fecha', '>=', $inicioAño)
                ->count();

            // Días en solicitudes pendientes (excluyendo fines de semana y festivos)
            $solicitudesPendientes = VacacionesSolicitud::where('user_id', $user->id)
                ->where('estado', 'pendiente')
                ->get();

            $diasEnPendientes = 0;
            foreach ($solicitudesPendientes as $sol) {
                $rangoPendiente = CarbonPeriod::create($sol->fecha_inicio, $sol->fecha_fin);
                foreach ($rangoPendiente as $fechaPend) {
                    $diasEnPendientes++;
                }
            }

            $tope = $user->vacaciones_correspondientes ?? 30;
            $diasDisponibles = $tope - $diasYaAsignados - $diasEnPendientes;
            $diasSolicitados = count($diasSolicitables);

            if ($diasSolicitados > $diasDisponibles) {
                return response()->json([
                    'error' => "No puedes solicitar {$diasSolicitados} días. Solo te quedan {$diasDisponibles} días disponibles (de {$tope} totales, {$diasYaAsignados} aprobados y {$diasEnPendientes} pendientes).",
                ], 400);
            }

            // Ajustar el rango a los días realmente solicitables
            sort($diasSolicitables);
            $nuevaInicio = Carbon::parse($diasSolicitables[0]);
            $nuevaFin = Carbon::parse($diasSolicitables[count($diasSolicitables) - 1]);
            $validated['fecha_inicio'] = $nuevaInicio->format('Y-m-d');
            $validated['fecha_fin'] = $nuevaFin->format('Y-m-d');

            // 4) Buscar solicitudes pendientes adyacentes o solapadas para fusionar
            $solicitud = DB::transaction(function () use ($validated, $nuevaInicio, $nuevaFin) {
                $userId = auth()->id();

                // Obtener todas las solicitudes pendientes del usuario
                $todasSolicitudes = VacacionesSolicitud::where('user_id', $userId)
                    ->where('estado', 'pendiente')
                    ->get();

                $solicitudesAdyacentes = collect();

                foreach ($todasSolicitudes as $sol) {
                    $solInicio = Carbon::parse($sol->fecha_inicio);
                    $solFin = Carbon::parse($sol->fecha_fin);

                    // Verificar solapamiento
                    if ($solInicio <= $nuevaFin && $solFin >= $nuevaInicio) {
                        $solicitudesAdyacentes->push($sol);
                        continue;
                    }

                    // Verificar si son adyacentes (día siguiente)
                    if ($solFin->copy()->addDay()->format('Y-m-d') === $nuevaInicio->format('Y-m-d')) {
                        $solicitudesAdyacentes->push($sol);
                        continue;
                    }

                    if ($solInicio->copy()->subDay()->format('Y-m-d') === $nuevaFin->format('Y-m-d')) {
                        $solicitudesAdyacentes->push($sol);
                        continue;
                    }
                }

                if ($solicitudesAdyacentes->isEmpty()) {
                    return VacacionesSolicitud::create([
                        'user_id' => $userId,
                        'fecha_inicio' => $validated['fecha_inicio'],
                        'fecha_fin' => $validated['fecha_fin'],
                        'estado' => 'pendiente',
                    ]);
                }

                // Fusionar todas las solicitudes adyacentes en una sola
                $fechaMinima = $nuevaInicio;
                $fechaMaxima = $nuevaFin;

                foreach ($solicitudesAdyacentes as $sol) {
                    $solInicio = Carbon::parse($sol->fecha_inicio);
                    $solFin = Carbon::parse($sol->fecha_fin);

                    if ($solInicio < $fechaMinima) {
                        $fechaMinima = $solInicio;
                    }
                    if ($solFin > $fechaMaxima) {
                        $fechaMaxima = $solFin;
                    }
                }

                // Actualizar la primera solicitud con el rango fusionado
                $solicitudPrincipal = $solicitudesAdyacentes->first();
                $solicitudPrincipal->fecha_inicio = $fechaMinima->format('Y-m-d');
                $solicitudPrincipal->fecha_fin = $fechaMaxima->format('Y-m-d');
                $solicitudPrincipal->save();

                // Eliminar las demás solicitudes que se fusionaron
                if ($solicitudesAdyacentes->count() > 1) {
                    VacacionesSolicitud::whereIn('id', $solicitudesAdyacentes->skip(1)->pluck('id'))
                        ->delete();
                }

                return $solicitudPrincipal;
            });

            // 5) Intentar crear la alerta a RRHH
            $alertaEnviada = false;
            try {
                $rrhh = User::where('email', 'josemanuel.amuedo@pacoreyes.com')->first();

                if ($rrhh) {
                    Alerta::create([
                        'user_id_1' => auth()->id(),
                        'destinatario_id' => $rrhh->id,
                        'mensaje' => auth()->user()->name . ' ha solicitado vacaciones del ' .
                            $solicitud->fecha_inicio . ' al ' . $solicitud->fecha_fin,
                        'tipo' => 'vacaciones',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $alertaEnviada = true;
                }
            } catch (Throwable $e) {
                Log::warning('Fallo creando la alerta de RRHH para vacaciones.', [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => 'Solicitud registrada correctamente.',
                'solicitud_id' => $solicitud->id,
                'alerta_enviada' => $alertaEnviada,
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error al registrar la solicitud de vacaciones.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'payload' => $request->only(['fecha_inicio', 'fecha_fin']),
            ]);

            return response()->json([
                'error' => 'No se pudo registrar la solicitud. Inténtalo de nuevo más tarde.',
            ], 500);
        }
    }

    public function aprobar(Request $request, $id)
    {
        $isAjax = request()->ajax() || request()->wantsJson();

        try {
            $solicitud = VacacionesSolicitud::with('user')->findOrFail($id);
            $user = $solicitud->user;

            // Obtener año de cargo (del request o año de la fecha de inicio)
            $fechaInicio = Carbon::parse($solicitud->fecha_inicio);
            $anioCargo = $request->input('anio_cargo', $fechaInicio->year);

            $rango = CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin);
            $diasNuevos = 0;
            $fechasAsignables = [];

            // Contar días ya asignados para el año de cargo seleccionado
            $diasYaAsignados = $user->asignacionesTurnos()
                ->where('estado', 'vacaciones')
                ->where('anio_cargo', $anioCargo)
                ->count();

            foreach ($rango as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');

                $asignacionExistente = AsignacionTurno::where('user_id', $user->id)
                    ->where('fecha', $fechaStr)
                    ->where('estado', 'vacaciones')
                    ->exists();

                if (!$asignacionExistente) {
                    $fechasAsignables[] = $fechaStr;
                    $diasNuevos++;
                }
            }

            $tope = $user->vacaciones_correspondientes ?? 30;

            if (($diasYaAsignados + $diasNuevos) > $tope) {
                $errorMsg = "No se puede aprobar. El usuario ya tiene {$diasYaAsignados} días asignados en {$anioCargo} y esta solicitud añade {$diasNuevos}, superando el tope de {$tope} días.";
                if ($isAjax) {
                    return response()->json(['success' => false, 'error' => $errorMsg], 400);
                }
                return redirect()->back()->with('error', $errorMsg);
            }

            // Asignación real (todos los días del rango)
            foreach ($fechasAsignables as $fechaStr) {
                $asignacion = AsignacionTurno::firstOrNew([
                    'user_id' => $user->id,
                    'fecha' => $fechaStr,
                ]);

                $estadoAnterior = $asignacion->estado;

                $asignacion->estado = 'vacaciones';
                $asignacion->anio_cargo = $anioCargo;
                $asignacion->maquina_id = $user->maquina_id;
                $asignacion->save();

                Log::info("Asignación vacaciones para $fechaStr - año cargo: $anioCargo - estado anterior: " . ($estadoAnterior ?? 'ninguno'));
            }

            // Marcar solicitud como aprobada
            $solicitud->estado = 'aprobada';
            $solicitud->save();

            // Alerta
            Alerta::create([
                'user_id_1' => auth()->id(),
                'destinatario_id' => $user->id,
                'mensaje' => "Tus vacaciones del {$solicitud->fecha_inicio} al {$solicitud->fecha_fin} han sido aprobadas.",
                'tipo' => 'vacaciones',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $successMsg = "Solicitud aprobada. Se asignaron {$diasNuevos} días de vacaciones.";

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'solicitud_id' => $solicitud->id,
                    'dias_asignados' => $diasNuevos,
                ]);
            }

            return redirect()->back()->with('success', $successMsg);

        } catch (Throwable $e) {
            Log::error('Error al aprobar solicitud de vacaciones.', [
                'error' => $e->getMessage(),
                'solicitud_id' => $id,
            ]);

            if ($isAjax) {
                return response()->json(['success' => false, 'error' => 'Error al aprobar la solicitud: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error al aprobar la solicitud.');
        }
    }

    public function denegar($id)
    {
        $isAjax = request()->ajax() || request()->wantsJson();

        try {
            $solicitud = VacacionesSolicitud::with('user')->findOrFail($id);
            $user = $solicitud->user;

            $solicitud->estado = 'denegada';
            $solicitud->save();

            // Alerta al trabajador
            Alerta::create([
                'user_id_1' => auth()->id(),
                'destinatario_id' => $user->id,
                'mensaje' => "Tu solicitud de vacaciones del {$solicitud->fecha_inicio} al {$solicitud->fecha_fin} ha sido denegada.",
                'tipo' => 'vacaciones',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud denegada y alerta enviada.',
                    'solicitud_id' => $solicitud->id,
                ]);
            }

            return redirect()->back()->with('success', 'Solicitud denegada y alerta enviada.');

        } catch (Throwable $e) {
            Log::error('Error al denegar solicitud de vacaciones.', [
                'error' => $e->getMessage(),
                'solicitud_id' => $id,
            ]);

            if ($isAjax) {
                return response()->json(['success' => false, 'error' => 'Error al denegar la solicitud: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error al denegar la solicitud.');
        }
    }

    /**
     * Eliminar una solicitud de vacaciones pendiente (solo el propietario)
     */
    public function eliminarSolicitud($id)
    {
        try {
            $solicitud = VacacionesSolicitud::findOrFail($id);

            // Solo el propietario puede eliminar su solicitud
            if ($solicitud->user_id !== auth()->id()) {
                return response()->json(['error' => 'No tienes permiso para eliminar esta solicitud.'], 403);
            }

            // Solo se pueden eliminar solicitudes pendientes
            if ($solicitud->estado !== 'pendiente') {
                return response()->json(['error' => 'Solo se pueden eliminar solicitudes pendientes.'], 400);
            }

            $solicitud->delete();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud eliminada correctamente.',
            ]);
        } catch (Throwable $e) {
            Log::error('Error al eliminar solicitud de vacaciones.', [
                'error' => $e->getMessage(),
                'solicitud_id' => $id,
                'user_id' => auth()->id(),
            ]);

            return response()->json(['error' => 'No se pudo eliminar la solicitud.'], 500);
        }
    }

    /**
     * Eliminar días específicos de una solicitud pendiente (modificar rango)
     */
    public function eliminarDiasSolicitud(Request $request)
    {
        try {
            $validated = $request->validate([
                'solicitud_id' => 'required|exists:solicitudes_vacaciones,id',
                'fechas_eliminar' => 'required|array|min:1',
                'fechas_eliminar.*' => 'date',
            ]);

            $solicitud = VacacionesSolicitud::findOrFail($validated['solicitud_id']);

            // Solo el propietario puede modificar su solicitud
            if ($solicitud->user_id !== auth()->id()) {
                return response()->json(['error' => 'No tienes permiso para modificar esta solicitud.'], 403);
            }

            // Solo se pueden modificar solicitudes pendientes
            if ($solicitud->estado !== 'pendiente') {
                return response()->json(['error' => 'Solo se pueden modificar solicitudes pendientes.'], 400);
            }

            $fechaInicio = Carbon::parse($solicitud->fecha_inicio);
            $fechaFin = Carbon::parse($solicitud->fecha_fin);
            $fechasEliminar = collect($validated['fechas_eliminar'])->map(fn($f) => Carbon::parse($f)->format('Y-m-d'));

            // Obtener todos los días del rango actual
            $rango = CarbonPeriod::create($fechaInicio, $fechaFin);
            $diasOriginales = collect();
            foreach ($rango as $fecha) {
                $diasOriginales->push($fecha->format('Y-m-d'));
            }

            // Filtrar los días que NO se eliminan
            $diasRestantes = $diasOriginales->reject(fn($d) => $fechasEliminar->contains($d))->values();

            // Si no quedan días, eliminar la solicitud completa
            if ($diasRestantes->isEmpty()) {
                $solicitud->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud eliminada completamente (no quedaban días).',
                    'solicitud_eliminada' => true,
                ]);
            }

            // Agrupar días consecutivos para crear rangos
            $rangos = [];
            $rangoActual = ['inicio' => null, 'fin' => null];

            foreach ($diasRestantes->sort()->values() as $dia) {
                if ($rangoActual['inicio'] === null) {
                    $rangoActual['inicio'] = $dia;
                    $rangoActual['fin'] = $dia;
                } else {
                    $diaAnterior = Carbon::parse($rangoActual['fin']);
                    $diaActual = Carbon::parse($dia);

                    if ($diaActual->diffInDays($diaAnterior) === 1) {
                        $rangoActual['fin'] = $dia;
                    } else {
                        $rangos[] = $rangoActual;
                        $rangoActual = ['inicio' => $dia, 'fin' => $dia];
                    }
                }
            }
            $rangos[] = $rangoActual;

            DB::transaction(function () use ($solicitud, $rangos) {
                // Actualizar la solicitud original con el primer rango
                $solicitud->fecha_inicio = $rangos[0]['inicio'];
                $solicitud->fecha_fin = $rangos[0]['fin'];
                $solicitud->save();

                // Crear nuevas solicitudes para los rangos adicionales
                for ($i = 1; $i < count($rangos); $i++) {
                    VacacionesSolicitud::create([
                        'user_id' => $solicitud->user_id,
                        'fecha_inicio' => $rangos[$i]['inicio'],
                        'fecha_fin' => $rangos[$i]['fin'],
                        'estado' => 'pendiente',
                        'observaciones' => $solicitud->observaciones,
                    ]);
                }
            });

            $mensaje = count($rangos) > 1
                ? 'Solicitud modificada. Se han creado ' . count($rangos) . ' solicitudes separadas.'
                : 'Solicitud modificada correctamente.';

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'rangos' => $rangos,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error al modificar solicitud de vacaciones.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'No se pudo modificar la solicitud.'], 500);
        }
    }

    /**
     * Obtener solicitudes pendientes del usuario autenticado
     */
    public function misSolicitudesPendientes()
    {
        $solicitudes = VacacionesSolicitud::where('user_id', auth()->id())
            ->where('estado', 'pendiente')
            ->orderBy('fecha_inicio')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'fecha_inicio' => $s->fecha_inicio,
                    'fecha_fin' => $s->fecha_fin,
                    'estado' => $s->estado,
                    'created_at' => $s->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json($solicitudes);
    }

    public function eliminarEvento(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::where('user_id', $validated['user_id'])
            ->where('fecha', $validated['fecha'])
            ->where('estado', 'vacaciones')
            ->first();

        if (!$asignacion) {
            return response()->json(['success' => false, 'error' => 'No se encontró la asignación de vacaciones.']);
        }

        // Cambiar el estado
        $asignacion->estado = 'activo';
        $asignacion->save();

        // Sumar un día al contador de vacaciones del usuario
        $usuario = User::find($validated['user_id']);
        if ($usuario) {
            $usuario->dias_vacaciones += 1;
            $usuario->save();
        }

        return response()->json(['success' => true]);
    }

    public function reprogramar(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_original' => 'required|date',
            'nueva_fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::where('user_id', $validated['user_id'])
            ->where('fecha', $validated['fecha_original'])
            ->where('estado', 'vacaciones')
            ->first();

        if (!$asignacion) {
            return response()->json(['error' => 'Asignación no encontrada'], 404);
        }

        $asignacion->fecha = $validated['nueva_fecha'];
        $asignacion->save();

        return response()->json(['success' => true]);
    }

    /**
     * Obtener eventos de vacaciones (para refetch dinámico)
     */
    public function eventos()
    {
        // Obtener festivos
        $festivos = Festivo::select('fecha', 'titulo')->get()->map(function ($festivo) {
            return [
                'id' => 'festivo-' . $festivo->fecha,
                'title' => $festivo->titulo,
                'start' => $festivo->fecha,
                'backgroundColor' => '#ff2800',
                'borderColor' => '#b22222',
                'textColor' => 'white',
                'allDay' => true,
                'editable' => false
            ];
        })->toArray();

        // Todas las vacaciones aprobadas
        $vacaciones = AsignacionTurno::with(['user', 'turno'])
            ->where('estado', 'vacaciones')
            ->get();

        $eventos = $vacaciones->map(function ($asignacion) {
            return [
                'title' => $asignacion->user->nombre_completo,
                'start' => Carbon::parse($asignacion->fecha)->toIso8601String(),
                'backgroundColor' => '#f87171',
                'borderColor' => '#dc2626',
                'textColor' => 'white',
                'allDay' => true,
                'extendedProps' => [
                    'user_id' => $asignacion->user->id,
                ],
            ];
        })->toArray();

        // Solicitudes pendientes
        $solicitudesPendientes = VacacionesSolicitud::with('user')
            ->where('estado', 'pendiente')
            ->get();

        $eventosSolicitudes = $solicitudesPendientes->flatMap(function ($solicitud) {
            return collect(CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin)->toArray())
                ->map(function ($fecha) use ($solicitud) {
                    $fechaStr = $fecha->format('Y-m-d');
                    return [
                        'id' => 'sol-' . $solicitud->id . '-' . $fechaStr,
                        'title' => $solicitud->user->nombre_completo . ' (pendiente)',
                        'start' => $fechaStr,
                        'allDay' => true,
                        'backgroundColor' => '#fcdde8',
                        'borderColor' => '#f9a8d4',
                        'textColor' => '#000000',
                        'extendedProps' => [
                            'solicitud_id' => $solicitud->id,
                            'user_id' => $solicitud->user_id,
                            'estado' => 'pendiente',
                            'es_solicitud_vacaciones' => true,
                        ],
                    ];
                });
        })->values()->toArray();

        return response()->json(array_merge($eventos, $festivos, $eventosSolicitudes));
    }

    /**
     * Obtener usuarios con su contador de vacaciones del año actual
     */
    public function usuariosConVacaciones()
    {
        $inicioAño = Carbon::now()->startOfYear();

        $usuarios = User::where('estado', 'activo')
            ->select('id', 'name', 'primer_apellido', 'segundo_apellido', 'rol', 'maquina_id', 'vacaciones_totales')
            ->orderBy('name')
            ->get();

        // Contar vacaciones asignadas para cada usuario
        $usuarioIds = $usuarios->pluck('id');
        $vacacionesPorUsuario = AsignacionTurno::whereIn('user_id', $usuarioIds)
            ->where('estado', 'vacaciones')
            ->where('fecha', '>=', $inicioAño)
            ->selectRaw('user_id, COUNT(*) as total_vacaciones')
            ->groupBy('user_id')
            ->pluck('total_vacaciones', 'user_id');

        $resultado = $usuarios->map(function ($user) use ($vacacionesPorUsuario) {
            $tope = $user->vacaciones_correspondientes;
            $usadas = $vacacionesPorUsuario[$user->id] ?? 0;
            return [
                'id' => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'vacaciones_usadas' => $usadas,
                'vacaciones_totales' => $tope,
                'vacaciones_restantes' => max(0, $tope - $usadas),
            ];
        });

        return response()->json($resultado);
    }

    /**
     * Asignar vacaciones directamente a un usuario (sin solicitud previa)
     */
    public function asignarDirecto(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            $user = User::findOrFail($validated['user_id']);
            $rango = CarbonPeriod::create($validated['fecha_inicio'], $validated['fecha_fin']);

            $inicioAño = Carbon::now()->startOfYear();
            $diasYaAsignados = $user->asignacionesTurnos()
                ->where('estado', 'vacaciones')
                ->where('fecha', '>=', $inicioAño)
                ->count();

            $diasNuevos = 0;
            $fechasAsignables = [];

            foreach ($rango as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');

                // Verificar si ya tiene vacaciones ese día
                $asignacionExistente = AsignacionTurno::where('user_id', $user->id)
                    ->where('fecha', $fechaStr)
                    ->where('estado', 'vacaciones')
                    ->exists();

                if (!$asignacionExistente) {
                    $fechasAsignables[] = $fechaStr;
                    $diasNuevos++;
                }
            }

            if ($diasNuevos === 0) {
                return response()->json([
                    'error' => 'No hay días nuevos para asignar (ya tiene vacaciones en esas fechas).'
                ], 400);
            }

            $tope = $user->vacaciones_correspondientes ?? 30;
            if (($diasYaAsignados + $diasNuevos) > $tope) {
                return response()->json([
                    'error' => "El usuario ya tiene {$diasYaAsignados} días asignados. Añadir {$diasNuevos} días supera el tope de {$tope}."
                ], 400);
            }

            // Asignar vacaciones
            DB::transaction(function () use ($fechasAsignables, $user) {
                foreach ($fechasAsignables as $fechaStr) {
                    $asignacion = AsignacionTurno::firstOrNew([
                        'user_id' => $user->id,
                        'fecha' => $fechaStr,
                    ]);

                    $asignacion->estado = 'vacaciones';
                    $asignacion->maquina_id = $user->maquina_id;
                    $asignacion->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Se asignaron {$diasNuevos} días de vacaciones a {$user->nombre_completo}.",
                'dias_asignados' => $diasNuevos,
                'total_vacaciones' => $diasYaAsignados + $diasNuevos,
            ]);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error al asignar vacaciones directamente.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'No se pudieron asignar las vacaciones. Inténtalo de nuevo.'
            ], 500);
        }
    }
}
