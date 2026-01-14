<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar el tipo de columna ENUM a los nuevos valores
        DB::statement("ALTER TABLE incorporacion_formaciones MODIFY tipo ENUM('cv', 'curso') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE incorporacion_formaciones MODIFY tipo ENUM('curso_20h_generico','curso_6h_ferralla','otros_cursos','formacion_generica_puesto','formacion_especifica_puesto') NOT NULL");
    }
};
