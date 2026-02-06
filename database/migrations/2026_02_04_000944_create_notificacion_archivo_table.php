<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('notificacion_archivo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notificacion_cliente_id')
                ->constrained('notificaciones_clientes')
                ->cascadeOnDelete();

            $table->foreignId('archivo_id')
                ->constrained('archivos_adjuntos')

                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_archivo');
    }

};
