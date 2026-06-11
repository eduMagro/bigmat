<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Turno;
use App\Models\Obra;
use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\AsignacionTurno;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Builder; // ✅ Correcto
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsignacionesTurnosExport;
use App\Models\Festivo;
use Carbon\CarbonPeriod;
use App\Servicios\Turnos\TurnoMapper;
use App\Servicios\Turnos\ValidadorAsignaciones;

class AsignacionTurnoController extends Controller
{
    private function escapeLike(string $value): string
    {
        // Escapa \ % _ para LIKE
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
        return "%{$value}%";
    }

    public function aplicarFiltros($query, Request $request)
    {
        // ID exacto
        if ($request->filled('id')) {
            $query->where('user_id', $request->input('id'));
        }

        // Empleado: name + apellidos (contains)
        if ($request->filled('empleado')) {
            $like = $this->escapeLike($request->empleado);
            $query->whereHas('user', function ($q) use ($like) {
                $q->whereRaw(
                    "CONCAT_WS(' ', COALESCE(name,''), COALESCE(primer_apellido,''), COALESCE(segundo_apellido,'')) LIKE ? ESCAPE '\\\\'",
                    [$like]
                );
            });
        }

        // Rango de fechas inclusivo
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $ini = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fin = Carbon::parse($request->fecha_fin)->endOfDay();
            $query->whereBetween('fecha', [$ini, $fin]);
        } elseif ($request->filled('fecha_inicio')) {
            $ini = Carbon::parse($request->fecha_inicio)->startOfDay();
            $query->where('fecha', '>=', $ini);
        } elseif ($request->filled('fecha_fin')) {
            $fin = Carbon::parse($request->fecha_fin)->endOfDay();
            $query->where('fecha', '<=', $fin);
        }

        // Obra (contains por nombre/columna 'obra')
        if ($request->filled('obra')) {
            $like = $this->escapeLike($request->obra);
            $query->whereHas('obra', function ($q) use ($like) {
                $q->whereRaw("obra LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Turno (contains por 'nombre')
        if ($request->filled('turno')) {
            $like = $this->escapeLike($request->turno);
            $query->whereHas('turno', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Máquina (contains por 'nombre')
        if ($request->filled('maquina')) {
            $like = $this->escapeLike($request->maquina);
            $query->whereHas('maquina', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Entrada / Salida (TIME → CAST a CHAR antes del LIKE)
        if ($request->filled('entrada')) {
            $like = $this->escapeLike($request->entrada);
            $query->whereRaw("CAST(entrada AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }
        if ($request->filled('salida')) {
            $like = $this->escapeLike($request->salida);
            $query->whereRaw("CAST(salida AS CHAR) LIKE ? ESCAPE '\\\\'", [$like]);
        }

        return $query;
    }

    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id')) {
            $filtros[] = 'ID Empleado: <strong>' . e($request->id) . '</strong>';
        }
        if ($request->filled('empleado')) {
            $filtros[] = 'Empleado: <strong>' . e($request->empleado) . '</strong>';
        }
        if ($request->filled('fecha_inicio') || $request->filled('fecha_fin')) {
            $rango = ($request->fecha_inicio ?? '—') . ' a ' . ($request->fecha_fin ?? '—');
            $filtros[] = 'Fecha: <strong>' . e($rango) . '</strong>';
        }
        if ($request->filled('obra')) {
            $filtros[] = 'Obra: <strong>' . e($request->obra) . '</strong>';
        }
        if ($request->filled('turno')) {
            $filtros[] = 'Turno: <strong>' . e($request->turno) . '</strong>';
        }
        if ($request->filled('maquina')) {
            $filtros[] = 'Máquina: <strong>' . e($request->maquina) . '</strong>';
        }
        if ($request->filled('entrada')) {
            $filtros[] = 'Entrada: <strong>' . e($request->entrada) . '</strong>';
        }
        if ($request->filled('salida')) {
            $filtros[] = 'Salida: <strong>' . e($request->salida) . '</strong>';
        }

        // 🧭 Unifica el nombre del parámetro de orden
        $sort   = $request->input('sort');
        $order  = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sort) {
            $filtros[] = 'Ordenado por <strong>' . e($sort) . '</strong> en <strong>' . ($order === 'asc' ? 'ascendente' : 'descendente') . '</strong>';
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . e($request->per_page) . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort  = request('sort');
        $currentOrder = request('order'); // ← usamos 'order' para ser coherentes
        $isSorted     = $currentSort === $columna;
        $nextOrder    = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? '▲' : '▼')
            : '⇅';

        $url = request()->fullUrlWithQuery(['sort' => $columna, 'order' => $nextOrder]);

        return '<a href="' . e($url) . '" class="inline-flex items-center space-x-1">' .
            '<span>' . e($titulo) . '</span><span class="text-xs">' . $icon . '</span></a>';
    }

    private function aplicarOrdenamiento($query, Request $request)
    {
        $sort  = $request->input('sort', 'fecha');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Mapea a columnas totalmente calificadas para evitar ambigüedad tras joins
        $map = [
            'user_id'    => 'asignaciones_turnos.user_id',
            'fecha'      => 'asignaciones_turnos.fecha',
            'obra_id'    => 'asignaciones_turnos.obra_id',
            'turno_id'   => 'asignaciones_turnos.turno_id',
            'maquina_id' => 'asignaciones_turnos.maquina_id',
            'entrada'    => 'asignaciones_turnos.entrada',
            'salida'     => 'asignaciones_turnos.salida',
        ];

        if (!array_key_exists($sort, $map)) {
            $sort = 'fecha';
        }

        // 1) Borra órdenes previos
        $query->reorder($map[$sort], $order);

        // 2) (Opcional) añade **orden secundario** estable
        //    - si ordenas por fecha, mantén el orden por turno (mañana/tarde/noche) como desempate
        if ($sort === 'fecha') {
            $query->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')");
        }

        // 3) (Opcional) último desempate siempre por ID para estabilidad
        $query->orderBy('asignaciones_turnos.id', 'asc');

        return $query;
    }



    public function index(Request $request)
    {
        // Filtrar por visibilidad del usuario actual
        $usuariosVisiblesIds = auth()->user()->getUsuariosVisiblesIds();

        // 1. QUERY BASE (filtros normales con empleado)
        $query = AsignacionTurno::with(['user', 'turno', 'obra'])
            ->whereDate('fecha', '<=', Carbon::tomorrow())
            ->where('estado', 'activo')
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->when($usuariosVisiblesIds !== null, fn($q) => $q->whereIn('user_id', $usuariosVisiblesIds))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->select('asignaciones_turnos.*');

        // aplicar filtros
        $query = $this->aplicarFiltros($query, $request);

        // aplicar ordenamiento separado
        $query = $this->aplicarOrdenamiento($query, $request);
        $ordenables = [
            'user_id'    => $this->getOrdenamiento('user_id', 'ID Empleado'),
            'fecha'      => $this->getOrdenamiento('fecha', 'Fecha'),
            'obra_id'    => $this->getOrdenamiento('obra_id', 'Lugar'),
            'turno_id'   => $this->getOrdenamiento('turno_id', 'Turno'),
            'maquina_id' => $this->getOrdenamiento('maquina_id', 'Máquina'),
            'entrada'    => $this->getOrdenamiento('entrada', 'Entrada'),
            'salida'     => $this->getOrdenamiento('salida', 'Salida'),
        ];

        $perPage = $request->input('per_page', 15);
        $asignaciones = $query->paginate($perPage)->withQueryString();
        // 🔹 Filtros activos para mostrarlos en la vista
        $filtrosActivos = $this->filtrosActivos($request);

        // 🔹 Turnos para los select
        $turnos = Turno::where('nombre', '!=', 'festivo')->orderBy('nombre')->get();

        // 2. Estadísticas del trabajador (cuando se filtra por nombre)
        $asignacionesFiltradas = (clone $query)->get();

        $diasAsignados = 0;
        $diasFichados = 0;
        $diasPuntuales = 0;
        $diasImpuntuales = 0;
        $diasSeVaAntes = 0;
        $diasSinFichaje = 0;

        foreach ($asignacionesFiltradas as $asignacion) {
            $esperadaEntrada = $asignacion->turno->hora_inicio ?? null;
            $esperadaSalida = $asignacion->turno->hora_fin ?? null;

            $realEntrada = $asignacion->entrada;
            $realSalida = $asignacion->salida;

            if ($esperadaEntrada) {
                $diasAsignados++;

                if ($realEntrada) {
                    $diasFichados++;

                    $llegaTemprano = Carbon::parse($realEntrada)->lte(Carbon::parse($esperadaEntrada));
                    $seVaTarde = $realSalida && $esperadaSalida
                        ? Carbon::parse($realSalida)->gte(Carbon::parse($esperadaSalida))
                        : false;
                    $seVaAntes = $realSalida && $esperadaSalida
                        ? Carbon::parse($realSalida)->lt(Carbon::parse($esperadaSalida))
                        : false;

                    if ($llegaTemprano && $seVaTarde) {
                        $diasPuntuales++;
                    } elseif (!$llegaTemprano && $seVaTarde) {
                        $diasImpuntuales++;
                    } elseif ($llegaTemprano && $seVaAntes) {
                        $diasSeVaAntes++;
                    }
                } else {
                    $diasSinFichaje++;
                }
            }
        }

        // 3. Ranking por minutos adelantados (solo mes actual)
        $requestSinEmpleado = $request->except('empleado');
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $queryRanking = AsignacionTurno::with(['user', 'turno'])
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->orderBy('fecha', 'desc')
            ->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')")
            ->select('asignaciones_turnos.*');

        $queryRanking = $this->aplicarFiltros($queryRanking, new \Illuminate\Http\Request($requestSinEmpleado));
        $asignacionesRanking = $queryRanking->get();

        $estadisticasPuntualidad = [];
        $asignacionesPorUsuario = $asignacionesRanking->groupBy('user_id');

        foreach ($asignacionesPorUsuario as $userId => $asignacionesUsuario) {
            $minutosAdelanto = 0;
            $minutosRetraso = 0;
            $diasAdelantado = 0;

            foreach ($asignacionesUsuario as $asignacion) {
                $esperadaEntrada = $asignacion->turno->hora_inicio ?? null;
                $realEntrada = $asignacion->entrada;

                if ($esperadaEntrada && $realEntrada) {
                    $fechaStr = Carbon::parse($asignacion->fecha)->format('Y-m-d');
                    $esperada = Carbon::parse($fechaStr . ' ' . $esperadaEntrada);
                    $real = Carbon::parse($fechaStr . ' ' . $realEntrada);

                    if ($real->lt($esperada)) {
                        $minutos = $real->diffInMinutes($esperada);
                        $minutosAdelanto += $minutos;
                        $diasAdelantado++;
                    } elseif ($real->gt($esperada)) {
                        $minutos = $esperada->diffInMinutes($real);
                        $minutosRetraso += $minutos;
                    }
                }
            }

            $minutosNetos = $minutosAdelanto - $minutosRetraso;

            if ($minutosNetos > 0) {
                $estadisticasPuntualidad[] = [
                    'usuario' => $asignacionesUsuario->first()->user,
                    'minutos_adelanto' => $minutosNetos,
                    'dias_adelantado' => $diasAdelantado
                ];
            }
        }


        $estadisticasPuntualidad = collect($estadisticasPuntualidad)
            ->sortByDesc('minutos_adelanto')
            ->take(3)
            ->values();
        $totalSolicitudesPendientes = VacacionesSolicitud::where('estado', 'pendiente')->count();
        return view('asignaciones-turnos.index', compact(
            'asignaciones',
            'diasAsignados',
            'diasFichados',
            'diasPuntuales',
            'diasImpuntuales',
            'diasSeVaAntes',
            'diasSinFichaje',
            'estadisticasPuntualidad',
            'turnos',
            'totalSolicitudesPendientes',
            'filtrosActivos',
            'ordenables',
        ));
    }

    public function fichar(Request $request)
    {
        try {
            /* 1) Validación y permisos ------------------------------------------------ */
            $request->validate([
                'user_id'  => 'required|exists:users,id',
                'tipo'     => 'required|in:entrada,salida',
                'latitud'  => 'required|numeric',
                'longitud' => 'required|numeric',
                'forzar'   => 'nullable|boolean', // Para confirmar acciones con advertencia
            ]);

            Log::info('Coordenadas recibidas', [
                'latitud'  => $request->latitud,
                'longitud' => $request->longitud,
            ]);

            $user = User::findOrFail($request->user_id);
            if (!in_array($user->rol, ['operario', 'oficina'])) {
                return response()->json(['error' => 'No tienes permisos para fichar.'], 403);
            }

            /* 2) Obra cercana --------------------------------------------------------- */
            if ($user->email === 'eduardo.magro@pacoreyes.com') {
                $obraEncontrada = Obra::first();
            } else {
                $obraEncontrada = $this->buscarObraCercana($request->latitud, $request->longitud);
            }

            if (!$obraEncontrada) {
                return response()->json(['error' => 'No estás dentro de ninguna zona de trabajo o no hay lugares configurados.'], 403);
            }

            /* 3) Hora actual ------------------------------------------------------------- */
            $ahora = now();
            $horaActual = $ahora->format('H:i:s');
            $fechaHoy = $ahora->toDateString();

            /* 4) Para SALIDA: no necesitamos detectar turno, buscamos asignación abierta */
            if ($request->tipo === 'salida') {
                return $this->procesarSalida(
                    $user, null, $horaActual, $obraEncontrada, $ahora, $request->forzar
                );
            }

            /* 5) Para ENTRADA: detectar turno/fecha ----------------------------------- */
            [$turnoDetectado, $fechaTurnoDetectado] = $this->detectarTurnoYFecha($ahora);
            if (!$turnoDetectado || !$fechaTurnoDetectado) {
                return response()->json(['error' => 'No se pudo determinar el turno para esta hora.'], 403);
            }

            $turnoModelo = Turno::where('nombre', $turnoDetectado)->first();
            if (!$turnoModelo) {
                return response()->json(['error' => "No existe configurado el turno '{$turnoDetectado}'."], 500);
            }

            /* 6) Buscar asignación existente para entrada ----------------------------- */
            $asignacion = $user->asignacionesTurnos()
                ->whereDate('fecha', $fechaTurnoDetectado)
                ->first();

            // También buscar en fecha real si es diferente
            if (!$asignacion && $fechaHoy !== $fechaTurnoDetectado) {
                $asignacion = $user->asignacionesTurnos()
                    ->whereDate('fecha', $fechaHoy)
                    ->first();
            }

            /* 7) Procesar ENTRADA ----------------------------------------------------- */
            return $this->procesarEntrada(
                $user, $asignacion, $turnoModelo, $turnoDetectado,
                $fechaTurnoDetectado, $horaActual, $obraEncontrada, $request->forzar
            );

        } catch (\Throwable $e) {
            Log::error('❌ Error en fichaje', ['exception' => $e]);
            return response()->json(['error' => 'Error al registrar el fichaje: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Procesa el fichaje de ENTRADA (turno partido)
     */
    private function procesarEntrada($user, $asignacion, $turnoModelo, $turnoDetectado, $fechaTurnoDetectado, $horaActual, $obraEncontrada, $forzar = false)
    {
        $warning = null;
        $campoActualizado = 'entrada';

        if (!$asignacion) {
            // === CASO 1: No existe asignación → Crear nueva con entrada ===
            $user->asignacionesTurnos()
                ->onlyTrashed()
                ->whereDate('fecha', $fechaTurnoDetectado)
                ->forceDelete();

            $asignacion = AsignacionTurno::create([
                'user_id'  => $user->id,
                'fecha'    => $fechaTurnoDetectado,
                'turno_id' => $turnoModelo->id,
                'estado'   => 'activo',
                'entrada'  => $horaActual,
                'obra_id'  => $obraEncontrada->id,
            ]);

            $this->notificarProgramadores($user, "🆕 Turno creado automáticamente ({$turnoDetectado}) para {$user->nombre_completo} en {$fechaTurnoDetectado}.");

        } elseif (!$asignacion->entrada) {
            // === CASO 2: Existe asignación sin entrada → Registrar entrada (sin cambiar turno) ===
            $asignacion->update([
                'estado'   => 'activo',
                'entrada'  => $horaActual,
                'obra_id'  => $obraEncontrada->id,
            ]);

        } elseif ($asignacion->entrada && $asignacion->salida && !$asignacion->entrada2) {
            // === CASO 3: Tiene entrada y salida, sin entrada2 → Registrar entrada2 (turno partido) ===
            $asignacion->update([
                'entrada2' => $horaActual,
                'obra_id'  => $obraEncontrada->id,
            ]);
            $campoActualizado = 'entrada2';

        } elseif ($asignacion->entrada && !$asignacion->salida) {
            // === CASO 4: Ya tiene entrada sin salida → Advertir ===
            return response()->json([
                'warning_confirm' => true,
                'titulo'          => 'Ya has fichado entrada',
                'mensaje'         => "Ya fichaste entrada a las {$asignacion->entrada}. ¿Quieres sobrescribir la hora de entrada?",
                'tipo_confirmacion' => 'sobrescribir_entrada',
            ]);

        } elseif ($asignacion->entrada2 && !$asignacion->salida2) {
            // === CASO 5: Ya tiene entrada2 sin salida2 → Advertir ===
            return response()->json([
                'warning_confirm' => true,
                'titulo'          => 'Ya has fichado segunda entrada',
                'mensaje'         => "Ya fichaste la segunda entrada a las {$asignacion->entrada2}. ¿Quieres sobrescribir?",
                'tipo_confirmacion' => 'sobrescribir_entrada2',
            ]);

        } elseif ($asignacion->entrada2 && $asignacion->salida2) {
            // === CASO 6: Turno partido completo → No permitir más entradas ===
            return response()->json([
                'error' => 'Ya has completado el turno partido de hoy (entrada, salida, entrada2, salida2). No puedes registrar más fichajes.',
            ], 403);
        }

        // Validación de horario
        if (!$this->validarHoraEntrada($turnoDetectado, $horaActual)) {
            $warning = 'Has fichado entrada fuera de tu horario.';
        }

        $mensaje = $campoActualizado === 'entrada2'
            ? 'Segunda entrada registrada (turno partido).'
            : 'Entrada registrada.';

        return response()->json([
            'success'     => $mensaje,
            'warning'     => $warning,
            'obra_nombre' => $obraEncontrada->obra,
            'campo'       => $campoActualizado,
            'estado'      => $this->getEstadoFichaje($asignacion->fresh()),
        ]);
    }

    /**
     * Procesa el fichaje de SALIDA (turno partido)
     */
    private function procesarSalida($user, $asignacion, $horaActual, $obraEncontrada, $ahora, $forzar = false)
    {
        $campoActualizado = 'salida';

        // Buscar asignación reciente si no se encontró (incluye turnos partidos)
        if (!$asignacion) {
            $asignacion = $this->buscarAsignacionRecienteParaSalida($user, $ahora);
        }

        if (!$asignacion) {
            // === CASO 1: No existe asignación → No se puede fichar salida ===
            return response()->json([
                'warning_confirm' => true,
                'titulo'          => 'No has fichado entrada',
                'mensaje'         => 'No tienes registro de entrada para hoy. ¿Quieres registrar la salida de todas formas? Se creará un registro solo con la hora de salida.',
                'tipo_confirmacion' => 'salida_sin_entrada',
            ]);
        }

        if (!$asignacion->entrada) {
            // === CASO 2: Asignación sin entrada → Advertir ===
            if (!$forzar) {
                return response()->json([
                    'warning_confirm' => true,
                    'titulo'          => 'No has fichado entrada',
                    'mensaje'         => 'No has fichado la entrada de hoy. ¿Quieres registrar la salida de todas formas?',
                    'tipo_confirmacion' => 'salida_sin_entrada',
                ]);
            }
            // Si forzar, registrar salida sin entrada
            $asignacion->update([
                'salida'  => $horaActual,
                'obra_id' => $obraEncontrada->id,
            ]);

        } elseif ($asignacion->entrada && !$asignacion->salida) {
            // === CASO 3: Tiene entrada sin salida → Registrar salida ===
            $asignacion->update([
                'salida'  => $horaActual,
                'obra_id' => $obraEncontrada->id,
            ]);

        } elseif ($asignacion->entrada && $asignacion->salida && !$asignacion->entrada2) {
            // === CASO 4: Tiene entrada y salida, sin entrada2 → Advertir (debe fichar entrada2 primero) ===
            return response()->json([
                'warning_confirm' => true,
                'titulo'          => 'No has fichado segunda entrada',
                'mensaje'         => "Ya fichaste salida a las {$asignacion->salida}. Para registrar otra salida, primero debes fichar la segunda entrada (turno partido).",
                'tipo_confirmacion' => 'necesita_entrada2',
                'accion_alternativa' => 'fichar_entrada',
            ]);

        } elseif ($asignacion->entrada2 && !$asignacion->salida2) {
            // === CASO 5: Tiene entrada2 sin salida2 → Registrar salida2 ===
            $asignacion->update([
                'salida2' => $horaActual,
                'obra_id' => $obraEncontrada->id,
            ]);
            $campoActualizado = 'salida2';

        } elseif ($asignacion->salida2) {
            // === CASO 6: Ya tiene salida2 → Turno partido completo ===
            return response()->json([
                'error' => 'Ya has completado el turno partido de hoy. No puedes registrar más salidas.',
            ], 403);
        }

        $mensaje = $campoActualizado === 'salida2'
            ? 'Segunda salida registrada (turno partido completo).'
            : 'Salida registrada.';

        return response()->json([
            'success'     => $mensaje,
            'obra_nombre' => $obraEncontrada->obra,
            'campo'       => $campoActualizado,
            'estado'      => $this->getEstadoFichaje($asignacion->fresh()),
        ]);
    }

    /**
     * Obtiene el estado actual del fichaje para mostrar en la UI
     */
    private function getEstadoFichaje($asignacion)
    {
        if (!$asignacion) {
            return ['fase' => 'sin_fichaje', 'descripcion' => 'Sin fichajes'];
        }

        $estado = [
            'entrada'  => $asignacion->entrada,
            'salida'   => $asignacion->salida,
            'entrada2' => $asignacion->entrada2,
            'salida2'  => $asignacion->salida2,
        ];

        if ($asignacion->salida2) {
            $estado['fase'] = 'turno_completo';
            $estado['descripcion'] = 'Turno partido completo';
        } elseif ($asignacion->entrada2) {
            $estado['fase'] = 'segunda_jornada';
            $estado['descripcion'] = 'En segunda jornada';
            $estado['proximo_fichaje'] = 'salida2';
        } elseif ($asignacion->salida) {
            $estado['fase'] = 'descanso';
            $estado['descripcion'] = 'En descanso (entre jornadas)';
            $estado['proximo_fichaje'] = 'entrada2';
        } elseif ($asignacion->entrada) {
            $estado['fase'] = 'trabajando';
            $estado['descripcion'] = 'Trabajando';
            $estado['proximo_fichaje'] = 'salida';
        } else {
            $estado['fase'] = 'pendiente';
            $estado['descripcion'] = 'Pendiente de entrada';
            $estado['proximo_fichaje'] = 'entrada';
        }

        return $estado;
    }

    /**
     * Notifica a los programadores y administradores sobre eventos de turno
     */
    private function notificarProgramadores($user, $mensaje)
    {
        try {
            $destinatarios = User::whereHas('departamentos', fn($q) =>
                $q->whereRaw('LOWER(nombre) IN (?, ?)', ['programador', 'administrador'])
            )->get();
            $alerta = Alerta::create([
                'user_id_1' => $user->id,
                'mensaje'   => $mensaje,
                'tipo'      => 'Info Turnos',
                'leida'     => false,
            ]);
            foreach ($destinatarios as $p) {
                AlertaLeida::firstOrCreate(['alerta_id' => $alerta->id, 'user_id' => $p->id]);
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo notificar: ' . $e->getMessage());
        }
    }

    /* ===================== HELPERS ===================== */

    /**
     * Detecta el turno y la fecha a la que debe imputarse usando los datos de la tabla turnos.
     *
     * Incluye margen de anticipación: si fichas hasta 1 hora antes del inicio del turno,
     * se asigna al turno que va a empezar (no al que está terminando).
     *
     * Ejemplo: Si fichas a las 13:00 y el turno tarde empieza a las 14:00, se asigna a tarde.
     */
    private function detectarTurnoYFecha(Carbon $ahora): array
    {
        // Margen de anticipación en minutos (fichar hasta 2 horas antes)
        $margenAnticipacion = 120;

        // Obtener turnos con horarios definidos (excluir montaje, festivo, dinámico que no tienen hora)
        $turnos = Turno::whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $horaActual = $ahora->format('H:i:s');
        $fechaHoy = $ahora->toDateString();

        Log::info("🔍 detectarTurnoYFecha - Entrada", [
            'ahora' => $ahora->toDateTimeString(),
            'horaActual' => $horaActual,
            'fechaHoy' => $fechaHoy,
            'diaSemana' => $ahora->dayName,
            'margenAnticipacion' => $margenAnticipacion . ' minutos',
        ]);

        // Primero: buscar si estamos en el margen de anticipación de algún turno
        // (esto tiene prioridad sobre estar "dentro" de un turno que está terminando)
        foreach ($turnos as $turno) {
            $horaInicio = Carbon::createFromFormat('H:i:s', $turno->hora_inicio);
            $horaFin = Carbon::createFromFormat('H:i:s', $turno->hora_fin);
            $offsetInicio = $turno->offset_dias_inicio ?? 0;

            // Calcular el inicio del margen de anticipación
            $inicioMargen = $horaInicio->copy()->subMinutes($margenAnticipacion);
            $horaActualCarbon = Carbon::createFromFormat('H:i:s', $horaActual);

            $cruzaMedianoche = $turno->hora_inicio > $turno->hora_fin;

            Log::info("🔍 Evaluando anticipación turno: {$turno->nombre}", [
                'horaInicio' => $turno->hora_inicio,
                'inicioMargen' => $inicioMargen->format('H:i:s'),
                'horaActual' => $horaActual,
            ]);

            // Turno que NO cruza medianoche (mañana, tarde)
            if (!$cruzaMedianoche) {
                // ¿Estamos en el margen de anticipación? (ej: 13:00-14:00 para turno tarde)
                if ($horaActual >= $inicioMargen->format('H:i:s') && $horaActual < $turno->hora_inicio) {
                    $fechaAsignacion = $ahora->copy()->addDays(-$offsetInicio)->toDateString();
                    Log::info("✅ Turno detectado (anticipación): {$turno->nombre}", [
                        'fechaAsignacion' => $fechaAsignacion,
                        'razon' => "Hora {$horaActual} está en margen de anticipación ({$inicioMargen->format('H:i')}-{$turno->hora_inicio})",
                    ]);
                    return [$turno->nombre, $fechaAsignacion];
                }
            }
            // Turno que SÍ cruza medianoche (noche)
            else {
                // Margen de anticipación para noche (ej: 21:00-22:00)
                if ($horaActual >= $inicioMargen->format('H:i:s') && $horaActual < $turno->hora_inicio) {
                    $fechaAsignacion = $ahora->copy()->addDays(-$offsetInicio)->toDateString();
                    Log::info("✅ Turno detectado (anticipación noche): {$turno->nombre}", [
                        'fechaAsignacion' => $fechaAsignacion,
                    ]);
                    return [$turno->nombre, $fechaAsignacion];
                }
            }
        }

        // Segundo: buscar si estamos DENTRO de algún turno
        foreach ($turnos as $turno) {
            $horaInicio = $turno->hora_inicio;
            $horaFin = $turno->hora_fin;
            $offsetInicio = $turno->offset_dias_inicio ?? 0;
            $offsetFin = $turno->offset_dias_fin ?? 0;

            $cruzaMedianoche = $horaInicio > $horaFin;

            // Turno que NO cruza medianoche (ej: mañana 06:00-14:00, tarde 14:00-22:00)
            if (!$cruzaMedianoche) {
                if ($horaActual >= $horaInicio && $horaActual < $horaFin) {
                    $fechaAsignacion = $ahora->copy()->addDays(-$offsetInicio)->toDateString();
                    Log::info("✅ Turno detectado (dentro del turno): {$turno->nombre}", [
                        'fechaAsignacion' => $fechaAsignacion,
                    ]);
                    return [$turno->nombre, $fechaAsignacion];
                }
            }
            // Turno que SÍ cruza medianoche (ej: noche 22:00-06:00)
            else {
                // Estamos en la parte de la noche ANTES de medianoche (22:00-23:59)
                if ($horaActual >= $horaInicio) {
                    $fechaAsignacion = $ahora->copy()->addDays(-$offsetInicio)->toDateString();
                    Log::info("✅ Turno detectado (noche antes medianoche): {$turno->nombre}", [
                        'fechaAsignacion' => $fechaAsignacion,
                    ]);
                    return [$turno->nombre, $fechaAsignacion];
                }
                // Estamos en la parte de la noche DESPUÉS de medianoche (00:00-06:00)
                elseif ($horaActual < $horaFin) {
                    $fechaAsignacion = $ahora->copy()->addDays(-$offsetFin)->toDateString();
                    Log::info("✅ Turno detectado (noche después medianoche): {$turno->nombre}", [
                        'fechaAsignacion' => $fechaAsignacion,
                    ]);
                    return [$turno->nombre, $fechaAsignacion];
                }
            }
        }

        // Si no coincide con ningún turno definido
        Log::warning("No se detectó turno para hora: {$horaActual}");
        return [null, null];
    }

    /**
     * Devuelve la obra más cercana dentro de su radio permitido.
     * Busca en TODAS las obras que tengan coordenadas definidas.
     * Radio por defecto: 200 metros si la obra no tiene distancia configurada.
     */
    private function buscarObraCercana(float $lat, float $lon): ?Obra
    {
        // Buscar todas las obras que tengan coordenadas (latitud y longitud)
        $obras = Obra::whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get();

        $mejor = null;
        $distMin = null;
        $radioDefault = 200; // 200 metros de radio por defecto

        foreach ($obras as $obra) {
            // Calcular distancia entre la ubicacion del usuario y la obra
            $dist = $this->calcularDistancia($lat, $lon, $obra->latitud, $obra->longitud);

            // Usar el radio de la obra o el default si no tiene
            $radioPermitido = $obra->distancia ?? $radioDefault;

            // Si esta dentro del rango permitido
            if ($dist <= $radioPermitido) {
                // Guardar si es la mas cercana
                if (is_null($distMin) || $dist < $distMin) {
                    $distMin = $dist;
                    $mejor = $obra;
                }
            }
        }

        // Log para debug
        if ($mejor) {
            Log::info('Obra encontrada para fichaje', [
                'obra_id' => $mejor->id,
                'obra_nombre' => $mejor->obra,
                'distancia_metros' => round($distMin, 2),
                'radio_permitido' => $mejor->distancia ?? $radioDefault,
            ]);
        } else {
            Log::warning('No se encontro obra cercana', [
                'latitud' => $lat,
                'longitud' => $lon,
                'obras_con_coordenadas' => $obras->count(),
            ]);
        }

        return $mejor;
    }

    /**
     * Para SALIDA: intenta encontrar la asignación abierta más razonable
     * en las últimas 36h (entrada no nula y salida nula).
     */
    private function buscarAsignacionAbiertaParaSalida(User $user, Carbon $ahora): ?AsignacionTurno
    {
        $desde = $ahora->copy()->subHours(36)->toDateString();
        $hasta = $ahora->toDateString();

        return $user->asignacionesTurnos()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereNotNull('entrada')
            ->whereNull('salida')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Busca cualquier asignación reciente del usuario para fichaje de salida.
     * Incluye asignaciones con salida ya registrada (para turnos partidos).
     */
    private function buscarAsignacionRecienteParaSalida(User $user, Carbon $ahora): ?AsignacionTurno
    {
        $desde = $ahora->copy()->subHours(36)->toDateString();
        $hasta = $ahora->toDateString();

        // Primero buscar asignación abierta (sin salida)
        $abierta = $this->buscarAsignacionAbiertaParaSalida($user, $ahora);
        if ($abierta) {
            return $abierta;
        }

        // Si no hay abierta, buscar la más reciente con entrada (puede tener salida pero no salida2)
        return $user->asignacionesTurnos()
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereNotNull('entrada')
            ->where(function ($q) {
                // Turno partido incompleto: tiene salida pero no salida2
                $q->where(function ($q2) {
                    $q2->whereNotNull('salida')
                       ->whereNull('salida2');
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();
    }
    /**
     * Valida si la hora de entrada está dentro del margen permitido.
     * Margen: 15 min antes de hora_inicio hasta 30 min después.
     */
    private function validarHoraEntrada($turnoNombre, $horaActual)
    {
        $turno = Turno::where('nombre', $turnoNombre)->first();
        if (!$turno || !$turno->hora_inicio) {
            return true; // Si no tiene horario definido, permitir
        }

        try {
            $hora = Carbon::createFromFormat('H:i:s', $horaActual);
            $horaInicio = Carbon::createFromFormat('H:i:s', $turno->hora_inicio);

            // Margen: 15 min antes hasta 30 min después de hora_inicio
            $limiteAntes = $horaInicio->copy()->subMinutes(15);
            $limiteDespues = $horaInicio->copy()->addMinutes(30);

            // Para turnos nocturnos que cruzan medianoche
            if ($turno->hora_inicio > $turno->hora_fin) {
                // Si la hora actual es antes de medianoche
                if ($hora->format('H:i:s') >= '12:00:00') {
                    return $hora->format('H:i:s') >= $limiteAntes->format('H:i:s');
                }
                // Si la hora actual es después de medianoche (madrugada)
                return $hora->format('H:i:s') <= $limiteDespues->format('H:i:s');
            }

            return $hora->between($limiteAntes, $limiteDespues);
        } catch (\Exception $e) {
            Log::error("Error validando hora entrada: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Valida si la hora de salida está dentro del margen permitido.
     * Margen: 15 min antes de hora_fin hasta 30 min después.
     */
    private function validarHoraSalida($turnoNombre, $horaActual)
    {
        $turno = Turno::where('nombre', $turnoNombre)->first();
        if (!$turno || !$turno->hora_fin) {
            return true; // Si no tiene horario definido, permitir
        }

        try {
            $hora = Carbon::createFromFormat('H:i:s', $horaActual);
            $horaFin = Carbon::createFromFormat('H:i:s', $turno->hora_fin);

            // Margen: 15 min antes hasta 30 min después de hora_fin
            $limiteAntes = $horaFin->copy()->subMinutes(15);
            $limiteDespues = $horaFin->copy()->addMinutes(30);

            // Para turnos nocturnos que cruzan medianoche
            if ($turno->hora_inicio > $turno->hora_fin) {
                // La salida del turno nocturno es por la mañana (antes de las 12)
                if ($hora->format('H:i:s') < '12:00:00') {
                    return $hora->between($limiteAntes, $limiteDespues);
                }
                return false;
            }

            return $hora->between($limiteAntes, $limiteDespues);
        } catch (\Exception $e) {
            Log::error("Error validando hora salida: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Calcula la distancia en metros entre dos puntos geográficos usando la fórmula de Haversine.
     */
    private function calcularDistancia($lat1, $lon1, $lat2, $lon2)
    {
        $radioTierra = 6371000; // Radio de la Tierra en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $radioTierra * $c; // Distancia en metros
    }

    public function store(Request $request)
    {
        try {
            Log::channel('planificacion_trabajadores_taller')->info('[store] Creando asignación', [
                'user_id' => $request->user_id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'tipo' => $request->tipo,
                'maquina_id' => $request->maquina_id,
                'obra_id' => $request->obra_id,
                'ejecutado_por' => auth()->id(),
            ]);

            $request->validate([
                'user_id'      => 'required|exists:users,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
                'tipo'         => 'required|string',
                'entrada'      => 'nullable|date_format:H:i',
                'salida'       => 'nullable|date_format:H:i',
                'entrada2'     => 'nullable|date_format:H:i',
                'salida2'      => 'nullable|date_format:H:i',
                'obra_id'      => 'nullable|exists:obras,id',
                'maquina_id'   => 'nullable|exists:maquinas,id',
            ]);

            if (in_array($request->tipo, ['eliminarEstado', 'eliminarTurnoEstado'])) {
                return response()->json(['error' => 'Esta operación debe gestionarse por otro método.'], 400);
            }

            $tipo        = $request->tipo;
            // Parsear solo la parte de fecha (YYYY-MM-DD) para evitar problemas de zona horaria
            $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fechaFin    = Carbon::parse($request->fecha_fin)->startOfDay();

            // 🔹 SOLO ACTUALIZAR HORAS (sin cambiar turno/estado)
            if ($tipo === 'soloHoras') {
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
                $actualizados = 0;

                foreach ($periodo as $fecha) {
                    $asignacion = AsignacionTurno::where('user_id', $request->user_id)
                        ->whereDate('fecha', $fecha->toDateString())
                        ->first();

                    if ($asignacion) {
                        $datos = [];
                        if ($request->filled('entrada')) {
                            $datos['entrada'] = $request->entrada;
                        }
                        if ($request->filled('salida')) {
                            $datos['salida'] = $request->salida;
                        }
                        // Segunda jornada (turno partido): has() permite enviar null para limpiarla
                        if ($request->has('entrada2')) {
                            $datos['entrada2'] = $request->entrada2;
                        }
                        if ($request->has('salida2')) {
                            $datos['salida2'] = $request->salida2;
                        }
                        if (!empty($datos)) {
                            $asignacion->update($datos);
                            $actualizados++;
                        }
                    }
                }

                if ($actualizados === 0) {
                    return response()->json(['error' => 'No se encontraron asignaciones para actualizar en las fechas seleccionadas.'], 400);
                }

                return response()->json(['success' => "Horas actualizadas en {$actualizados} día(s)."]);
            }

            // 🔹 NUEVO COMPORTAMIENTO PARA FESTIVOS
            if ($tipo === 'festivo') {
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
                $titulo  = $request->filled('titulo') ? $request->titulo : 'Festivo';

                $filas = collect($periodo)->map(function ($fecha) use ($titulo) {
                    return [
                        'titulo'     => $titulo,
                        'fecha'      => $fecha->toDateString(),
                        'anio'       => (int) $fecha->format('Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                // Evita duplicados: upsert por (anio, fecha) y actualiza título si cambia
                Festivo::upsert(
                    $filas->toArray(),
                    ['anio', 'fecha'],
                    ['titulo', 'updated_at']
                );

                return response()->json([
                    'success' => "Se han registrado {$filas->count()} día(s) festivo(s) en la tabla.",
                ]);
            }


            // ====== A partir de aquí, el flujo normal para turnos/estados/vacaciones ======

            $turnosValidos = Turno::pluck('nombre')->toArray();
            $esTurno = in_array($tipo, $turnosValidos);
            $turno = $esTurno ? Turno::where('nombre', $tipo)->first() : null;

            // Ahora los festivos vienen de tu tabla
            $festivos = collect($this->getFestivos())->pluck('start')->toArray();

            // Solo el usuario seleccionado (ya no "todos" en caso de festivo)
            $usuarios = collect([User::with('incorporacion')->findOrFail($request->user_id)]);

            foreach ($usuarios as $user) {
                $maquinaAsignada = $request->maquina_id ?? $user->maquina?->id;

                $diasSolicitados = 0;

                // Determinar si dividir entre años
                $usarAnteriorPrimero = $request->boolean('usar_anterior_primero', false);
                $diasDisponiblesAnterior = (int) $request->input('dias_disponibles_anterior', 0);
                $anioAnterior = (int) $request->input('anio_anterior', $fechaInicio->year - 1);
                $anioActual = $fechaInicio->year;
                $anioCargo = $request->input('anio_cargo', $anioActual);

                if ($tipo === 'vacaciones') {
                    // Contar días ya asignados para cada año
                    $yaDisfrutadosAnterior = $user->asignacionesTurnos()
                        ->where('estado', 'vacaciones')
                        ->where('anio_cargo', $anioAnterior)
                        ->count();

                    $yaDisfrutadosActual = $user->asignacionesTurnos()
                        ->where('estado', 'vacaciones')
                        ->where('anio_cargo', $anioActual)
                        ->count();

                    $yaDisfrutados = $usarAnteriorPrimero ? $yaDisfrutadosAnterior :
                        ($anioCargo == $anioAnterior ? $yaDisfrutadosAnterior : $yaDisfrutadosActual);

                    $tempDate = $fechaInicio->copy();
                    while ($tempDate->lte($fechaFin)) {
                        $tempStr = $tempDate->toDateString();
                        if (
                            !in_array($tempDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) &&
                            !in_array($tempStr, $festivos)
                        ) {
                            $asignacion = AsignacionTurno::where('user_id', $user->id)
                                ->whereDate('fecha', $tempStr)
                                ->first();

                            if (!$asignacion || $asignacion->estado !== 'vacaciones') {
                                $diasSolicitados++;
                            }
                        }
                        $tempDate->addDay();
                    }

                    $totalPermitido = $user->vacaciones_correspondientes;

                    // Validar según si se divide entre años o no
                    if ($usarAnteriorPrimero) {
                        // Calcular cuántos días irán a cada año
                        $disponiblesAnteriorReal = max(0, $totalPermitido - $yaDisfrutadosAnterior);
                        $diasParaAnterior = min($diasSolicitados, min($diasDisponiblesAnterior, $disponiblesAnteriorReal));
                        $diasParaActual = $diasSolicitados - $diasParaAnterior;

                        // Validar año actual si hay días para él
                        if ($diasParaActual > 0 && ($yaDisfrutadosActual + $diasParaActual) > $totalPermitido) {
                            $msg = "El usuario {$user->name} ya tiene {$yaDisfrutadosActual} días en {$anioActual} y quiere añadir {$diasParaActual}. Máximo: {$totalPermitido}.";
                            return response()->json(['error' => $msg], 400);
                        }
                    } else {
                        if (($yaDisfrutados + $diasSolicitados) > $totalPermitido) {
                            $msg = "El usuario {$user->name} ya tiene {$yaDisfrutados} días y quiere añadir {$diasSolicitados}. Máximo: {$totalPermitido}.";
                            return response()->json(['error' => $msg], 400);
                        }
                    }

                    // ✅ Crear alerta personalizada si se asignan vacaciones
                    $alerta = Alerta::create([
                        'user_id_1'       => auth()->id(),
                        'destinatario_id' => $user->id,
                        'mensaje'         => "{$user->nombre_completo}, Se te han asignado vacaciones del {$fechaInicio->format('Y-m-d')} al {$fechaFin->format('Y-m-d')}.",
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    // ✅ Registrar como alerta pendiente de leer
                    AlertaLeida::create([
                        'alerta_id' => $alerta->id,
                        'user_id'   => $user->id,
                        'leida_en'  => null,
                    ]);
                }

                // Usar CarbonPeriod para iterar de forma confiable sobre el rango de fechas
                $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

                // Contador para dividir días entre años
                $diasAsignadosAnterior = 0;
                $diasParaAnterior = $usarAnteriorPrimero ? min($diasSolicitados, $diasDisponiblesAnterior) : 0;

                foreach ($periodo as $currentDate) {
                    $dateStr = $currentDate->toDateString();

                    // Saltar fines de semana y festivos para vacaciones y turnos
                    if (
                        ($tipo === 'vacaciones' || $esTurno) &&
                        (in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
                            in_array($dateStr, $festivos))
                    ) {
                        continue;
                    }

                    $asignacion = AsignacionTurno::where('user_id', $user->id)
                        ->whereDate('fecha', $dateStr)
                        ->first();

                    $estadoNuevo = $esTurno ? 'activo' : $tipo;

                    $datos = [];

                    if ($esTurno || $tipo !== 'activo') {
                        $datos['estado'] = $estadoNuevo;
                        if ($esTurno) {
                            $datos['turno_id'] = $turno->id;
                        }
                        $datos['maquina_id'] = $request->maquina_id ?? ($asignacion ? $asignacion->maquina_id : null) ?? $user->maquina_id;
                    }

                    if ($request->has('entrada')) {
                        $datos['entrada'] = $request->entrada;
                    }
                    if ($request->has('salida')) {
                        $datos['salida'] = $request->salida;
                    }
                    // Segunda jornada (turno partido)
                    if ($request->has('entrada2')) {
                        $datos['entrada2'] = $request->entrada2;
                    }
                    if ($request->has('salida2')) {
                        $datos['salida2'] = $request->salida2;
                    }
                    if ($request->has('obra_id')) {
                        $datos['obra_id'] = $request->obra_id;
                    }

                    // Añadir año de cargo si es vacaciones (con división automática si aplica)
                    if ($tipo === 'vacaciones') {
                        if ($usarAnteriorPrimero && $diasAsignadosAnterior < $diasParaAnterior) {
                            $datos['anio_cargo'] = $anioAnterior;
                            $diasAsignadosAnterior++;
                        } else {
                            $datos['anio_cargo'] = $usarAnteriorPrimero ? $anioActual : $anioCargo;
                        }
                    }

                    // Buscar asignación existente (incluyendo soft-deleted) con whereDate
                    $asignacionExistente = AsignacionTurno::withTrashed()
                        ->where('user_id', $user->id)
                        ->whereDate('fecha', $dateStr)
                        ->first();

                    if ($asignacionExistente) {
                        // Restaurar si estaba eliminada
                        if ($asignacionExistente->trashed()) {
                            $asignacionExistente->restore();
                        }
                        $asignacionExistente->update($datos);
                    } else {
                        // Usar try-catch para manejar posibles condiciones de carrera
                        try {
                            AsignacionTurno::create(array_merge($datos, [
                                'user_id' => $user->id,
                                'fecha'   => $dateStr,
                            ]));
                        } catch (\Illuminate\Database\QueryException $e) {
                            // Si es error de duplicado, intentar actualizar (incluye soft-deleted)
                            if ($e->errorInfo[1] == 1062) {
                                $asignacion = AsignacionTurno::withTrashed()
                                    ->where('user_id', $user->id)
                                    ->whereDate('fecha', $dateStr)
                                    ->first();
                                if ($asignacion) {
                                    if ($asignacion->trashed()) {
                                        $asignacion->restore();
                                    }
                                    $asignacion->update($datos);
                                }
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }

            return response()->json(['success' => 'Asignación completada.']);
        } catch (\Exception $e) {
            Log::error('❌ Error en store fusionado: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error al registrar la asignación.'], 500);
        }
    }

    private function getFestivos()
    {
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/" . date('Y') . "/ES");

        if ($response->failed()) {
            return []; // Si la API falla, devolvemos un array vacío
        }

        $festivos = collect($response->json())->filter(function ($holiday) {
            // Si no tiene 'counties', es un festivo NACIONAL
            if (!isset($holiday['counties'])) {
                return true;
            }
            // Si el festivo pertenece a Andalucía
            return in_array('ES-AN', $holiday['counties']);
        })->map(function ($holiday) {
            return [
                'title' => $holiday['localName'], // Nombre del festivo
                'start' => Carbon::parse($holiday['date'])->toDateString(), // Fecha formateada correctamente
                'backgroundColor' => '#ff0000', // Rojo para festivos
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'allDay' => true
            ];
        });

        // Añadir festivos locales de Los Palacios y Villafranca
        $festivosLocales = collect([
            [
                'title' => 'Festividad de Nuestra Señora de las Nieves',
                'start' => date('Y') . '-08-05',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'editable' => true,
                'allDay' => true
            ],
            [
                'title' => 'Feria Los Palacios y Vfca',
                'start' => date('Y') . '-09-25',
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'editable' => true,
                'allDay' => true
            ]
        ]);

        // Combinar festivos nacionales, autonómicos y locales
        return $festivos->merge($festivosLocales)->values()->toArray();
    }

    public function update(Request $request, $id)
    {
        try {
            Log::channel('planificacion_trabajadores_taller')->info('[update] Actualizando asignación', [
                'asignacion_id' => $id,
                'cambios' => $request->all(),
                'ejecutado_por' => auth()->id(),
            ]);

            $asignacion = AsignacionTurno::findOrFail($id);

            // Validar los campos que puedes editar en línea
            $validated = $request->validate([
                'fecha' => 'nullable|date',
                'entrada' => 'nullable|date_format:H:i',
                'salida' => 'nullable|date_format:H:i',
                'maquina_id' => 'nullable|exists:maquinas,id',
                'obra_id' => 'nullable|exists:obras,id',
                'estado' => 'nullable|string|in:activo,curso,vacaciones,baja,justificada,injustificada',
            ]);

            $asignacion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Asignación actualizada correctamente.'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error actualizando asignación', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la asignación.',
                'errors' => ['exception' => $e->getMessage()]
            ], 500);
        }
    }

    public function actualizarHoras(Request $request, $id)
    {
        try {
            Log::channel('planificacion_trabajadores_taller')->info('[actualizarHoras] Actualizando horas', [
                'asignacion_id' => $id,
                'entrada' => $request->entrada,
                'salida' => $request->salida,
                'ejecutado_por' => auth()->id(),
            ]);

            $request->validate(
                [
                    'entrada' => 'nullable|date_format:H:i',
                    'salida'  => 'nullable|date_format:H:i',
                ],
                [
                    'entrada.date_format' => 'El campo entrada debe tener el formato HH:mm (por ejemplo 08:30).',
                    'salida.date_format'  => 'El campo salida debe tener el formato HH:mm (por ejemplo 17:45).',
                ]
            );

            $asignacion = AsignacionTurno::findOrFail($id);
            $asignacion->entrada = $request->entrada;
            $asignacion->salida  = $request->salida;
            $asignacion->save();

            return response()->json([
                'ok'      => true,
                'entrada' => $request->entrada,
                'salida'  => $request->salida,
                'message' => 'Horas actualizadas correctamente'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación
            return response()->json([
                'ok'      => false,
                'message' => 'Datos no válidos',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Cualquier otro error
            return response()->json([
                'ok'      => false,
                'message' => 'Error al actualizar horas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[destroy] Eliminando asignación', [
            'user_id' => $request->user_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'tipo' => $request->tipo,
            'ejecutado_por' => auth()->id(),
        ]);

        try {
            // Normalizar fechas (FullCalendar puede enviar datetime con T)
            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->fecha_fin ?? $request->fecha_inicio;

            // Extraer solo la fecha si viene con tiempo
            if ($fechaInicio && str_contains($fechaInicio, 'T')) {
                $fechaInicio = substr($fechaInicio, 0, 10);
            }
            if ($fechaFin && str_contains($fechaFin, 'T')) {
                $fechaFin = substr($fechaFin, 0, 10);
            }

            $request->merge([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]);

            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            ]);

            if ($request->filled('tipo_turno') && $request->tipo_turno === 'festivo') {
                $turno = Turno::where('nombre', 'festivo')->first();

                if (!$turno) {
                    return response()->json(['error' => 'El turno festivo no está configurado.'], 400);
                }

                AsignacionTurno::where('turno_id', $turno->id)
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->delete();

                return response()->json(['success' => 'Turnos festivos eliminados para todos los usuarios.']);
            }

            // Verificar que user_id no sea null o undefined
            if (!$request->filled('user_id')) {
                Log::warning('AsignacionTurno destroy - user_id no proporcionado', $request->all());
                return response()->json(['error' => 'No se proporcionó el ID del usuario'], 400);
            }

            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo'    => 'required|in:eliminarTurnoEstado,eliminarEstado',
            ]);

            $tipo = trim($request->tipo);

            $user = User::findOrFail($request->user_id);

            $asignaciones = AsignacionTurno::where('user_id', $user->id)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->get();

            foreach ($asignaciones as $asignacion) {
                if ($tipo === 'eliminarTurnoEstado') {
                    $asignacion->delete();
                } elseif ($tipo === 'eliminarEstado') {
                    $nuevoEstado = $asignacion->turno_id ? 'activo' : null;

                    $asignacion->update([
                        'estado' => $nuevoEstado,
                    ]);
                }
            }

            return response()->json([
                'success' => $tipo === 'eliminarTurnoEstado'
                    ? 'Turnos eliminados correctamente.'
                    : 'Estado eliminado correctamente.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('AsignacionTurno destroy - Validación fallida', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Datos inválidos: ' . collect($e->errors())->flatten()->first()], 422);
        } catch (\Exception $e) {
            Log::error('AsignacionTurno destroy - Error', [
                'message' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Error al eliminar los turnos: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        abort(404); // o simplemente return response('No disponible', 404);
    }

    public function asignarObra(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[asignarObra] Asignando obra a trabajador', [
            'user_id' => $request->user_id,
            'fecha' => $request->fecha,
            'obra_id' => $request->obra_id,
            'ejecutado_por' => auth()->id(),
        ]);

        // 👇 Forzar null si viene cadena vacía
        if ($request->obra_id === '') {
            $request->merge(['obra_id' => null]);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'obra_id' => ['nullable', 'integer', 'exists:obras,id']

        ]);
        $turnoMontajeId = Turno::where('nombre', 'montaje')->firstOrFail()->id;

        // Buscar asignación de turno para ese día (incluyendo soft-deleted)
        $asignacion = AsignacionTurno::withTrashed()
            ->where('user_id', $validated['user_id'])
            ->whereDate('fecha', $validated['fecha'])
            ->first();

        // Si existe pero está soft-deleted, restaurarla
        if ($asignacion && $asignacion->trashed()) {
            $asignacion->restore();
        }

        // Si no existe, crear nueva
        if (!$asignacion) {
            $asignacion = new AsignacionTurno();
            $asignacion->user_id = $validated['user_id'];
            $asignacion->fecha = $validated['fecha'];
            $asignacion->turno_id = $turnoMontajeId;
        }

        // Asignar o actualizar obra
        $asignacion->obra_id = $validated['obra_id'];
        $asignacion->save();

        $user = $asignacion->user()->with('categoria')->first();
        $turno = $asignacion->turno;

        return response()->json([
            'success' => true,
            'message' => '✅ Obra asignada correctamente',
            'asignacion' => $asignacion,
            'user' => $user,
            'turno' => $turno,
            'fecha' => $validated['fecha'],
            'obra_id' => $validated['obra_id']
        ]);
    }

    public function asignarObraMultiple(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[asignarObraMultiple] Asignando obra a múltiples trabajadores', [
            'user_ids' => $request->user_ids,
            'obra_id' => $request->obra_id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'ejecutado_por' => auth()->id(),
        ]);

        // 👇 Forzar null si viene cadena vacía o "sin-obra"
        if (in_array($request->obra_id, ['', 'sin-obra', null], true)) {
            $request->merge(['obra_id' => null]);
        } else {
            $request->merge(['obra_id' => (int) $request->obra_id]);
        }

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'obra_id' => ['nullable', 'integer', 'exists:obras,id'],
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $turnoMontajeId = Turno::where('nombre', 'montaje')->firstOrFail()->id; // 👈 AÑADIDO

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);

        foreach ($request->user_ids as $userId) {
            $fecha = $fechaInicio->copy();
            while ($fecha->lte($fechaFin)) {
                $fechaStr = $fecha->toDateString();

                // Buscar asignación existente (incluyendo soft-deleted)
                $asignacion = AsignacionTurno::withTrashed()
                    ->where('user_id', $userId)
                    ->whereDate('fecha', $fechaStr)
                    ->first();

                if ($asignacion) {
                    // Si está soft-deleted, restaurarla
                    if ($asignacion->trashed()) {
                        $asignacion->restore();
                    }
                    // Actualizar
                    $asignacion->update([
                        'obra_id' => $request->obra_id,
                        'turno_id' => $turnoMontajeId,
                    ]);
                } else {
                    // Crear nueva
                    AsignacionTurno::create([
                        'user_id' => $userId,
                        'fecha' => $fechaStr,
                        'obra_id' => $request->obra_id,
                        'turno_id' => $turnoMontajeId,
                    ]);
                }

                $fecha->addDay();
            }
        }

        return response()->json(['success' => true]);
    }


    public function updateObra(Request $request, $id)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[updateObra] Actualizando obra de asignación', [
            'asignacion_id' => $id,
            'obra_id' => $request->obra_id,
            'fecha' => $request->fecha,
            'ejecutado_por' => auth()->id(),
        ]);

        // 🛠️ Corregimos obra_id si llega como string vacío
        if (in_array($request->obra_id, ['', 'sin-obra', 'null', null], true)) {
            $request->merge(['obra_id' => null]);
        }


        $request->validate([
            'obra_id' => 'nullable|exists:obras,id',
            'fecha' => 'required|date',
        ]);

        $asignacion = AsignacionTurno::findOrFail($id);

        // Verificar si existe otra asignación (incluyendo soft-deleted) para esa fecha
        $existeOtra = AsignacionTurno::withTrashed()
            ->where('user_id', $asignacion->user_id)
            ->whereDate('fecha', $request->fecha)
            ->where('id', '!=', $asignacion->id)
            ->first();

        if ($existeOtra) {
            // Si la otra está soft-deleted, eliminarla definitivamente
            if ($existeOtra->trashed()) {
                $existeOtra->forceDelete();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otra asignación para este usuario en esa fecha.'
                ]);
            }
        }

        $asignacion->obra_id = $request->obra_id;
        $asignacion->fecha = $request->fecha;
        $asignacion->save();

        return response()->json(['success' => true]);
    }

    public function repetirSemana(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[repetirSemana] Repitiendo semana anterior', [
            'fecha_actual' => $request->fecha_actual,
            'ejecutado_por' => auth()->id(),
        ]);

        $request->validate([
            'fecha_actual' => 'required|date',
        ]);

        $inicioSemana = Carbon::parse($request->fecha_actual)->startOfWeek();
        $inicioAnterior = $inicioSemana->copy()->subWeek();
        $finAnterior = $inicioAnterior->copy()->endOfWeek();

        // Repetir asignaciones normales
        $asignaciones = AsignacionTurno::whereBetween('fecha', [$inicioAnterior, $finAnterior])->get();

        foreach ($asignaciones as $asignacion) {
            $nuevaFecha = Carbon::parse($asignacion->fecha)->addWeek();
            // Verifica si ya existe (sin soft-deleted)
            $existe = AsignacionTurno::where('user_id', $asignacion->user_id)
                ->whereDate('fecha', $nuevaFecha)
                ->exists();

            if (!$existe) {
                // Limpiar cualquier soft-deleted para evitar conflicto de unique
                ValidadorAsignaciones::limpiarSoftDeleted($asignacion->user_id, $nuevaFecha->toDateString());

                AsignacionTurno::create([
                    'user_id' => $asignacion->user_id,
                    'obra_id' => $asignacion->obra_id,
                    'fecha' => $nuevaFecha,
                    'estado' => 'activo', // Solo copiamos la obra, no el estado (vacaciones, baja, etc.)
                    'turno_id' => $asignacion->turno_id,
                    'maquina_id' => $asignacion->maquina_id,
                ]);
            }
        }

        // Repetir eventos ficticios
        $eventosFicticios = \App\Models\EventoFicticioObra::whereBetween('fecha', [$inicioAnterior, $finAnterior])->get();

        foreach ($eventosFicticios as $evento) {
            $nuevaFecha = Carbon::parse($evento->fecha)->addWeek();
            $existe = \App\Models\EventoFicticioObra::where('trabajador_ficticio_id', $evento->trabajador_ficticio_id)
                ->whereDate('fecha', $nuevaFecha)
                ->where('obra_id', $evento->obra_id)
                ->exists();

            if (!$existe) {
                \App\Models\EventoFicticioObra::create([
                    'trabajador_ficticio_id' => $evento->trabajador_ficticio_id,
                    'obra_id' => $evento->obra_id,
                    'fecha' => $nuevaFecha,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function repetirSemanaObra(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[repetirSemanaObra] Repitiendo semana por obra', [
            'fecha_actual' => $request->fecha_actual,
            'obra_id' => $request->obra_id,
            'ejecutado_por' => auth()->id(),
        ]);

        $request->validate([
            'fecha_actual' => 'required|date',
            'obra_id' => 'required|exists:obras,id',
        ]);

        $obraId = $request->obra_id;
        $inicioSemana = Carbon::parse($request->fecha_actual)->startOfWeek();
        $inicioAnterior = $inicioSemana->copy()->subWeek();
        $finAnterior = $inicioAnterior->copy()->endOfWeek();

        // Repetir asignaciones normales
        $asignaciones = AsignacionTurno::whereBetween('fecha', [$inicioAnterior, $finAnterior])
            ->where('obra_id', $obraId)
            ->get();

        $copiadas = 0;
        foreach ($asignaciones as $asignacion) {
            $nuevaFecha = Carbon::parse($asignacion->fecha)->addWeek();

            // Verifica si ya existe (sin importar la obra, un usuario solo puede tener 1 asignación por día)
            $existe = AsignacionTurno::where('user_id', $asignacion->user_id)
                ->whereDate('fecha', $nuevaFecha)
                ->exists();

            if (!$existe) {
                // Limpiar cualquier soft-deleted para evitar conflicto de unique
                ValidadorAsignaciones::limpiarSoftDeleted($asignacion->user_id, $nuevaFecha->toDateString());

                AsignacionTurno::create([
                    'user_id' => $asignacion->user_id,
                    'obra_id' => $asignacion->obra_id,
                    'fecha' => $nuevaFecha,
                    'estado' => 'activo', // Solo copiamos la obra, no el estado (vacaciones, baja, etc.)
                    'turno_id' => $asignacion->turno_id,
                    'maquina_id' => $asignacion->maquina_id,
                ]);
                $copiadas++;
            }
        }

        // Repetir eventos ficticios de la misma obra
        $eventosFicticios = \App\Models\EventoFicticioObra::whereBetween('fecha', [$inicioAnterior, $finAnterior])
            ->where('obra_id', $obraId)
            ->get();

        $copiadasFicticias = 0;
        foreach ($eventosFicticios as $evento) {
            $nuevaFecha = Carbon::parse($evento->fecha)->addWeek();
            $existe = \App\Models\EventoFicticioObra::where('trabajador_ficticio_id', $evento->trabajador_ficticio_id)
                ->whereDate('fecha', $nuevaFecha)
                ->where('obra_id', $obraId)
                ->exists();

            if (!$existe) {
                \App\Models\EventoFicticioObra::create([
                    'trabajador_ficticio_id' => $evento->trabajador_ficticio_id,
                    'obra_id' => $evento->obra_id,
                    'fecha' => $nuevaFecha,
                ]);
                $copiadasFicticias++;
            }
        }

        $total = $copiadas + $copiadasFicticias;
        return response()->json([
            'success' => true,
            'message' => "Se copiaron {$total} asignaciones ({$copiadas} normales, {$copiadasFicticias} ficticias)."
        ]);
    }

    /**
     * Limpia el obra_id de las asignaciones de una semana
     * Excluye las obras de Hierros Paco Reyes (naves propias)
     * Puede limpiar todas las obras o solo una específica
     */
    public function limpiarSemana(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[limpiarSemana] Limpiando asignaciones de semana', [
            'fecha_actual' => $request->fecha_actual,
            'obra_id' => $request->obra_id,
            'ejecutado_por' => auth()->id(),
        ]);

        $request->validate([
            'fecha_actual' => 'required|date',
            'obra_id' => 'nullable|exists:obras,id',
        ]);

        $inicioSemana = Carbon::parse($request->fecha_actual)->startOfWeek();
        $finSemana = $inicioSemana->copy()->endOfWeek();

        // Obtener IDs de obras de Hierros Paco Reyes (no se deben limpiar)
        $obrasPacoReyes = Obra::getNavesPacoReyes()->pluck('id')->toArray();

        // Query base para asignaciones normales (excluyendo obras de Paco Reyes)
        $queryAsignaciones = AsignacionTurno::whereBetween('fecha', [$inicioSemana, $finSemana])
            ->whereNotNull('obra_id')
            ->whereNotIn('obra_id', $obrasPacoReyes);

        // Query base para eventos ficticios (excluyendo obras de Paco Reyes)
        $queryFicticios = \App\Models\EventoFicticioObra::whereBetween('fecha', [$inicioSemana, $finSemana])
            ->whereNotIn('obra_id', $obrasPacoReyes);

        // Filtrar por obra si se especifica
        if ($request->obra_id) {
            // Verificar que la obra especificada no sea de Paco Reyes
            if (in_array($request->obra_id, $obrasPacoReyes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden limpiar obras de Hierros Paco Reyes.'
                ]);
            }
            $queryAsignaciones->where('obra_id', $request->obra_id);
            $queryFicticios->where('obra_id', $request->obra_id);
        }

        // Quitar obra_id de las asignaciones (no eliminar el registro)
        $limpiadasNormales = $queryAsignaciones->count();
        $queryAsignaciones->update(['obra_id' => null]);

        // Eliminar eventos ficticios (estos sí se eliminan porque no tienen sentido sin obra)
        $eliminadasFicticias = $queryFicticios->count();
        $queryFicticios->delete();

        $total = $limpiadasNormales + $eliminadasFicticias;

        return response()->json([
            'success' => true,
            'message' => "Se limpiaron {$total} asignaciones ({$limpiadasNormales} normales, {$eliminadasFicticias} ficticias)."
        ]);
    }

    /**
     * Repite los turnos de la semana anterior para una máquina específica
     * Usado desde el calendario de trabajadores (clic derecho en máquina)
     */
    public function repetirSemanaMaquina(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[repetirSemanaMaquina] Repitiendo semana por máquina', [
            'maquina_id' => $request->maquina_id,
            'semana_inicio' => $request->semana_inicio,
            'duracion_semanas' => $request->duracion_semanas,
            'ejecutado_por' => auth()->id(),
        ]);

        $request->validate([
            'maquina_id' => 'required|exists:maquinas,id',
            'semana_inicio' => 'required|date',
            'duracion_semanas' => 'required|integer|min:1|max:2',
        ]);

        $maquinaId = $request->maquina_id;
        $duracionSemanas = $request->duracion_semanas;
        $inicioSemanaActual = Carbon::parse($request->semana_inicio)->startOfWeek();
        $inicioSemanaAnterior = $inicioSemanaActual->copy()->subWeek();
        $finSemanaAnterior = $inicioSemanaAnterior->copy()->endOfWeek();

        // Obtener la máquina para saber su obra_id
        $maquina = \App\Models\Maquina::find($maquinaId);
        $obraId = $maquina->obra_id;

        // Colores por obra
        $coloresEventos = [
            1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'],
            2 => ['bg' => '#6EE7B7', 'border' => '#34D399'],
            3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'],
        ];
        $colorEvento = $coloresEventos[$obraId] ?? ['bg' => '#d1d5db', 'border' => '#9ca3af'];

        // Obtener asignaciones de la semana anterior para esta máquina
        $asignaciones = AsignacionTurno::with(['user', 'turno'])
            ->whereBetween('fecha', [$inicioSemanaAnterior, $finSemanaAnterior])
            ->where('maquina_id', $maquinaId)
            ->get();

        $turnosCreados = 0;
        $eventosCreados = [];

        // Copiar a las semanas solicitadas
        for ($semana = 0; $semana < $duracionSemanas; $semana++) {
            $offsetSemanas = $semana; // 0 = semana actual, 1 = semana siguiente

            foreach ($asignaciones as $asignacion) {
                $nuevaFecha = Carbon::parse($asignacion->fecha)->addWeeks($offsetSemanas + 1);

                // Verificar si ya existe para evitar duplicados
                $existe = AsignacionTurno::where('user_id', $asignacion->user_id)
                    ->whereDate('fecha', $nuevaFecha)
                    ->exists();

                if (!$existe) {
                    // Limpiar cualquier soft-deleted para evitar conflicto de unique
                    ValidadorAsignaciones::limpiarSoftDeleted($asignacion->user_id, $nuevaFecha->toDateString());

                    $nuevaAsignacion = AsignacionTurno::create([
                        'user_id' => $asignacion->user_id,
                        'obra_id' => $asignacion->obra_id,
                        'fecha' => $nuevaFecha,
                        'estado' => 'activo',
                        'turno_id' => $asignacion->turno_id,
                        'maquina_id' => $asignacion->maquina_id,
                    ]);

                    $turnosCreados++;

                    // Mapeo visual usando TurnoMapper
                    $fechaStr = $nuevaFecha->format('Y-m-d');
                    $turnoModel = $asignacion->turno;
                    $slot = TurnoMapper::getSlotParaTurnoModel($turnoModel, $fechaStr);

                    $eventosCreados[] = [
                        'id' => 'turno-' . $nuevaAsignacion->id,
                        'title' => $asignacion->user->nombre_completo ?? $asignacion->user->name,
                        'start' => $slot['start'],
                        'end' => $slot['end'],
                        'resourceId' => $maquinaId,
                        'backgroundColor' => $colorEvento['bg'],
                        'borderColor' => $colorEvento['border'],
                        'textColor' => '#000000',
                        'extendedProps' => [
                            'user_id' => $asignacion->user_id,
                            'turno' => $turnoModel->nombre ?? null,
                            'categoria_nombre' => $asignacion->user->categoria->nombre ?? null,
                            'entrada' => null,
                            'salida' => null,
                            'foto' => $asignacion->user->ruta_imagen ?? null,
                            'es_festivo' => false,
                        ],
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se copiaron {$turnosCreados} turnos correctamente.",
            'turnos_creados' => $turnosCreados,
            'eventos' => $eventosCreados,
        ]);
    }

    /**
     * Copia las asignaciones de un día a otro (persistiendo en BD)
     * Usado desde el calendario de trabajadores
     */
    public function copiarDia(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[copiarDia] Copiando asignaciones de un día a otro', [
            'fecha_origen' => $request->fecha_origen,
            'fecha_destino' => $request->fecha_destino,
            'maquina_id' => $request->maquina_id,
            'ejecutado_por' => auth()->id(),
        ]);

        $request->validate([
            'fecha_origen' => 'required|date',
            'fecha_destino' => 'required|date',
            'maquina_id' => 'nullable|exists:maquinas,id',
        ]);

        $fechaOrigen = $request->fecha_origen;
        $fechaDestino = $request->fecha_destino;
        $maquinaId = $request->maquina_id;

        // Obtener asignaciones del día origen
        $query = AsignacionTurno::with(['user.categoria', 'turno', 'obra'])
            ->whereDate('fecha', $fechaOrigen);

        if ($maquinaId) {
            $query->where('maquina_id', $maquinaId);
        }

        $asignaciones = $query->get();

        if ($asignaciones->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay asignaciones en el día origen para copiar.',
            ]);
        }

        // Colores por obra
        $coloresEventos = [
            1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'],
            2 => ['bg' => '#6EE7B7', 'border' => '#34D399'],
            3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'],
        ];

        $copiadas = 0;
        $eventosCreados = [];

        foreach ($asignaciones as $asignacion) {
            // Verificar si ya existe para evitar duplicados
            $existe = AsignacionTurno::where('user_id', $asignacion->user_id)
                ->whereDate('fecha', $fechaDestino)
                ->exists();

            if ($existe) {
                continue;
            }

            // Eliminar cualquier soft-deleted para evitar conflicto de unique
            AsignacionTurno::onlyTrashed()
                ->where('user_id', $asignacion->user_id)
                ->whereDate('fecha', $fechaDestino)
                ->forceDelete();

            $nuevaAsignacion = AsignacionTurno::create([
                'user_id' => $asignacion->user_id,
                'fecha' => $fechaDestino,
                'turno_id' => $asignacion->turno_id,
                'maquina_id' => $asignacion->maquina_id,
                'obra_id' => $asignacion->obra_id,
                'estado' => 'activo',
            ]);

            $copiadas++;

            // Construir evento para el frontend usando TurnoMapper
            $turnoModel = $asignacion->turno;
            $slot = TurnoMapper::getSlotParaTurnoModel($turnoModel, $fechaDestino);
            $slotStart = $slot['start'];
            $slotEnd = $slot['end'];

            $obraId = $asignacion->obra_id;
            $colorEvento = $coloresEventos[$obraId] ?? ['bg' => '#d1d5db', 'border' => '#9ca3af'];

            $eventosCreados[] = [
                'id' => 'turno-' . $nuevaAsignacion->id,
                'title' => $asignacion->user->nombre_completo ?? $asignacion->user->name,
                'start' => $slotStart,
                'end' => $slotEnd,
                'resourceId' => $asignacion->maquina_id,
                'backgroundColor' => $colorEvento['bg'],
                'borderColor' => $colorEvento['border'],
                'textColor' => '#000000',
                'extendedProps' => [
                    'user_id' => $asignacion->user_id,
                    'turno' => $turnoModel->nombre ?? null,
                    'categoria_nombre' => $asignacion->user->categoria->nombre ?? null,
                    'entrada' => null,
                    'salida' => null,
                    'foto' => $asignacion->user->ruta_imagen ?? null,
                    'es_festivo' => false,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Se copiaron {$copiadas} asignaciones correctamente.",
            'copiadas' => $copiadas,
            'eventos' => $eventosCreados,
        ]);
    }

    /**
     * Verifica si hay conflictos entre obra externa y taller (Paco Reyes)
     * para un trabajador en un rango de fechas
     */
    public function verificarConflictosObraTaller(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
            'destino' => 'required|in:taller,obra', // hacia dónde va la asignación
        ]);

        $userId = $request->user_id;
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : $fechaInicio;
        $destino = $request->destino;

        // Obtener IDs de obras de Hierros Paco Reyes (taller)
        $obrasPacoReyes = Obra::getNavesPacoReyes()->pluck('id')->toArray();

        // Buscar asignaciones del trabajador en el rango de fechas
        $asignaciones = AsignacionTurno::where('user_id', $userId)
            ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->whereNotNull('obra_id')
            ->with('obra:id,nombre')
            ->get();

        $conflictos = [];

        foreach ($asignaciones as $asig) {
            $esEnTaller = in_array($asig->obra_id, $obrasPacoReyes);

            // Si va hacia taller y tiene asignaciones en obra externa
            if ($destino === 'taller' && !$esEnTaller) {
                $conflictos[] = [
                    'fecha' => Carbon::parse($asig->fecha)->format('Y-m-d'),
                    'fecha_formateada' => Carbon::parse($asig->fecha)->locale('es')->isoFormat('ddd D MMM'),
                    'obra' => $asig->obra?->nombre ?? 'Obra externa',
                    'tipo' => 'obra_externa',
                ];
            }

            // Si va hacia obra y tiene asignaciones en taller
            if ($destino === 'obra' && $esEnTaller) {
                $conflictos[] = [
                    'fecha' => Carbon::parse($asig->fecha)->format('Y-m-d'),
                    'fecha_formateada' => Carbon::parse($asig->fecha)->locale('es')->isoFormat('ddd D MMM'),
                    'obra' => $asig->obra?->nombre ?? 'Taller',
                    'tipo' => 'taller',
                ];
            }
        }

        // Agrupar por tipo para mejor presentación
        $diasEnObra = collect($conflictos)->where('tipo', 'obra_externa')->pluck('fecha_formateada')->unique()->values()->toArray();
        $diasEnTaller = collect($conflictos)->where('tipo', 'taller')->pluck('fecha_formateada')->unique()->values()->toArray();

        return response()->json([
            'tiene_conflictos' => count($conflictos) > 0,
            'conflictos' => $conflictos,
            'dias_en_obra' => $diasEnObra,
            'dias_en_taller' => $diasEnTaller,
            'resumen' => [
                'total_obra' => count($diasEnObra),
                'total_taller' => count($diasEnTaller),
            ],
        ]);
    }

    /**
     * Propaga las asignaciones de un día a múltiples días siguientes
     * Salta fines de semana y festivos automáticamente
     */
    public function propagarDia(Request $request)
    {
        $request->validate([
            'fecha_origen' => 'required|date',
            'alcance' => 'required|in:semana_actual,dos_semanas',
            'maquina_id' => 'nullable',
        ]);

        $fechaOrigen = Carbon::parse($request->fecha_origen);
        $maquinaId = $request->maquina_id;
        $alcance = $request->alcance;

        // Calcular fecha fin según alcance
        // dayOfWeek: 0=domingo, 1=lunes, ..., 5=viernes, 6=sábado
        $diaSemana = $fechaOrigen->dayOfWeek;

        // Calcular días hasta el viernes de esta semana
        // Si es domingo (0), el viernes es en 5 días
        // Si es lunes (1), el viernes es en 4 días
        // Si es viernes (5), es hoy mismo
        // Si es sábado (6), el viernes pasó, ir al siguiente
        $diasHastaViernes = $diaSemana === 0 ? 5 : (5 - $diaSemana);
        if ($diasHastaViernes <= 0) {
            $diasHastaViernes += 7; // Ir al viernes de la siguiente semana
        }

        if ($alcance === 'semana_actual') {
            $fechaFin = $fechaOrigen->copy()->addDays($diasHastaViernes);
        } else {
            // Dos semanas: viernes de la semana siguiente
            $fechaFin = $fechaOrigen->copy()->addDays($diasHastaViernes + 7);
        }

        Log::channel('planificacion_trabajadores_taller')->info('[propagarDia] Parámetros:', [
            'fecha_origen' => $fechaOrigen->toDateString(),
            'fecha_fin' => $fechaFin->toDateString(),
            'alcance' => $alcance,
            'maquina_id' => $maquinaId,
        ]);

        // Si la fecha origen es posterior o igual a la fecha fin, error
        if ($fechaOrigen->greaterThanOrEqualTo($fechaFin)) {
            return response()->json([
                'success' => false,
                'message' => "La fecha de origen ({$fechaOrigen->toDateString()}) debe ser anterior al viernes ({$fechaFin->toDateString()}).",
            ]);
        }

        // Obtener asignaciones del día origen
        $query = AsignacionTurno::with(['user.categoria', 'turno', 'obra'])
            ->whereDate('fecha', $fechaOrigen->toDateString());

        // Solo filtrar por máquina si se especifica una válida
        if ($maquinaId && is_numeric($maquinaId)) {
            $query->where('maquina_id', $maquinaId);
        }

        $asignaciones = $query->get();

        Log::channel('planificacion_trabajadores_taller')->info('[propagarDia] Asignaciones encontradas:', [
            'count' => $asignaciones->count(),
            'ids' => $asignaciones->pluck('id')->toArray(),
        ]);

        if ($asignaciones->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay asignaciones en el día origen para propagar.',
            ]);
        }

        // Obtener festivos en el rango
        $festivos = Festivo::whereBetween('fecha', [
            $fechaOrigen->toDateString(),
            $fechaFin->toDateString()
        ])->pluck('fecha')->map(fn($f) => Carbon::parse($f)->toDateString())->toArray();

        // Obtener vacaciones de los usuarios involucrados
        $userIds = $asignaciones->pluck('user_id')->unique()->toArray();
        $vacaciones = VacacionesSolicitud::whereIn('user_id', $userIds)
            ->where('estado', 'aprobada')
            ->where(function ($q) use ($fechaOrigen, $fechaFin) {
                $q->whereBetween('fecha_inicio', [$fechaOrigen, $fechaFin])
                  ->orWhereBetween('fecha_fin', [$fechaOrigen, $fechaFin])
                  ->orWhere(function ($q2) use ($fechaOrigen, $fechaFin) {
                      $q2->where('fecha_inicio', '<=', $fechaOrigen)
                         ->where('fecha_fin', '>=', $fechaFin);
                  });
            })
            ->get();

        // Crear mapa de vacaciones por usuario y fecha
        $vacacionesPorUsuario = [];
        foreach ($vacaciones as $v) {
            $periodo = CarbonPeriod::create($v->fecha_inicio, $v->fecha_fin);
            foreach ($periodo as $dia) {
                $vacacionesPorUsuario[$v->user_id][$dia->toDateString()] = true;
            }
        }

        // Colores por obra
        $coloresEventos = [
            1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'],
            2 => ['bg' => '#6EE7B7', 'border' => '#34D399'],
            3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'],
        ];

        $copiadas = 0;
        $eliminadas = 0;
        $eventosCreados = [];
        $eventosEliminados = [];
        $diasProcesados = 0;

        // IDs de usuarios que tienen asignación en el día origen (para modo espejo)
        $userIdsEnOrigen = $asignaciones->pluck('user_id')->unique()->toArray();

        // Iterar desde el día siguiente al origen hasta el fin
        $fechaActual = $fechaOrigen->copy()->addDay();

        while ($fechaActual->lessThanOrEqualTo($fechaFin)) {
            $fechaStr = $fechaActual->toDateString();

            // Saltar fines de semana
            if ($fechaActual->isWeekend()) {
                $fechaActual->addDay();
                continue;
            }

            // Saltar festivos
            if (in_array($fechaStr, $festivos)) {
                $fechaActual->addDay();
                continue;
            }

            $diasProcesados++;

            // === MODO ESPEJO: Quitar maquina_id a usuarios que NO están en el día origen ===
            $queryEspejo = AsignacionTurno::whereDate('fecha', $fechaStr)
                ->whereNotIn('user_id', $userIdsEnOrigen);

            // Si filtramos por máquina, solo afectar asignaciones de esa máquina
            if ($maquinaId && is_numeric($maquinaId)) {
                $queryEspejo->where('maquina_id', $maquinaId);
            } else {
                // Si es "todas las máquinas", solo afectar las que tienen maquina_id
                $queryEspejo->whereNotNull('maquina_id');
            }

            $asignacionesAQuitar = $queryEspejo->get();

            foreach ($asignacionesAQuitar as $asignacionQuitar) {
                // Guardar ID para notificar al frontend
                $eventosEliminados[] = 'turno-' . $asignacionQuitar->id;

                // Quitar el maquina_id (el trabajador ya no está asignado a esa máquina)
                $asignacionQuitar->update(['maquina_id' => null]);
                $eliminadas++;
            }

            // === Propagar asignaciones del día origen ===
            foreach ($asignaciones as $asignacion) {
                // Verificar si el usuario tiene vacaciones este día
                if (isset($vacacionesPorUsuario[$asignacion->user_id][$fechaStr])) {
                    continue;
                }

                // Buscar asignación existente (incluyendo soft-deleted)
                $asignacionExistente = AsignacionTurno::withTrashed()
                    ->where('user_id', $asignacion->user_id)
                    ->whereDate('fecha', $fechaStr)
                    ->first();

                if ($asignacionExistente) {
                    // Si está soft-deleted, restaurarla
                    if ($asignacionExistente->trashed()) {
                        $asignacionExistente->restore();
                    }
                    // Actualizar con los datos del día origen
                    $asignacionExistente->update([
                        'turno_id' => $asignacion->turno_id,
                        'maquina_id' => $asignacion->maquina_id,
                        'obra_id' => $asignacion->obra_id,
                        'estado' => 'activo',
                    ]);
                    $nuevaAsignacion = $asignacionExistente;
                } else {
                    // Crear nueva asignación
                    $nuevaAsignacion = AsignacionTurno::create([
                        'user_id' => $asignacion->user_id,
                        'fecha' => $fechaStr,
                        'turno_id' => $asignacion->turno_id,
                        'maquina_id' => $asignacion->maquina_id,
                        'obra_id' => $asignacion->obra_id,
                        'estado' => 'activo',
                    ]);
                }

                $copiadas++;

                // Construir evento para el frontend
                $turnoModel = $asignacion->turno;
                $slot = TurnoMapper::getSlotParaTurnoModel($turnoModel, $fechaStr);
                $slotStart = $slot['start'];
                $slotEnd = $slot['end'];

                $obraId = $asignacion->obra_id;
                $colorEvento = $coloresEventos[$obraId] ?? ['bg' => '#d1d5db', 'border' => '#9ca3af'];

                $eventosCreados[] = [
                    'id' => 'turno-' . $nuevaAsignacion->id,
                    'title' => $asignacion->user->nombre_completo ?? $asignacion->user->name,
                    'start' => $slotStart,
                    'end' => $slotEnd,
                    'resourceId' => $asignacion->maquina_id,
                    'backgroundColor' => $colorEvento['bg'],
                    'borderColor' => $colorEvento['border'],
                    'textColor' => '#000000',
                    'extendedProps' => [
                        'user_id' => $asignacion->user_id,
                        'turno' => $turnoModel->nombre ?? null,
                        'categoria_nombre' => $asignacion->user->categoria->nombre ?? null,
                        'entrada' => null,
                        'salida' => null,
                        'foto' => $asignacion->user->ruta_imagen ?? null,
                        'es_festivo' => false,
                    ],
                ];
            }

            $fechaActual->addDay();
        }

        $alcanceTexto = $alcance === 'semana_actual' ? 'esta semana' : 'las próximas 2 semanas';

        return response()->json([
            'success' => true,
            'message' => "Se propagaron {$copiadas} asignaciones y se quitaron {$eliminadas} de máquinas ({$alcanceTexto}).",
            'copiadas' => $copiadas,
            'eliminadas' => $eliminadas,
            'dias_procesados' => $diasProcesados,
            'eventos' => $eventosCreados,
            'eventos_eliminados' => $eventosEliminados,
        ]);
    }

    public function quitarObra($id)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[quitarObra] Quitando obra de asignación', [
            'asignacion_id' => $id,
            'ejecutado_por' => auth()->id(),
        ]);

        $asignacion = AsignacionTurno::find($id);

        if (!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => '❌ Asignación no encontrada.'
            ]);
        }

        $asignacion->delete();

        return response()->json([
            'success' => true,
            'message' => '🗑️ Asignación eliminada correctamente.'
        ]);
    }

    /**
     * Mueve múltiples asignaciones a otra obra manteniendo las fechas
     */
    public function moverEventosAObra(Request $request)
    {
        Log::channel('planificacion_trabajadores_taller')->info('[moverEventosAObra] Moviendo eventos a otra obra', [
            'asignacion_ids' => $request->asignacion_ids,
            'obra_id' => $request->obra_id,
            'ejecutado_por' => auth()->id(),
        ]);

        try {
            // Procesar IDs primero (quitar prefijo 'turno-')
            $ids = collect($request->asignacion_ids)->map(function ($id) {
                return (int) str_replace('turno-', '', $id);
            })->filter()->values()->toArray();

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionaron IDs válidos.'
                ], 400);
            }

            // Validar obra_id (puede venir como string)
            $obraId = $request->obra_id;
            if ($obraId === 'sin-obra' || $obraId === '' || $obraId === null) {
                $obraId = null;
            } else {
                $obraId = (int) $obraId;
                // Verificar que la obra existe
                if (!Obra::where('id', $obraId)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La obra seleccionada no existe.'
                    ], 400);
                }
            }

            $actualizados = AsignacionTurno::whereIn('id', $ids)->update([
                'obra_id' => $obraId
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se movieron {$actualizados} asignaciones correctamente.",
                'actualizados' => $actualizados
            ]);
        } catch (\Exception $e) {
            \Log::error('Error moviendo eventos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al mover eventos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        // 💡 Usa la misma query base que index()
        $query = AsignacionTurno::with(['user', 'turno', 'obra'])
            ->whereDate('fecha', '<=', Carbon::tomorrow())
            ->where('estado', 'activo')
            ->whereHas('turno', fn($q) => $q->where('nombre', '!=', 'vacaciones'))
            ->join('turnos', 'asignaciones_turnos.turno_id', '=', 'turnos.id')
            ->orderBy('fecha', 'desc')
            ->orderByRaw("FIELD(turnos.nombre, 'mañana', 'tarde', 'noche')")
            ->orderBy('asignaciones_turnos.id')
            ->select('asignaciones_turnos.*');

        // 🔍 aplica los mismos filtros
        $query = $this->aplicarFiltros($query, $request);

        // 🔀 aplica el mismo orden dinámico
        $query = $this->aplicarOrdenamiento($query, $request);

        // 🔥 ejecuta la query
        $asignaciones = $query->get();

        return Excel::download(
            new AsignacionesTurnosExport($asignaciones),
            'Registros entrada y salida.xlsx'
        );
    }
}
