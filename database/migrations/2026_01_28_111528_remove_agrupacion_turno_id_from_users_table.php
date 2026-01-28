<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agrupacion_turno_id']);
            $table->dropColumn('agrupacion_turno_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('agrupacion_turno_id')->nullable()->after('turno_actual');
            $table->foreign('agrupacion_turno_id')->references('id')->on('agrupacion_turnos')->onDelete('set null');
        });
    }
};
