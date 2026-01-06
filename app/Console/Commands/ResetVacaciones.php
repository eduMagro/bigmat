<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ResetVacaciones extends Command
{
    /**
     * El nombre y la descripción del comando en Artisan.
     *
     * @var string
     */
    protected $signature = 'vacaciones:reset';
    protected $description = 'Restablece los días de vacaciones de todos los usuarios al inicio del año';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $totalDiasVacaciones = 28; // Días de vacaciones por año

        // Actualiza todos los usuarios con los días completos de vacaciones
        User::query()->update(['dias_vacaciones' => $totalDiasVacaciones]);

        $this->info("Se han restablecido las vacaciones de todos los usuarios a {$totalDiasVacaciones} días.");
    }
}
