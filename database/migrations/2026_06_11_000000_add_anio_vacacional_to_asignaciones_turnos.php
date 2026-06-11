<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Añade la columna anio_vacacional: año al que se imputan las vacaciones.
     * null = año natural de la fecha. Reemplaza conceptualmente a anio_cargo,
     * cuyos valores se copian para conservar las imputaciones históricas.
     */
    public function up(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio_vacacional')->nullable()->after('anio_cargo');
            $table->index(['user_id', 'estado', 'anio_vacacional']);
        });

        // Backfill: conservar las imputaciones por año ya existentes (anio_cargo).
        DB::statement('UPDATE asignaciones_turnos SET anio_vacacional = anio_cargo WHERE anio_vacacional IS NULL AND anio_cargo IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'estado', 'anio_vacacional']);
            $table->dropColumn('anio_vacacional');
        });
    }
};
