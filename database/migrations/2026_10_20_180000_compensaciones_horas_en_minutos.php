<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las solicitudes de horas pasan a guardarse en minutos (pasos de 5) en vez de horas enteras.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('compensaciones_horas', 'horas')) {
            return;
        }

        Schema::table('compensaciones_horas', function (Blueprint $table) {
            $table->renameColumn('horas', 'minutos');
        });

        Schema::table('compensaciones_horas', function (Blueprint $table) {
            $table->integer('minutos')->change();
        });

        DB::table('compensaciones_horas')->update(['minutos' => DB::raw('minutos * 60')]);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('compensaciones_horas', 'minutos')) {
            return;
        }

        DB::table('compensaciones_horas')->update(['minutos' => DB::raw('ROUND(minutos / 60)')]);

        Schema::table('compensaciones_horas', function (Blueprint $table) {
            $table->smallInteger('minutos')->change();
        });

        Schema::table('compensaciones_horas', function (Blueprint $table) {
            $table->renameColumn('minutos', 'horas');
        });
    }
};
