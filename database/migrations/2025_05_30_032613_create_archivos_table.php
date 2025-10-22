<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchivosTable extends Migration
{
    public function up()
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->string('tipo'); // ej: 'fiel', 'csd', 'documento'
            $table->string('path_local')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('url_drive')->nullable();
            $table->enum('origen', ['local', 'drive', 'both'])->default('local');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('archivos');
    }
}

