<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObligacionesTable extends Migration
{
    public function up()
    {
        Schema::create('obligaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo'); // federal, estatal, local, patronal
            $table->string('periodicidad'); // mensual, bimestral, trimestral, anual, etc.

            // Nuevos campos de control de calendario
            $table->unsignedTinyInteger('mes_inicio')->nullable()->comment('Mes en que inicia el ciclo');
            $table->unsignedTinyInteger('desfase_meses')->nullable()->comment('Meses posteriores para vencimiento');
            $table->unsignedTinyInteger('dia_corte')->nullable()->comment('Día límite de cumplimiento');
            $table->boolean('activa')->default(true);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('obligaciones');
    }
}
