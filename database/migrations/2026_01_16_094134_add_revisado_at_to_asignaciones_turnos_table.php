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
            $table->timestamp('revisado_at')->nullable()->after('salida2');
            $table->unsignedBigInteger('revisado_por')->nullable()->after('revisado_at');

            $table->foreign('revisado_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_turnos', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropColumn(['revisado_at', 'revisado_por']);
        });
    }
};
