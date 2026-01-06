<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FestivoService;

class SincronizarFestivosCommand extends Command
{
    protected $signature = 'festivos:sincronizar {anio?} {--region=ES-AN}';
    protected $description = 'Sincroniza los festivos del año en la tabla festivos';

    public function handle(FestivoService $servicio): int
    {
        $anio = $this->argument('anio') ? (int) $this->argument('anio') : (int) date('Y');
        $region = (string) $this->option('region');

        $n = $servicio->sincronizarFestivos($anio, $region);
        $this->info("Festivos sincronizados para {$anio} ({$region}). Filas procesadas: {$n}");

        return self::SUCCESS;
    }
}
