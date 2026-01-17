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
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->dropColumn([
                'aprobado_rrhh',
                'aprobado_rrhh_at',
                'aprobado_rrhh_by',
                'aprobado_ceo',
                'aprobado_ceo_at',
                'aprobado_ceo_by',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->boolean('aprobado_rrhh')->nullable()->default(false);
            $table->datetime('aprobado_rrhh_at')->nullable();
            $table->unsignedBigInteger('aprobado_rrhh_by')->nullable();
            $table->boolean('aprobado_ceo')->nullable()->default(false);
            $table->datetime('aprobado_ceo_at')->nullable();
            $table->unsignedBigInteger('aprobado_ceo_by')->nullable();
        });
    }
};
