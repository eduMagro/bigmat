<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Turno;
use App\Models\AgrupacionTurno;
use App\Models\AgrupacionTurnoDia;

class TurnosYAgrupacionesSeeder extends Seeder
{
    /**
     * Seed de turnos y agrupaciones de turnos.
     * Basado en: turnos y responsables.xlsx
     */
    public function run(): void
    {
        // =====================================================
        // TURNOS BASE (horarios individuales)
        // =====================================================

        $turnos = [
            // Turno 1: L-V 7:00-15:00
            [
                'nombre' => 'Mañana 7-15',
                'hora_inicio' => '07:00:00',
                'hora_fin' => '15:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 1,
                'color' => '#FCD34D', // Amarillo
                'color_texto' => '#000000',
                'es_partido' => false,
            ],
            // Turno 2 y 3 L-V: 7:00-14:00 + 15:00-20:00 (partido)
            [
                'nombre' => 'Partido 7-14/15-20',
                'hora_inicio' => '07:00:00',
                'hora_fin' => '14:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 2,
                'color' => '#10B981', // Verde
                'color_texto' => '#FFFFFF',
                'es_partido' => true,
                'hora_inicio2' => '15:00:00',
                'hora_fin2' => '20:00:00',
                'offset_dias_inicio2' => 0,
                'offset_dias_fin2' => 0,
            ],
            // Turno 3 Sábado: 8:00-13:00
            [
                'nombre' => 'Mañana Sábado 8-13',
                'hora_inicio' => '08:00:00',
                'hora_fin' => '13:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 3,
                'color' => '#F97316', // Naranja
                'color_texto' => '#FFFFFF',
                'es_partido' => false,
            ],
            // Turno 4: L-V 8:00-12:00
            [
                'nombre' => 'Mañana 8-12',
                'hora_inicio' => '08:00:00',
                'hora_fin' => '12:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 4,
                'color' => '#6366F1', // Indigo
                'color_texto' => '#FFFFFF',
                'es_partido' => false,
            ],
            // Vacaciones
            [
                'nombre' => 'Vacaciones',
                'hora_inicio' => '00:00:00',
                'hora_fin' => '23:59:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 99,
                'color' => '#22C55E', // Verde claro
                'color_texto' => '#FFFFFF',
                'es_partido' => false,
            ],
        ];

        $turnosCreados = [];
        foreach ($turnos as $turnoData) {
            $turno = Turno::updateOrCreate(
                ['nombre' => $turnoData['nombre']],
                $turnoData
            );
            $turnosCreados[$turnoData['nombre']] = $turno->id;
        }

        $this->command->info('Turnos creados: ' . count($turnosCreados));

        // =====================================================
        // AGRUPACIONES DE TURNOS (plantillas semanales)
        // Basado en: turnos y responsables.xlsx
        // =====================================================

        $agrupaciones = [
            // TURNO 1: L-V 7:00-15:00
            [
                'nombre' => 'Turno 1',
                'descripcion' => 'L-V 7:00-15:00',
                'activo' => true,
                'orden' => 1,
                'dias' => [
                    1 => 'Mañana 7-15', // Lunes
                    2 => 'Mañana 7-15', // Martes
                    3 => 'Mañana 7-15', // Miércoles
                    4 => 'Mañana 7-15', // Jueves
                    5 => 'Mañana 7-15', // Viernes
                    6 => null,          // Sábado - libre
                    0 => null,          // Domingo - libre
                ],
            ],
            // TURNO 2: L-V 7:00-14:00 + 15:00-20:00
            [
                'nombre' => 'Turno 2',
                'descripcion' => 'L-V 7:00-14:00 + 15:00-20:00 (partido)',
                'activo' => true,
                'orden' => 2,
                'dias' => [
                    1 => 'Partido 7-14/15-20',
                    2 => 'Partido 7-14/15-20',
                    3 => 'Partido 7-14/15-20',
                    4 => 'Partido 7-14/15-20',
                    5 => 'Partido 7-14/15-20',
                    6 => null,
                    0 => null,
                ],
            ],
            // TURNO 3: L-V 7:00-14:00 + 15:00-20:00, Sábado 8:00-13:00
            [
                'nombre' => 'Turno 3',
                'descripcion' => 'L-V 7:00-14:00 + 15:00-20:00 (partido), Sáb 8:00-13:00',
                'activo' => true,
                'orden' => 3,
                'dias' => [
                    1 => 'Partido 7-14/15-20',
                    2 => 'Partido 7-14/15-20',
                    3 => 'Partido 7-14/15-20',
                    4 => 'Partido 7-14/15-20',
                    5 => 'Partido 7-14/15-20',
                    6 => 'Mañana Sábado 8-13', // Sábado
                    0 => null,
                ],
            ],
            // TURNO 4: L-V 8:00-12:00
            [
                'nombre' => 'Turno 4',
                'descripcion' => 'L-V 8:00-12:00',
                'activo' => true,
                'orden' => 4,
                'dias' => [
                    1 => 'Mañana 8-12',
                    2 => 'Mañana 8-12',
                    3 => 'Mañana 8-12',
                    4 => 'Mañana 8-12',
                    5 => 'Mañana 8-12',
                    6 => null,
                    0 => null,
                ],
            ],
        ];

        foreach ($agrupaciones as $agrupacionData) {
            $dias = $agrupacionData['dias'];
            unset($agrupacionData['dias']);

            // Crear o actualizar la agrupación
            $agrupacion = AgrupacionTurno::updateOrCreate(
                ['nombre' => $agrupacionData['nombre']],
                $agrupacionData
            );

            // Eliminar días existentes para recrearlos
            AgrupacionTurnoDia::where('agrupacion_turno_id', $agrupacion->id)->delete();

            // Crear los días de la agrupación
            foreach ($dias as $diaSemana => $turnoNombre) {
                AgrupacionTurnoDia::create([
                    'agrupacion_turno_id' => $agrupacion->id,
                    'dia_semana' => $diaSemana,
                    'turno_id' => $turnoNombre ? ($turnosCreados[$turnoNombre] ?? null) : null,
                ]);
            }
        }

        $this->command->info('Agrupaciones de turnos creadas: ' . count($agrupaciones));
    }
}
