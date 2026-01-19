<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar registros de formaciones con tipos de cursos obsoletos
        DB::table('incorporacion_formaciones')
            ->whereIn('tipo', ['curso_20h_generico', 'curso_6h_ferralla'])
            ->delete();

        // Eliminar columna requiere_aprobacion de incorporaciones
        Schema::table('incorporaciones', function (Blueprint $table) {
            if (Schema::hasColumn('incorporaciones', 'requiere_aprobacion')) {
                $table->dropColumn('requiere_aprobacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar columna requiere_aprobacion
        Schema::table('incorporaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('incorporaciones', 'requiere_aprobacion')) {
                $table->boolean('requiere_aprobacion')->default(false)->after('empresa_destino');
            }
        });
    }
};
