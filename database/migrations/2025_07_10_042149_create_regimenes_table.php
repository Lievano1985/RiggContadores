<?php
// Migraciones completas
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// create_regimenes_table.php
class CreateRegimenesTable extends Migration
{
    public function up()
    {
        Schema::create('regimenes', function (Blueprint $table) {
            $table->id();
            $table->string('clave_sat');
            $table->string('nombre');
            $table->enum('tipo_persona', ['física', 'moral', 'física/moral']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('regimenes');
    }
}