<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();
            $table->foreignId('obligacion_id')
                ->nullable()
                ->constrained('obligaciones')
                ->nullOnDelete();
            $table->foreignId('obligacion_cliente_contador_id')
                ->nullable()
                ->constrained('obligacion_cliente_contador')
                ->nullOnDelete();
            $table->enum('modo_solicitud', ['general', 'definida'])
                ->default('general');
            $table->foreignId('tipo_solicitud_id')
                ->nullable()
                ->constrained('solicitud_tipos')
                ->nullOnDelete();
            $table->enum('origen', ['cliente', 'despacho']);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->json('datos_formulario')->nullable();
            $table->enum('estado_formulario', ['no_aplica', 'pendiente', 'respondido', 'validado'])
                ->nullable();
            $table->json('plantilla_snapshot')->nullable();
            $table->enum('estado', ['abierta', 'en_proceso', 'pendiente_cliente', 'resuelto', 'cerrada', 'cancelada'])
                ->default('abierta');
            $table->string('prioridad')->nullable();
            $table->foreignId('responsable_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('creado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('cerrado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('comentario_cierre')->nullable();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
            $table->index(['responsable_user_id', 'estado']);
            $table->index(['obligacion_id']);
            $table->index(['obligacion_cliente_contador_id']);
            $table->index(['origen']);
            $table->index(['modo_solicitud']);
            $table->index(['tipo_solicitud_id']);
            $table->index(['estado_formulario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
