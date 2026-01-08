<?php

/**
 * Modelo: ArchivoAdjunto
 * Autor: Luis Liévano - JL3 Digital
 *
 * Descripción técnica:
 * - Representa archivos adjuntos asociados de forma polimórfica
 *   a tareas u obligaciones.
 * - Un archivo tiene nombre lógico y puede vivir en Storage y/o Drive.
 * - Eliminar el modelo elimina SOLO la referencia en BD,
 *   no el archivo físico en Google Drive.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ArchivoAdjunto extends Model
{
    protected $table = 'archivos_adjuntos';

    /**
     * Atributos asignables
     */
    protected $fillable = [
        'nombre',
        'archivo',
        'archivo_drive_url',
    ];

    /**
     * Relación polimórfica
     * Puede pertenecer a:
     * - TareaAsignada
     * - ObligacionClienteContador
     */
    public function archivoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helper: indica si el archivo existe en Storage
     */
    public function tieneArchivoStorage(): bool
    {
        return !empty($this->archivo);
    }

    /**
     * Helper: indica si el archivo existe en Drive
     */
    public function tieneArchivoDrive(): bool
    {
        return !empty($this->archivo_drive_url);
    }
}
