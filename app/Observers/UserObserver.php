<?php
namespace App\Observers;

use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use Carbon\Carbon;

class UserObserver
{
    public function created(User $user)
    {
        \Log::info("Observer ejecutado para el usuario: {$user->id}");
        $this->generarTurnos($user);
   
    }

    public function generarTurnos($user)
    {
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        $inicio = Carbon::now()->startOfYear();
        $fin = Carbon::now()->endOfYear();
        
        if ($user->turno == 'diurno') {
            $turnoAsignado = ($user->turno_actual == 1) ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } else {
            return; // No asignar turno si no es diurno o nocturno
        }
        
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            if (in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            AsignacionTurno::firstOrCreate(
                ['user_id' => $user->id, 'fecha' => $fecha->toDateString()],
                ['turno_id' => $turnoAsignado, 'asignacion_manual' => false, 'modificado' => false]
            );

            if ($user->turno == 'diurno' && $fecha->dayOfWeek == Carbon::FRIDAY) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }
    }
}
