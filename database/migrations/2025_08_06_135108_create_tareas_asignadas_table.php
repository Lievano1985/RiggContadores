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
        Schema::create('tareas_asignadas', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('tarea_catalogo_id')->constrained('tareas_catalogo')->onDelete('cascade');
            $table->foreignId('contador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('obligacion_cliente_contador_id')->nullable()->constrained('obligacion_cliente_contador')->nullOnDelete();
            $table->foreignId('carpeta_drive_id')->nullable()->constrained('carpeta_drives')->nullOnDelete();

            $table->dateTime('fecha_asignacion')->nullable();
            $table->dateTime('fecha_limite')->nullable();
            $table->integer('tiempo_estimado')->nullable();

            $table->enum('estatus', ['asignada',  'en_progreso', 'realizada', 'revisada', 'rechazada'])->default('asignada');
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_termino')->nullable();
            $table->year('ejercicio')->nullable()->index();
            $table->unsignedTinyInteger('mes')->nullable()->index();

            $table->string('periodo')->nullable()->index();

            $table->string('archivo')->nullable(); // Laravel Storage
            $table->string('archivo_drive_url')->nullable(); // opcional
            $table->text('comentario')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas_asignadas');
    }
};
