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
            $table->boolean('requiere_aprobacion')->default(true)->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            $table->dropColumn('requiere_aprobacion');
        });
    }
};
