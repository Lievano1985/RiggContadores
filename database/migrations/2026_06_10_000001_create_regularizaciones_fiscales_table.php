<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regularizaciones_fiscales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->unsignedInteger('generadas')->default(0);
            $table->unsignedInteger('ya_existian')->default(0);
            $table->unsignedInteger('omitidas')->default(0);
            $table->json('obligaciones_solicitadas')->nullable();
            $table->json('resumen')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'anio', 'mes'], 'reg_fisc_cliente_periodo_idx');
        });

        Schema::create('regularizacion_obligacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regularizacion_fiscal_id')
                ->constrained('regularizaciones_fiscales')
                ->cascadeOnDelete();
            $table->foreignId('obligacion_cliente_contador_id')
                ->constrained('obligacion_cliente_contador')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['regularizacion_fiscal_id', 'obligacion_cliente_contador_id'],
                'reg_obl_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regularizacion_obligacion');
        Schema::dropIfExists('regularizaciones_fiscales');
    }
};
