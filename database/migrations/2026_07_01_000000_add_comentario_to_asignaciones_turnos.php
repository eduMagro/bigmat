<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comentario libre para justificar una ausencia (sobre todo el motivo de una
     * falta injustificada). Texto plano opcional por asignación de turno.
     */
    public function up(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->text('comentario')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropColumn('comentario');
        });
    }
};
