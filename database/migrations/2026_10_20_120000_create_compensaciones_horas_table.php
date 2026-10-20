<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de compensación de horas contra la bolsa de horas del trabajador.
     *
     * horas: entero con signo. Negativo = el trabajador se toma horas libres (restan de su bolsa).
     * Positivo = se le reconocen horas extra (suman a su bolsa).
     * Un día con compensación aprobada cuenta en la bolsa por estas horas, no por su fichaje.
     */
    public function up(): void
    {
        Schema::create('compensaciones_horas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->smallInteger('horas');
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
