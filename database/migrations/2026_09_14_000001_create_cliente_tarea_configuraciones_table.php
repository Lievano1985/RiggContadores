<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_tarea_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tarea_catalogo_id')->constrained('tareas_catalogo')->cascadeOnDelete();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['cliente_id', 'tarea_catalogo_id'], 'cliente_tarea_configuracion_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_tarea_configuraciones');
    }
};
