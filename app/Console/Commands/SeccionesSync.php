<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SeccionAutoDetectService;

class SeccionesSync extends Command
{
    protected $signature = 'secciones:sync
                            {--check : Solo muestra el estado sin crear secciones}
                            {--create : Crea las secciones faltantes automáticamente}';

    protected $description = 'Sincroniza las secciones con los prefijos de rutas del sistema';

    public function handle(SeccionAutoDetectService $service)
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║        SINCRONIZACIÓN DE SECCIONES Y RUTAS                   ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $comparacion = $service->compararConSecciones();
        $estadisticas = $service->obtenerEstadisticas();

        // Mostrar estadísticas
        $this->info("📊 ESTADÍSTICAS:");
        $this->info("   Total prefijos detectados: {$estadisticas['total_prefijos']}");
        $this->info("   Con sección asignada:      {$estadisticas['con_seccion']}");
        $this->warn("   Sin sección (faltantes):   {$estadisticas['sin_seccion']}");

        if ($estadisticas['secciones_huerfanas'] > 0) {
            $this->error("   Secciones huérfanas:       {$estadisticas['secciones_huerfanas']}");
        }

        $this->info("   Cobertura:                 {$estadisticas['cobertura']}%");
        $this->info('');

        // Mostrar prefijos CON sección
        if (count($comparacion['con_seccion']) > 0) {
            $this->info('✅ PREFIJOS CON SECCIÓN ASIGNADA:');
            $headers = ['Prefijo', 'Rutas', 'Sección'];
            $rows = [];
            foreach ($comparacion['con_seccion'] as $item) {
                $rows[] = [$item['prefijo'], $item['total_rutas'], $item['seccion_nombre']];
            }
            $this->table($headers, $rows);
            $this->info('');
        }

        // Mostrar prefijos SIN sección
        if (count($comparacion['sin_seccion']) > 0) {
            $this->warn('❌ PREFIJOS SIN SECCIÓN (usuarios no podrán acceder):');
            $headers = ['Prefijo', 'Rutas', 'Nombre Sugerido'];
            $rows = [];
            foreach ($comparacion['sin_seccion'] as $item) {
                $rows[] = [$item['prefijo'], $item['total_rutas'], $item['nombre_sugerido']];
            }
            $this->table($headers, $rows);
            $this->info('');
        }

        // Mostrar secciones huérfanas
        if (count($comparacion['secciones_huerfanas']) > 0) {
            $this->error('⚠️  SECCIONES HUÉRFANAS (no corresponden a ninguna ruta):');
            $headers = ['ID', 'Nombre', 'Ruta'];
            $rows = [];
            foreach ($comparacion['secciones_huerfanas'] as $item) {
                $rows[] = [$item['id'], $item['nombre'], $item['ruta']];
            }
            $this->table($headers, $rows);
            $this->info('');
        }

        // Crear secciones si se especifica --create
        if ($this->option('create') && count($comparacion['sin_seccion']) > 0) {
            if ($this->confirm('¿Deseas crear las secciones faltantes automáticamente?', true)) {
                $creadas = $service->crearSeccionesFaltantes();

                $this->info('');
                $this->info('✅ SECCIONES CREADAS:');
                $headers = ['ID', 'Nombre', 'Ruta'];
                $rows = [];
                foreach ($creadas as $seccion) {
                    $rows[] = [$seccion['id'], $seccion['nombre'], $seccion['ruta']];
                }
                $this->table($headers, $rows);
                $this->info('');
                $this->info('🎉 Se crearon ' . count($creadas) . ' secciones nuevas.');
                $this->warn('   Recuerda asignarlas a los departamentos correspondientes.');
            }
        } elseif (!$this->option('check') && count($comparacion['sin_seccion']) > 0) {
            $this->info('');
            $this->warn('💡 Para crear las secciones faltantes, ejecuta:');
            $this->info('   php artisan secciones:sync --create');
        }

        return Command::SUCCESS;
    }
}
