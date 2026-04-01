<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obligacion_cliente_contador', function (Blueprint $table) {
            $table->boolean('sin_carpeta')->default(false)->after('carpeta_drive_id');
        });

        Schema::table('tareas_asignadas', function (Blueprint $table) {
            $table->boolean('sin_carpeta')->default(false)->after('carpeta_drive_id');
        });
    }

    public function down(): void
    {
        Schema::table('obligacion_cliente_contador', function (Blueprint $table) {
            $table->dropColumn('sin_carpeta');
        });

        Schema::table('tareas_asignadas', function (Blueprint $table) {
            $table->dropColumn('sin_carpeta');
        });
    }
};
