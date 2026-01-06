<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('documento_empleados')) {
            Schema::create('documento_empleados', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('tipo')->default('contrato'); // contrato, prorroga, nomina, otros
                $table->string('titulo');
                $table->string('ruta_archivo'); // path en storage/app/private/documentos_empleados/{user_id}/
                $table->date('fecha_vencimiento')->nullable();
                $table->text('comentarios')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_empleados');
    }
};
