<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpeta_drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('carpeta_drives')->onDelete('cascade');
            $table->string('tipo'); 
            $table->string('drive_folder_id')->unique();
            $table->string('nombre')->nullable(); // nombre visible o personalizado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpeta_drives');
    }
  
};
