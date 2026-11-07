<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones_clientes', function (Blueprint $table) {
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes')->nullOnDelete();
            $table->integer('periodo_mes')->nullable()->change();
            $table->integer('periodo_ejercicio')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones_clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solicitud_id');
        });
    }
};
