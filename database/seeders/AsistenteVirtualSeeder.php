<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsistenteVirtualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe la sección
        $exists = DB::table('secciones')
            ->where('nombre', 'Asistente Virtual')
            ->exists();

        if ($exists) {
            $this->command->info('La sección del Asistente Virtual ya existe.');
            return;
        }

        // Insertar la sección del Asistente Virtual
        DB::table('secciones')->insert([
            'nombre' => 'Asistente Virtual',
            'ruta' => 'asistente.index',
            'icono' => 'imagenes/iconos/asistente.png',
            'mostrar_en_dashboard' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Sección del Asistente Virtual creada exitosamente.');
    }
}
