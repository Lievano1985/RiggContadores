<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes')->nullOnDelete();
            $table->foreignId('solicitud_requerimiento_id')->nullable()->constrained('solicitud_requerimientos')->nullOnDelete();
            $table->string('tipo', 80);
            $table->string('titulo');
            $table->text('mensaje')->nullable();
            $table->string('url')->nullable();
            $table->json('datos')->nullable();
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'leida_at'], 'sol_notif_user_read_idx');
            $table->index(['solicitud_id', 'tipo'], 'sol_notif_sol_tipo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_notificaciones');
    }
};
