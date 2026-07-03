<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\AsignacionTurno;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarFichajesEntrada extends Command
{
    protected $signature = 'fichajes:verificar-entradas {--turno= : Nombre del turno a verificar (mañana, tarde, noche)}';

    protected $description = 'Verifica quién no ha fichado entrada y envía alertas a programación';

    public function handle()
    {
        $turnoNombre = $this->option('turno');
        $ahora = now();
        $hoy = $ahora->toDateString();

        // Si se especifica un turno, verificar solo ese
        if ($turnoNombre) {
            $turnos = Turno::where('nombre', $turnoNombre)
                ->whereNotNull('hora_inicio')
                ->where('activo', true)
                ->get();
        } else {
            // Obtener turnos con horarios definidos
            $turnos = Turno::whereNotNull('hora_inicio')
                ->whereNotNull('hora_fin')
                ->where('activo', true)
                ->get();
        }

        if ($turnos->isEmpty()) {
            $this->warn('No hay turnos configurados para verificar.');
            return 0;
        }

        $margenMinutos = 30;
        $trabajadoresSinFichar = collect();

        foreach ($turnos as $turno) {
            // Calcular la hora límite (hora_inicio + margen)
            $horaInicio = Carbon::createFromFormat('H:i:s', $turno->hora_inicio);
            $horaLimite = $horaInicio->copy()->addMinutes($margenMinutos);

            // Determinar la fecha de asignación según el turno
            $fechaAsignacion = $hoy;

            // Para turno noche con offset, la asignación es del día siguiente
            // Si estamos verificando a las 22:30, la asignación es de mañana
            if ($turno->nombre === 'noche' && $turno->offset_dias_inicio == -1) {
                // Si la hora actual es antes de medianoche, la asignación es de mañana
                if ($ahora->format('H:i:s') >= $turno->hora_inicio) {
                    $fechaAsignacion = $ahora->copy()->addDay()->toDateString();
                }
            }

            // Solo verificar si estamos en la ventana correcta (hora_inicio hasta hora_inicio + margen + 5min)
            $horaActual = $ahora->format('H:i:s');
            $dentroVentana = $horaActual >= $horaInicio->format('H:i:s')
                          && $horaActual <= $horaLimite->copy()->addMinutes(5)->format('H:i:s');

            // Si se especificó un turno manualmente, ignorar la ventana de tiempo
            if (!$turnoNombre && !$dentroVentana) {
                $this->line("Turno {$turno->nombre}: fuera de ventana de verificación.");
                continue;
            }

            $this->info("Verificando turno: {$turno->nombre} para fecha {$fechaAsignacion}");

            // Buscar asignaciones del turno sin entrada fichada
            $sinFichar = AsignacionTurno::where('turno_id', $turno->id)
                ->whereDate('fecha', $fechaAsignacion)
                ->where('estado', 'activo')
                ->whereNull('entrada')
                ->with('user')
                ->get();

            foreach ($sinFichar as $asignacion) {
                if ($asignacion->user) {
                    $trabajadoresSinFichar->push([
                        'usuario' => $asignacion->user,
                        'turno' => $turno->nombre,
                        'fecha' => $fechaAsignacion,
                    ]);
                    $this->warn("  - {$asignacion->user->nombre_completo} no ha fichado entrada");
                }
            }
        }

        // Enviar alerta si hay trabajadores sin fichar
        if ($trabajadoresSinFichar->isNotEmpty()) {
            $this->enviarAlertaProgramacion($trabajadoresSinFichar);
            $this->info("Alerta enviada a programación con {$trabajadoresSinFichar->count()} trabajadores sin fichar.");
        } else {
            $this->info("Todos los trabajadores han fichado correctamente.");
        }

        return 0;
    }

    private function enviarAlertaProgramacion($trabajadoresSinFichar)
    {
        // Agrupar por turno para el mensaje
        $porTurno = $trabajadoresSinFichar->groupBy('turno');

        $mensajeLineas = ["⚠️ Trabajadores sin fichar entrada:"];

        foreach ($porTurno as $turno => $trabajadores) {
            $mensajeLineas[] = "";
            $mensajeLineas[] = "Turno {$turno}:";
            foreach ($trabajadores as $t) {
                $mensajeLineas[] = "  • {$t['usuario']->nombre_completo}";
            }
        }

        $mensaje = implode("\n", $mensajeLineas);

        // Crear la alerta
        $alerta = Alerta::create([
            'mensaje' => $mensaje,
            'tipo' => 'Fichajes',
            'leida' => false,
        ]);

        // Obtener usuarios del departamento de Programador y Administrador
        $destinatarios = User::whereHas('departamentos', function ($q) {
            $q->whereRaw('LOWER(nombre) IN (?, ?)', ['programador', 'administrador']);
        })->get();

        // Crear registros de alerta para cada destinatario
        foreach ($destinatarios as $destinatario) {
            AlertaLeida::firstOrCreate([
                'alerta_id' => $alerta->id,
                'user_id' => $destinatario->id,
            ]);
        }

        Log::info("Alerta de fichajes enviada", [
            'trabajadores_sin_fichar' => $trabajadoresSinFichar->count(),
            'destinatarios_notificados' => $destinatarios->count(),
        ]);
    }
}
