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
        Schema::table('departamentos', function (Blueprint $table) {
            $table->foreignId('responsable_id')->nullable()->after('descripcion')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
            $table->dropColumn('responsable_id');
        });
    }
};
