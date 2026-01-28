<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indices para optimizar consultas frecuentes en asignaciones_turnos
     */
    public function up(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            // Indice para consultas por fecha (muy frecuente)
            $table->index('fecha', 'idx_asignaciones_fecha');

            // Indice para consultas por estado
            $table->index('estado', 'idx_asignaciones_estado');

            // Indice compuesto para consultas por usuario y fecha (getHorasMensuales, eventosTurnos)
            $table->index(['user_id', 'fecha'], 'idx_asignaciones_user_fecha');

            // Indice compuesto para consultas por usuario, estado y fecha (getResumenAsistencia)
            $table->index(['user_id', 'estado', 'fecha'], 'idx_asignaciones_user_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropIndex('idx_asignaciones_fecha');
            $table->dropIndex('idx_asignaciones_estado');
            $table->dropIndex('idx_asignaciones_user_fecha');
            $table->dropIndex('idx_asignaciones_user_estado_fecha');
        });
    }
};
