<?php

/**
 * Migración: create_archivos_adjuntos_table
 * Autor: Luis Liévano - JL3 Digital
 *
 * Descripción técnica:
 * - Tabla polimórfica para almacenar archivos asociados
 *   a tareas u obligaciones.
 * - Soporta múltiples archivos por entidad.
 * - Los archivos en Google Drive NO se eliminan físicamente,
 *   solo se pierde la referencia en BD.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_adjuntos', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica
            $table->morphs('archivoable'); 
            // crea: archivoable_id (bigint) + archivoable_type (string)

            // Nombre lógico del archivo (definido por el usuario)
            $table->string('nombre');

            // Ruta en Laravel Storage (public)
            $table->string('archivo')->nullable();

            // Link público / webViewLink de Google Drive
            $table->string('archivo_drive_url')->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_adjuntos');
    }
};
