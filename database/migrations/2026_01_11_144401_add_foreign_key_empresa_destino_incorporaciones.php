<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->foreign('empresa_destino')
                  ->references('id')
                  ->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->dropForeign(['empresa_destino']);
        });
    }
};
