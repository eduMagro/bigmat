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
        Schema::table('turnos', function (Blueprint $table) {
            $table->boolean('es_partido')->default(false)->after('color');
            $table->time('hora_inicio2')->nullable()->after('es_partido');
            $table->time('hora_fin2')->nullable()->after('hora_inicio2');
            $table->integer('offset_dias_inicio2')->default(0)->after('hora_fin2');
            $table->integer('offset_dias_fin2')->default(0)->after('offset_dias_inicio2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['es_partido', 'hora_inicio2', 'hora_fin2', 'offset_dias_inicio2', 'offset_dias_fin2']);
        });
    }
};
