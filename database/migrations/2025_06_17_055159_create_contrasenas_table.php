<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contrasenas', function (Blueprint $table) {
            $table->id();
            $table->string('portal');
            $table->string('url')->nullable(); // Link del portal
            $table->string('usuario')->nullable();
            $table->string('contrasena');
            $table->string('correo')->nullable();
            $table->string('archivo_certificado')->nullable();
            $table->string('archivo_clave')->nullable();
            $table->date('vencimiento')->nullable();
            $table->string('registro_patronal')->nullable();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrasenas');
    }
};
