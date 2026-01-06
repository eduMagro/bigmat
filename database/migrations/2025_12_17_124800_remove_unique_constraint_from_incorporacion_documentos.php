<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        try {
            DB::statement('ALTER TABLE `incorporacion_documentos` DROP INDEX `uk_incorporacion_tipo`');
        } catch (\Exception $e) {
            // Ignorar si el índice no existe
        }
    }

    public function down()
    {
        Schema::table('incorporacion_documentos', function (Blueprint $table) {
            // Re-adding it would be problematic if duplicates exist now.
            // So we might leave down empty or try to add it back only if no duplicates.
        });
    }
};
