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

        return view('vacaciones.index', [
            'eventos' => $eventos,
            'solicitudesPendientes' => $solicitudesPendientes,
            'totalSolicitudesPendientes' => $solicitudesPendientes->count(),
        ]);
    }
    public function store(Request $request)
    {
        try {
            // 1) Validación (si falla lanza ValidationException con 422 automáticamente)
            $validated = $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            // 2) Crear la solicitud dentro de una transacción
            $solicitud = DB::transaction(function () use ($validated) {
                return \App\Models\VacacionesSolicitud::create([
                    'user_id' => auth()->id(),
                    'fecha_inicio' => $validated['fecha_inicio'],
                    'fecha_fin' => $validated['fecha_fin'],
                    'estado' => 'pendiente',
                ]);
            });

            // 3) Intentar crear la alerta a RRHH fuera de la transacción
            $alertaEnviada = false;
            try {
                $rrhh = \App\Models\User::where('email', 'josemanuel.amuedo@pacoreyes.com')->first();

                if ($rrhh) {
                    \App\Models\Alerta::create([
                        'user_id_1' => auth()->id(),
                        'destinatario_id' => $rrhh->id,
                        'mensaje' => auth()->user()->name . ' ha solicitado vacaciones del ' .
                            $validated['fecha_inicio'] . ' al ' . $validated['fecha_fin'],
                        'tipo' => 'vacaciones',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $alertaEnviada = true;
                } else {
                    Log::warning('RRHH no encontrado para alerta de vacaciones.', [
                        'email_rrhh' => 'josemanuel.amuedo@pacoreyes.com',
                        'user_id' => auth()->id(),
                        'solicitud_id' => $solicitud->id ?? null,
                    ]);
                }
            } catch (Throwable $e) {
                // No rompemos la solicitud si falla la alerta
                Log::warning('Fallo creando la alerta de RRHH para vacaciones.', [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                    'solicitud_id' => $solicitud->id ?? null,
                ]);
            }

            // 4) Respuesta OK
            return response()->json([
                'success' => 'Solicitud registrada correctamente.',
                'solicitud_id' => $solicitud->id,
                'alerta_enviada' => $alertaEnviada,
            ], 201);
        } catch (ValidationException $e) {
            // Dejamos que Laravel responda 422 con los errores de validación
            throw $e;
        } catch (Throwable $e) {
            // Cualquier otro error inesperado
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
    public function aprobar($id)
    {
        $solicitud = VacacionesSolicitud::with('user')->findOrFail($id);
        $user = $solicitud->user;

        $rango = CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin);
        $diasNuevos = 0;
        $fechasAsignables = [];

        $inicioAño = Carbon::now()->startOfYear();
        $diasYaAsignados = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->where('fecha', '>=', $inicioAño)
            ->count();

        foreach ($rango as $fecha) {
            $fechaStr = $fecha->format('Y-m-d');

            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            $asignacionExistente = AsignacionTurno::where('user_id', $user->id)
                ->where('fecha', $fechaStr)
                ->where('estado', 'vacaciones')
                ->exists();

            if (!$asignacionExistente) {
                $fechasAsignables[] = $fechaStr;
                $diasNuevos++;
            }
        }

        $tope = $user->vacaciones_correspondientes;

        if (($diasYaAsignados + $diasNuevos) > $tope) {
            return redirect()->back()->with('error', "No se puede aprobar la solicitud. El usuario ya tiene {$diasYaAsignados} días asignados y esta solicitud añade {$diasNuevos}, superando el tope de {$tope} días.");
        }

        // ✔️ Asignación real
        foreach ($rango as $fecha) {
            $fechaStr = $fecha->format('Y-m-d');

            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            $asignacion = AsignacionTurno::firstOrNew([
                'user_id' => $user->id,
                'fecha' => $fechaStr,
            ]);

            $estadoAnterior = $asignacion->estado;

            $asignacion->estado = 'vacaciones';
            $asignacion->maquina_id = $user->maquina_id;
            $asignacion->save();

            Log::info("✏️ Asignación actualizada para $fechaStr - estado anterior: " . ($estadoAnterior ?? 'ninguno'));
        }

        // ✔️ Marcar solicitud como aprobada
        $solicitud->estado = 'aprobada';
        $solicitud->save();

        // ✔️ Alerta
        Alerta::create([
            'user_id_1' => auth()->id(),
            'destinatario_id' => $user->id,
            'mensaje' => "Tus vacaciones del {$solicitud->fecha_inicio} al {$solicitud->fecha_fin} han sido aprobadas.",
            'tipo' => 'vacaciones',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', "Solicitud aprobada. Se asignaron {$diasNuevos} días de vacaciones.");
    }

    public function denegar($id)
    {
        $solicitud = VacacionesSolicitud::with('user')->findOrFail($id);
        $user = $solicitud->user;

        $solicitud->estado = 'denegada';
        $solicitud->save();

        // 🛑 Alerta al trabajador
        Alerta::create([
            'user_id_1' => auth()->id(), // quien deniega
            'destinatario_id' => $user->id,    // quien recibe
            'mensaje' => "Tu solicitud de vacaciones del {$solicitud->fecha_inicio} al {$solicitud->fecha_fin} ha sido denegada.",
            'tipo' => 'vacaciones',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Solicitud denegada y alerta enviada.');
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

        // Todas las vacaciones
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

        return response()->json(array_merge($eventos, $festivos));
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

                // Saltar fines de semana
                if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                    continue;
                }

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
                    'error' => 'No hay días nuevos para asignar (ya tiene vacaciones en esas fechas o son fines de semana).'
                ], 400);
            }

            $tope = $user->vacaciones_correspondientes;
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
