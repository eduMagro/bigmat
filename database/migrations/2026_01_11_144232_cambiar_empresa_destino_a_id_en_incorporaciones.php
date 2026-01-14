<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Obtener el ID de la primera empresa (BigMat Los Palacios)
        $empresaId = DB::table('empresas')->first()?->id ?? 1;

        // Cambiar el tipo de columna de ENUM a BIGINT UNSIGNED
        DB::statement("ALTER TABLE incorporaciones MODIFY empresa_destino BIGINT UNSIGNED NOT NULL DEFAULT {$empresaId}");

        // Actualizar los valores existentes
        DB::table('incorporaciones')
            ->whereIn('empresa_destino', ['0', ''])
            ->orWhereNull('empresa_destino')
            ->update(['empresa_destino' => $empresaId]);

        // Actualizar cualquier valor que no sea numérico válido
        DB::statement("UPDATE incorporaciones SET empresa_destino = {$empresaId} WHERE empresa_destino NOT IN (SELECT id FROM empresas)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE incorporaciones MODIFY empresa_destino ENUM('hpr_servicios','hierros_paco_reyes') NOT NULL");
    }
};
