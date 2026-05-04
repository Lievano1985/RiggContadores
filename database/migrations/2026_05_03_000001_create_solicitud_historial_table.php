<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->foreignId('solicitud_requerimiento_id')->nullable()->constrained('solicitud_requerimientos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 80);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->json('datos')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['solicitud_id', 'created_at'], 'sol_hist_sol_created_idx');
            $table->index(['tipo', 'created_at'], 'sol_hist_tipo_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_historial');
    }
};
