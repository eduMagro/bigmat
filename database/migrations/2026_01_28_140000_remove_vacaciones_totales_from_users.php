<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eliminar columna vacaciones_totales - ahora se calcula dinámicamente
     * según la fecha de incorporación del usuario.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('vacaciones_totales');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('vacaciones_totales')->default(30)->after('dias_vacaciones');
        });
    }
};
