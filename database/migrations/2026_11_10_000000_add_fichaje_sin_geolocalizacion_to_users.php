<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fichaje libre de geolocalización: los usuarios marcados pueden fichar sin
     * enviar su ubicación ni estar dentro del radio de un lugar de trabajo.
     * Se gestiona desde Ajustes.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'fichaje_sin_geolocalizacion')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('fichaje_sin_geolocalizacion')->default(false);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'fichaje_sin_geolocalizacion')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fichaje_sin_geolocalizacion');
        });
    }
};
