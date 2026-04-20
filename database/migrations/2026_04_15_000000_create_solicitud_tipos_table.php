<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('titulo_sugerido')->nullable();
            $table->text('descripcion_sugerida')->nullable();
            $table->string('prioridad_default')->nullable();
            $table->enum('aplica_para', ['cliente', 'despacho', 'ambos'])->default('ambos');
            $table->json('documentos_sugeridos')->nullable();
            $table->json('configuracion_formulario')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_tipos');
    }
};
