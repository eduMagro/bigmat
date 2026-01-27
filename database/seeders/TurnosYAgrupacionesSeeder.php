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
     */
    public function run(): void
    {
        // =====================================================
        // TURNOS
        // =====================================================

        $turnos = [
            [
                'nombre' => 'Mañana',
                'hora_inicio' => '06:00:00',
                'hora_fin' => '14:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 1,
                'color' => '#FCD34D', // Amarillo
                'color_texto' => '#000000',
                'es_partido' => false,
            ],
            [
                'nombre' => 'Tarde',
                'hora_inicio' => '14:00:00',
                'hora_fin' => '22:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 2,
                'color' => '#F97316', // Naranja
                'color_texto' => '#FFFFFF',
                'es_partido' => false,
            ],
            [
                'nombre' => 'Noche',
                'hora_inicio' => '22:00:00',
                'hora_fin' => '06:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 1, // Termina al día siguiente
                'activo' => true,
                'orden' => 3,
                'color' => '#1E3A8A', // Azul oscuro
                'color_texto' => '#FFFFFF',
                'es_partido' => false,
            ],
            [
                'nombre' => 'Partido',
                'hora_inicio' => '08:00:00',
                'hora_fin' => '14:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 4,
                'color' => '#10B981', // Verde
                'color_texto' => '#FFFFFF',
                'es_partido' => true,
                'hora_inicio2' => '16:00:00',
                'hora_fin2' => '20:00:00',
                'offset_dias_inicio2' => 0,
                'offset_dias_fin2' => 0,
            ],
            [
                'nombre' => 'Oficina',
                'hora_inicio' => '08:00:00',
                'hora_fin' => '17:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 5,
                'color' => '#6366F1', // Indigo
                'color_texto' => '#FFFFFF',
                'es_partido' => false,
            ],
            [
                'nombre' => 'Flexible',
                'hora_inicio' => '07:00:00',
                'hora_fin' => '15:00:00',
                'offset_dias_inicio' => 0,
                'offset_dias_fin' => 0,
                'activo' => true,
                'orden' => 6,
                'color' => '#8B5CF6', // Violeta
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
        // AGRUPACIONES DE TURNOS
        // =====================================================

        $agrupaciones = [
            [
                'nombre' => 'Turno Mañana L-V',
                'descripcion' => 'Turno de mañana de lunes a viernes',
                'activo' => true,
                'orden' => 1,
                'dias' => [
                    1 => 'Mañana', // Lunes
                    2 => 'Mañana', // Martes
                    3 => 'Mañana', // Miércoles
                    4 => 'Mañana', // Jueves
                    5 => 'Mañana', // Viernes
                    6 => null,     // Sábado - libre
                    0 => null,     // Domingo - libre
                ],
            ],
            [
                'nombre' => 'Turno Tarde L-V',
                'descripcion' => 'Turno de tarde de lunes a viernes',
                'activo' => true,
                'orden' => 2,
                'dias' => [
                    1 => 'Tarde',
                    2 => 'Tarde',
                    3 => 'Tarde',
                    4 => 'Tarde',
                    5 => 'Tarde',
                    6 => null,
                    0 => null,
                ],
            ],
            [
                'nombre' => 'Turno Noche L-V',
                'descripcion' => 'Turno de noche de lunes a viernes',
                'activo' => true,
                'orden' => 3,
                'dias' => [
                    1 => 'Noche',
                    2 => 'Noche',
                    3 => 'Noche',
                    4 => 'Noche',
                    5 => 'Noche',
                    6 => null,
                    0 => null,
                ],
            ],
            [
                'nombre' => 'Turno Mañana L-S',
                'descripcion' => 'Turno de mañana de lunes a sábado',
                'activo' => true,
                'orden' => 4,
                'dias' => [
                    1 => 'Mañana',
                    2 => 'Mañana',
                    3 => 'Mañana',
                    4 => 'Mañana',
                    5 => 'Mañana',
                    6 => 'Mañana', // Sábado también
                    0 => null,
                ],
            ],
            [
                'nombre' => 'Turno Partido L-V',
                'descripcion' => 'Turno partido (mañana y tarde) de lunes a viernes',
                'activo' => true,
                'orden' => 5,
                'dias' => [
                    1 => 'Partido',
                    2 => 'Partido',
                    3 => 'Partido',
                    4 => 'Partido',
                    5 => 'Partido',
                    6 => null,
                    0 => null,
                ],
            ],
            [
                'nombre' => 'Oficina L-V',
                'descripcion' => 'Horario de oficina estándar de lunes a viernes',
                'activo' => true,
                'orden' => 6,
                'dias' => [
                    1 => 'Oficina',
                    2 => 'Oficina',
                    3 => 'Oficina',
                    4 => 'Oficina',
                    5 => 'Oficina',
                    6 => null,
                    0 => null,
                ],
            ],
            [
                'nombre' => 'Rotativo M-T',
                'descripcion' => 'Alterna semana mañana y semana tarde',
                'activo' => true,
                'orden' => 7,
                'dias' => [
                    1 => 'Mañana',
                    2 => 'Mañana',
                    3 => 'Mañana',
                    4 => 'Tarde',
                    5 => 'Tarde',
                    6 => null,
                    0 => null,
                ],
            ],
            [
                'nombre' => 'Flexible L-V',
                'descripcion' => 'Horario flexible de lunes a viernes',
                'activo' => true,
                'orden' => 8,
                'dias' => [
                    1 => 'Flexible',
                    2 => 'Flexible',
                    3 => 'Flexible',
                    4 => 'Flexible',
                    5 => 'Flexible',
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
