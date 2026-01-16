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
        Schema::create('revision_fichaje_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['pendiente', 'resuelta', 'denegada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('resuelta_por')->nullable();
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamps();

            $table->foreign('resuelta_por')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_fichaje_solicitudes');
    }
};
