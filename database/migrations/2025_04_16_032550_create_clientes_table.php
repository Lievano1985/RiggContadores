<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')
                ->nullable()
                ->constrained('despachos')
                ->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->enum('tipo_persona', ['fisica', 'moral']);
            $table->string('nombre');
            $table->string('nombre_comercial')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('rfc', 13);
            $table->string('curp')->nullable();
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->boolean('tiene_trabajadores')->default(false);
            $table->date('inicio_obligaciones')->nullable();
            $table->date('fin_obligaciones')->nullable();
            $table->string('contrato')->nullable();
            $table->string('vigencia')->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('rfc_representante')->nullable();
            $table->string('correo_representante')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
}
