<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de compensación de horas contra la bolsa de horas del trabajador.
     *
     * minutos: entero con signo, en pasos de 5. Negativo = el trabajador se toma tiempo libre
     * (resta de su bolsa). Positivo = se le reconocen horas extra (suman a su bolsa).
     * Solo lo aprobado mueve la bolsa; el fichaje sirve de comprobación.
     */
    public function up(): void
    {
        Schema::create('compensaciones_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->integer('minutos');
            $table->string('estado', 20)->default('pendiente'); // pendiente | aprobada | denegada
            $table->text('comentario')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensaciones_horas');
    }
};
