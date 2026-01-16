<?php

namespace App\Http\Controllers;

use App\Models\RevisionFichajeSolicitud;
use App\Models\AsignacionTurno;
use App\Models\User;
use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RevisionFichajeController extends Controller
{
    /**
     * Crear solicitud de revision de fichajes
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);

        // Crear la solicitud
        $solicitud = RevisionFichajeSolicitud::create([
            'user_id' => $user->id,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => 'pendiente',
            'observaciones' => $request->observaciones,
        ]);

        // Obtener detalles de fichajes para el mensaje
        $detallesFichajes = $this->obtenerDetallesFichajes($user->id, $fechaInicio, $fechaFin);

        // Construir mensaje de alerta
        $mensaje = $this->construirMensajeAlerta($user, $solicitud, $detallesFichajes);

        // Enviar alerta a todos los programadores
        $this->enviarAlertaProgramadores($user, $solicitud, $mensaje);

        return response()->json([
            'success' => 'Solicitud de revision enviada correctamente.',
            'solicitud_id' => $solicitud->id,
        ], 201);
    }

    /**
     * Auto-rellenar fichajes segun el turno asignado
     */
    public function autoRellenar($id)
    {
        $solicitud = RevisionFichajeSolicitud::findOrFail($id);

        // Solo programadores pueden ejecutar esto
        if (!in_array(Auth::user()->rol, ['oficina', 'programador'])) {
            return response()->json(['error' => 'No tienes permisos para esta accion.'], 403);
        }

        if ($solicitud->estado !== 'pendiente') {
            return response()->json(['error' => 'Esta solicitud ya fue procesada.'], 400);
        }

        $fechaInicio = Carbon::parse($solicitud->fecha_inicio);
        $fechaFin = Carbon::parse($solicitud->fecha_fin);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        $corregidos = 0;
        $errores = [];

        foreach ($periodo as $fecha) {
            $fechaStr = $fecha->toDateString();

            $asignacion = AsignacionTurno::where('user_id', $solicitud->user_id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            if (!$asignacion) {
                continue;
            }

            // Obtener horas del turno
            $turno = $asignacion->turno;
            if (!$turno) {
                $errores[] = "{$fechaStr}: Sin turno asignado";
                continue;
            }

            // Usar hora_inicio/hora_fin o hora_entrada/hora_salida segun lo que este disponible
            $horaEntradaTurno = $turno->hora_entrada ?? $turno->hora_inicio;
            $horaSalidaTurno = $turno->hora_salida ?? $turno->hora_fin;

            $horasActualizadas = false;

            // Rellenar entrada si falta
            if (empty($asignacion->entrada) && $horaEntradaTurno) {
                $asignacion->entrada = $horaEntradaTurno;
                $horasActualizadas = true;
            }

            // Rellenar salida si falta
            if (empty($asignacion->salida) && $horaSalidaTurno) {
                $asignacion->salida = $horaSalidaTurno;
                $horasActualizadas = true;
            }

            if ($horasActualizadas) {
                // Marcar como revisado
                $asignacion->revisado_at = now();
                $asignacion->revisado_por = Auth::id();
                $asignacion->save();
                $corregidos++;
            }
        }

        // Marcar solicitud como resuelta
        $solicitud->update([
            'estado' => 'resuelta',
            'resuelta_por' => Auth::id(),
            'resuelta_en' => now(),
        ]);

        // Notificar al usuario que su solicitud fue procesada
        $this->notificarUsuarioResolucion($solicitud, $corregidos);

        return response()->json([
            'success' => "Fichajes auto-rellenados correctamente. {$corregidos} dia(s) corregido(s).",
            'corregidos' => $corregidos,
            'errores' => $errores,
        ]);
    }

    /**
     * Obtener detalles de fichajes para un rango de fechas
     */
    private function obtenerDetallesFichajes($userId, $fechaInicio, $fechaFin)
    {
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        $detalles = [];

        foreach ($periodo as $fecha) {
            $fechaStr = $fecha->toDateString();

            // Saltar fines de semana
            if ($fecha->isWeekend()) {
                continue;
            }

            $asignacion = AsignacionTurno::with('turno')
                ->where('user_id', $userId)
                ->whereDate('fecha', $fechaStr)
                ->first();

            $turnoNombre = $asignacion?->turno?->nombre ?? 'Sin turno';
            $entrada = $asignacion?->entrada ? substr($asignacion->entrada, 0, 5) : null;
            $salida = $asignacion?->salida ? substr($asignacion->salida, 0, 5) : null;
            $entrada2 = $asignacion?->entrada2 ? substr($asignacion->entrada2, 0, 5) : null;
            $salida2 = $asignacion?->salida2 ? substr($asignacion->salida2, 0, 5) : null;

            // Determinar si hay fichajes incompletos
            $completo = true;
            if (!$asignacion) {
                $completo = false;
            } elseif (!$entrada || !$salida) {
                $completo = false;
            }

            $detalles[] = [
                'fecha' => $fechaStr,
                'turno' => $turnoNombre,
                'entrada' => $entrada,
                'salida' => $salida,
                'entrada2' => $entrada2,
                'salida2' => $salida2,
                'completo' => $completo,
            ];
        }

        return $detalles;
    }

    /**
     * Construir mensaje de alerta con formato
     */
    private function construirMensajeAlerta($user, $solicitud, $detalles)
    {
        $fechaInicioStr = $solicitud->fecha_inicio->format('d/m/Y');
        $fechaFinStr = $solicitud->fecha_fin->format('d/m/Y');
        $rangoFechas = $fechaInicioStr === $fechaFinStr
            ? $fechaInicioStr
            : "{$fechaInicioStr} - {$fechaFinStr}";

        // Marcador especial para identificar la solicitud (se usara para el boton)
        $mensaje = "[REVISION_ID:{$solicitud->id}][USER_ID:{$solicitud->user_id}]\n";
        $mensaje .= "SOLICITUD DE REVISION DE FICHAJES\n\n";
        $mensaje .= "Usuario: {$user->nombre_completo} (ID: {$user->id})\n";
        $mensaje .= "Fechas: {$rangoFechas}\n\n";
        $mensaje .= "Estado de fichajes:\n";

        foreach ($detalles as $d) {
            $icono = $d['completo'] ? '[OK]' : '[X]';
            $fechaFormateada = Carbon::parse($d['fecha'])->format('d/m');
            $turno = $d['turno'];

            $horasStr = '';
            if ($d['entrada'] || $d['salida']) {
                $e = $d['entrada'] ?? '-';
                $s = $d['salida'] ?? '-';
                $horasStr = "E: {$e} | S: {$s}";

                if ($d['entrada2'] || $d['salida2']) {
                    $e2 = $d['entrada2'] ?? '-';
                    $s2 = $d['salida2'] ?? '-';
                    $horasStr .= " | E2: {$e2} | S2: {$s2}";
                }
            } else {
                $horasStr = 'Sin fichajes';
            }

            $mensaje .= "{$icono} {$fechaFormateada} ({$turno}): {$horasStr}\n";
        }

        if ($solicitud->observaciones) {
            $mensaje .= "\nObservaciones: {$solicitud->observaciones}\n";
        }

        return $mensaje;
    }

    /**
     * Enviar alerta a todos los usuarios del departamento Programador
     */
    private function enviarAlertaProgramadores($user, $solicitud, $mensaje)
    {
        $programadores = User::whereHas('departamentos', function ($q) {
            $q->where('nombre', 'Programador');
        })->get();

        if ($programadores->isEmpty()) {
            Log::warning('No se encontraron usuarios en el departamento Programador para enviar alerta de revision.');
            return;
        }

        // Crear una sola alerta
        $alerta = Alerta::create([
            'user_id_1' => $user->id,
            'user_id_2' => null,
            'destino' => 'programador',
            'destinatario' => 'Programador',
            'destinatario_id' => $programadores->first()->id,
            'mensaje' => $mensaje,
            'tipo' => 'revision_fichaje',
        ]);

        // Crear registro de lectura para cada programador
        foreach ($programadores as $programador) {
            AlertaLeida::create([
                'alerta_id' => $alerta->id,
                'user_id' => $programador->id,
                'leida_en' => null,
            ]);
        }

        Log::info("Alerta de revision de fichajes enviada a {$programadores->count()} programador(es).");
    }

    /**
     * Notificar al usuario que su solicitud fue procesada
     */
    private function notificarUsuarioResolucion($solicitud, $diasCorregidos)
    {
        $alertaService = app(AlertaService::class);

        $mensaje = "Tu solicitud de revision de fichajes ha sido procesada.\n\n";
        $mensaje .= "Fechas: {$solicitud->fecha_inicio->format('d/m/Y')} - {$solicitud->fecha_fin->format('d/m/Y')}\n";
        $mensaje .= "Dias corregidos: {$diasCorregidos}\n";
        $mensaje .= "Procesado por: " . Auth::user()->nombre_completo;

        $alertaService->crearAlerta(
            emisorId: Auth::id(),
            destinatarioId: $solicitud->user_id,
            mensaje: $mensaje,
            tipo: 'revision_fichaje'
        );
    }
}
