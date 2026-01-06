<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$schedule = app(Schedule::class);

// =====================================================================
// TAREAS ANUALES (1 de enero)
// =====================================================================

// 1. Sincronizar festivos del año nuevo (primero, para que los turnos los respeten)
$schedule->command('festivos:sincronizar')
    ->yearlyOn(1, 1, '00:05')
    ->timezone('Europe/Madrid');

// 2. Generar turnos para el nuevo año (después de tener los festivos)
$schedule->command('turnos:generar-anuales')
    ->yearlyOn(1, 1, '00:15')
    ->timezone('Europe/Madrid');
