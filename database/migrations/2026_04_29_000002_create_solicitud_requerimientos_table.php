<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_requerimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')
                ->constrained('solicitudes')
                ->cascadeOnDelete();
            $table->foreignId('creado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('destinatario_tipo', ['cliente', 'interno']);
            $table->foreignId('destinatario_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['abierto', 'respondido', 'validado', 'rechazado', 'cancelado'])
                ->default('abierto');
            $table->dateTime('fecha_limite')->nullable();
            $table->text('respuesta_texto')->nullable();
            $table->foreignId('respondido_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('respondido_at')->nullable();
            $table->foreignId('validado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('validado_at')->nullable();
            $table->text('comentario_validacion')->nullable();
            $table->timestamps();

            $table->index(['solicitud_id', 'estado']);
            $table->index(['destinatario_tipo', 'destinatario_user_id'], 'sol_req_dest_idx');
            $table->index(['fecha_limite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_requerimientos');
    }
};
