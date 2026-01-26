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
        Schema::create('agrupacion_turno_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agrupacion_turno_id')->constrained('agrupaciones_turnos')->onDelete('cascade');
            $table->tinyInteger('dia_semana')->comment('0=Domingo, 1=Lunes, ..., 6=Sábado');
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->onDelete('set null');
            $table->timestamps();

            $table->unique(['agrupacion_turno_id', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agrupacion_turno_dias');
    }
};
