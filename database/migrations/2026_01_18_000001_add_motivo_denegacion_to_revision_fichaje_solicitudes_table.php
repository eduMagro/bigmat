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
        Schema::table('revision_fichaje_solicitudes', function (Blueprint $table) {
            $table->text('motivo_denegacion')->nullable()->after('observaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revision_fichaje_solicitudes', function (Blueprint $table) {
            $table->dropColumn('motivo_denegacion');
        });
    }
};
