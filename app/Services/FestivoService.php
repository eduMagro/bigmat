<?php

namespace App\Services;

use App\Models\Festivo;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FestivoService
{
    /**
     * Descarga y guarda/actualiza festivos del año dado (nacionales + ES-AN + locales).
     * Idempotente: lo puedes ejecutar varias veces.
     */
    public function sincronizarFestivos(?int $anio = null, string $codigoRegion = 'ES-AN'): int
    {
        $anio = $anio ?? (int) date('Y');

        $respuesta = Http::timeout(20)->retry(2, 100)
            ->get("https://date.nager.at/api/v3/PublicHolidays/{$anio}/ES");

        $festivosApi = collect();

        if ($respuesta->ok()) {
            $festivosApi = collect($respuesta->json())
                ->filter(function ($festivo) use ($codigoRegion) {
                    // Mantener nacionales (sin counties) o los de la región indicada
                    return !isset($festivo['counties']) || in_array($codigoRegion, $festivo['counties'], true);
                })
                ->map(function ($festivo) use ($anio) {
                    return [
                        'titulo' => $festivo['localName'] ?? $festivo['name'],
                        'fecha'  => Carbon::parse($festivo['date'])->toDateString(),
                        'anio'   => $anio,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });
        }

        // Añade aquí tus festivos locales (ajústalos a tu municipio)
        // NOTA: La Feria de Los Palacios tiene fecha variable cada año, añadir manualmente
        $festivosLocales = collect([
            [
                'titulo' => 'Festividad de Nuestra Señora de las Nieves',
                'fecha'  => "{$anio}-08-05",
                'anio'   => $anio,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $filas = $festivosApi->merge($festivosLocales)->values();

        // Con tu esquema, upsert por (anio, fecha) y actualiza el título si cambia
        Festivo::upsert(
            $filas->toArray(),
            ['anio', 'fecha'],
            ['titulo', 'updated_at']
        );

        return $filas->count();
    }

    /**
     * Devuelve los festivos del año en formato para FullCalendar.
     */
    public function obtenerFestivos(?int $anio = null): array
    {
        $anio = $anio ?? (int) date('Y');

        return Festivo::delAnio($anio)
            ->get(['titulo', 'fecha'])
            ->map(fn($f) => [
                'title'           => $f->titulo,
                'start'           => $f->fecha->toDateString(),
                'backgroundColor' => '#ff0000',
                'borderColor'     => '#b91c1c',
                'textColor'       => 'white',
                'allDay'          => true,
            ])
            ->values()
            ->toArray();
    }
}
