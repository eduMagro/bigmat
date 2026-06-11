<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerta_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_id')->constrained('alertas')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->boolean('requiere_firma')->default(false);
            $table->timestamps();
        });

        Schema::create('alerta_adjunto_firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_adjunto_id')->constrained('alerta_adjuntos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('firmado')->default(false);
            $table->datetime('firmado_dia')->nullable();
            $table->string('firma_ruta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_adjunto_firmas');
        Schema::dropIfExists('alerta_adjuntos');
    }
};
