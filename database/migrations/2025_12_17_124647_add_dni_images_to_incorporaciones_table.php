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
        Schema::table('incorporaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('incorporaciones', 'dni_frontal')) {
                $table->string('dni_frontal', 255)->nullable()->after('dni');
            }
            if (!Schema::hasColumn('incorporaciones', 'dni_trasero')) {
                $table->string('dni_trasero', 255)->nullable()->after('dni_frontal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->dropColumn(['dni_frontal', 'dni_trasero']);
        });
    }
};
