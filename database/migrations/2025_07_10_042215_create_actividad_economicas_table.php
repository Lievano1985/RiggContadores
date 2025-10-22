<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// create_actividad_economicas_table.php
class CreateActividadEconomicasTable extends Migration
{
    public function up()
    {
        Schema::create('actividad_economicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('clave');
            $table->integer('ponderacion')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('actividad_economicas');
    }
}