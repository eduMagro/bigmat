<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('incorporaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('incorporaciones', 'fecha_incorporacion')) {
                $table->date('fecha_incorporacion')->nullable();
            }
        });
    }

    public function down()
    {
        if (Schema::hasColumn('incorporaciones', 'fecha_incorporacion')) {
            Schema::table('incorporaciones', function (Blueprint $table) {
                $table->dropColumn('fecha_incorporacion');
            });
        }
    }
};
