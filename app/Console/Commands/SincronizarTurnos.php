<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Turno;
use App\Models\AsignacionTurno;
use App\Models\Festivo;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SincronizarTurnos extends Command
{
    protected $signature = 'turnos:sincronizar
        {--dry-run : Muestra qué haría sin ejecutar cambios}
        {--desde= : Fecha inicio (YYYY-MM-DD, default: mañana)}
        {--hasta= : Fecha fin (YYYY-MM-DD, default: fin de año)}';

    protected $description = 'Sincroniza turnos desde el xlsx de trabajadores (columna D=DNI, L=turno_id)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $inicio = $this->option('desde')
            ? Carbon::parse($this->option('desde'))->startOfDay()
            : Carbon::now()->addDay()->startOfDay();
        $hasta = $this->option('hasta')
            ? Carbon::parse($this->option('hasta'))->endOfDay()
            : Carbon::now()->endOfYear();

        if ($dryRun) {
            $this->warn('*** DRY RUN — no se realizarán cambios ***');
        }

        $this->info("Rango: {$inicio->toDateString()} → {$hasta->toDateString()}");

        // 1. Leer xlsx
        $archivoXlsx = base_path('base datos trabajadores.xlsx');
        if (!file_exists($archivoXlsx)) {
            $this->error("No se encontró el archivo: {$archivoXlsx}");
            return 1;
        }

        $spreadsheet = IOFactory::load($archivoXlsx);
        $sheet = $spreadsheet->getActiveSheet();

        // Extraer pares DNI → turno_id desde el xlsx
        $trabajadoresXlsx = [];
        foreach ($sheet->getRowIterator(2) as $row) { // Empezar en fila 2 (saltar cabecera)
            $cellIterator = $row->getCellIterator('A', 'L');
            $cellIterator->setIterateOnlyExistingCells(false);

            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[$cell->getColumn()] = $cell->getValue();
            }

            $dni = trim($cells['D'] ?? '');
            $turnoId = $cells['L'] ?? null;

            if ($dni === '' || $turnoId === null || $turnoId === '') {
                continue;
            }

            $turnoId = (int) $turnoId;
            if ($turnoId <= 0) {
                continue;
            }

            $trabajadoresXlsx[] = [
                'dni' => strtoupper($dni),
                'turno_id' => $turnoId,
            ];
        }

        $this->info("Trabajadores leídos del xlsx con turno asignado: " . count($trabajadoresXlsx));

        if (empty($trabajadoresXlsx)) {
            $this->warn("No hay trabajadores con turno_id en la columna L. Nada que hacer.");
            return 0;
        }

        // 2. Verificar que los turno_id existen en BD
        $turnosValidos = Turno::pluck('id')->toArray();
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id');

        // 3. Matchear con usuarios por DNI
        $usuarios = User::withoutGlobalScopes()->get()->keyBy(function ($u) {
            return strtoupper(trim($u->dni));
        });

        $festivos = $this->getFestivosEntre($inicio, $hasta);

        $matched = 0;
        $notFound = 0;
        $turnoInvalido = 0;
        $asignacionesCreadas = 0;
        $eliminadas = 0;

        foreach ($trabajadoresXlsx as $t) {
            $user = $usuarios->get($t['dni']);

            if (!$user) {
                $this->warn("  DNI {$t['dni']}: no encontrado en BD");
                $notFound++;
                continue;
            }

            if (!in_array($t['turno_id'], $turnosValidos)) {
                $this->warn("  DNI {$t['dni']} ({$user->name}): turno_id={$t['turno_id']} no existe en BD");
                $turnoInvalido++;
                continue;
            }

            $matched++;

            // Días de vacaciones del usuario
            $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
                ->where('turno_id', $turnoVacacionesId)
                ->whereDate('fecha', '>=', $inicio->toDateString())
                ->whereDate('fecha', '<=', $hasta->toDateString())
                ->pluck('fecha')
                ->map(fn($f) => Carbon::parse($f)->toDateString())
                ->toArray();

            if (!$dryRun) {
                // Limpiar asignaciones previas (excepto vacaciones) para este usuario
                $del = AsignacionTurno::withTrashed()
                    ->where('user_id', $user->id)
                    ->whereDate('fecha', '>=', $inicio->toDateString())
                    ->whereDate('fecha', '<=', $hasta->toDateString())
                    ->where(function ($query) use ($turnoVacacionesId) {
                        $query->where('turno_id', '!=', $turnoVacacionesId)
                              ->orWhereNull('turno_id');
                    })
                    ->forceDelete();
                $eliminadas += $del;
            }

            // Generar asignaciones día a día
            $asignacionesUsuario = 0;
            for ($fecha = $inicio->copy(); $fecha->lte($hasta); $fecha->addDay()) {
                $fechaStr = $fecha->toDateString();

                // Saltar fines de semana, festivos y vacaciones
                if ($fecha->dayOfWeek === Carbon::SATURDAY
                    || $fecha->dayOfWeek === Carbon::SUNDAY
                    || in_array($fechaStr, $festivos, true)
                    || in_array($fechaStr, $diasVacaciones, true)
                ) {
                    continue;
                }

                if (!$dryRun) {
                    AsignacionTurno::updateOrCreate(
                        ['user_id' => $user->id, 'fecha' => $fechaStr],
                        ['turno_id' => $t['turno_id']]
                    );
                }

                $asignacionesUsuario++;
            }

            $turnoNombre = Turno::find($t['turno_id'])->nombre ?? $t['turno_id'];
            $this->line("  {$user->name} {$user->apellidos} (DNI: {$t['dni']}) → turno: {$turnoNombre} — {$asignacionesUsuario} días");
            $asignacionesCreadas += $asignacionesUsuario;
        }

        // Resumen
        $this->newLine();
        $this->info("=== RESUMEN ===");
        $this->info("Matched:              {$matched}");
        if ($notFound > 0) $this->warn("No encontrados en BD:  {$notFound}");
        if ($turnoInvalido > 0) $this->warn("Turno ID inválido:    {$turnoInvalido}");
        if (!$dryRun) {
            $this->info("Asignaciones previas eliminadas: {$eliminadas}");
        }
        $this->info("Asignaciones " . ($dryRun ? '(pendientes)' : 'creadas') . ": {$asignacionesCreadas}");

        if ($dryRun) {
            $this->newLine();
            $this->warn("Ejecuta sin --dry-run para aplicar los cambios.");
        }

        return 0;
    }

    protected function getFestivosEntre(Carbon $inicio, Carbon $fin): array
    {
        return Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->toArray();
    }
}
