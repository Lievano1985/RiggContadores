<?php
/**
 * Migración principal: Tabla obligacion_cliente_contador
 * Autor: Luis Liévano - JL3 Digital
 * Descripción: Registra las obligaciones asignadas a cada cliente, incluyendo control de vigencia (is_activa, fecha_baja, motivo_baja).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObligacionClienteContadorTable extends Migration
{
    public function up()
    {
        Schema::create('obligacion_cliente_contador', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('obligacion_id')->constrained('obligaciones')->onDelete('cascade');
            $table->foreignId('contador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('carpeta_drive_id')->nullable()->constrained('carpeta_drives')->nullOnDelete();

            // Ciclo fiscal
            $table->unsignedSmallInteger('ejercicio')->nullable()->index()->comment('Año fiscal');
            $table->unsignedTinyInteger('mes')->nullable()->index()->comment('Mes del ejercicio (1-12)');

            // Seguimiento de asignación
            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            // Estado del proceso
            $table->enum('estatus', [
                'asignada',
                'en_progreso',
                'realizada',
                'enviada_cliente',
                'respuesta_cliente',
                'respuesta_revisada',
                'finalizado',
                'rechazada',
                'reabierta'
            ])->default('asignada');

            // Trazabilidad
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_termino')->nullable();
            $table->timestamp('fecha_finalizado')->nullable();

            // Archivos
            $table->string('archivo_resultado')->nullable();
            $table->string('numero_operacion')->nullable();
            $table->string('archivo_cliente')->nullable();

            // Comentario interno
            $table->text('comentario')->nullable();

            // Control de unicidad por periodo
            $table->unique(['cliente_id', 'obligacion_id', 'ejercicio', 'mes'], 'unique_cliente_obligacion_periodo');

            // Control de versiones
            $table->unsignedTinyInteger('revision')->default(1);
            $table->foreignId('obligacion_padre_id')->nullable()
                ->constrained('obligacion_cliente_contador')->nullOnDelete();

            //  Campos de control administrativo (nuevos)
            $table->boolean('is_activa')->default(true)
                ->comment('Indica si la obligación sigue activa para el cliente');
            $table->date('fecha_baja')->nullable()
                ->comment('Fecha en la que se dio de baja la obligación');
            $table->text('motivo_baja')->nullable()
                ->comment('Motivo o justificación de la baja de la obligación');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('obligacion_cliente_contador');
    }
}
