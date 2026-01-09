<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            // Año al que se cargan las vacaciones (solo para estado='vacaciones')
            // Permite elegir año anterior en período de gracia (1 ene - 31 mar)
            $table->unsignedSmallInteger('anio_cargo')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropColumn('anio_cargo');
        });
    }
};
