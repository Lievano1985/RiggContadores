<?php

/**
 * Componente Livewire: ArchivosAdjuntosCrud
 * Autor: Luis Liévano - JL3 Digital
 *
 * Descripción técnica:
 * - Componente reutilizable para gestionar archivos adjuntos
 *   asociados a modelos polimórficos (tareas u obligaciones).
 * - Permite subir múltiples archivos con nombre lógico.
 * - Permite eliminar archivos uno a uno.
 * - NO maneja estatus ni flujos.
 */

namespace App\Livewire\Shared;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\ArchivoAdjunto;
use App\Services\DriveService;
use Illuminate\Support\Facades\Auth;
use App\Models\CarpetaDrive;
class ArchivosAdjuntosCrud extends Component
{
    use WithFileUploads;

    /** Modelo padre (tarea u obligación) */
    public Model $modelo;

    /**
     * Archivos nuevos a subir
     * [
     *   ['nombre' => '', 'file' => null],
     * ]
     */
    public array $nuevosArchivos = [];

    protected function rules()
    {
        return [
            'nuevosArchivos' => ['array'],
            'nuevosArchivos.*.nombre' => ['required', 'string', 'max:255'],
            'nuevosArchivos.*.file'   => ['required', 'file', 'mimes:pdf,zip,jpg,jpeg,png'],
        ];
    }

    public function mount(): void
    {
        $this->nuevosArchivos = [
            ['nombre' => '', 'file' => null],
        ];
    }

    // =====================================================
    // ACCIONES
    // =====================================================

    public function agregarArchivo(): void
    {
        $this->nuevosArchivos[] = ['nombre' => '', 'file' => null];
    }

    public function quitarArchivo(int $index): void
    {
        unset($this->nuevosArchivos[$index]);
        $this->nuevosArchivos = array_values($this->nuevosArchivos);
    }

    public function eliminarArchivo(int $archivoId): void
    {
        $archivo = $this->modelo->archivos()->findOrFail($archivoId);

        // Borra archivo local si existe (Drive NO)
        if ($archivo->archivo) {
            Storage::disk('public')->delete($archivo->archivo);
        }

        $archivo->delete();

        $this->dispatch('toast', type: 'success', message: 'Archivo eliminado.');
    }


    
    public function guardar(): void
    {
        $this->validate();
    
        $modelo = $this->modelo;
    
        // Detectar despacho (tarea u obligación)
        $cliente = $modelo->cliente ?? $modelo->obligacionClienteContador?->cliente;
        $despacho = $cliente->despacho;
    
        $politica = $despacho->politica_almacenamiento; // 👈 CORRECTO
    
        foreach ($this->nuevosArchivos as $item) {
    
            $extension = $item['file']->getClientOriginalExtension();

            $nombreFinal = /* now()->format('Ymd_His') . '_' .  */
                           \Str::slug($item['nombre']) . 
                           '.' . $extension;
                
            $rutaStorage = null;
            $urlDrive = null;
    
            /* ============================
             | STORAGE
            ============================ */
            if (in_array($politica, ['storage_only', 'both'])) {
    
                $rutaStorage = $item['file']->storeAs(
                    'adjuntos',
                    $nombreFinal,
                    'public'
                );
            }
    
            /* ============================
             | DRIVE
            ============================ */
            if (in_array($politica, ['drive_only', 'both'])) {
    
                $folderId = null;
    
                // Buscar carpeta real en tabla carpetas_drive
                if ($modelo->carpeta_drive_id) {
                    $cd = CarpetaDrive::find($modelo->carpeta_drive_id);
                    $folderId = $cd?->drive_folder_id;
                }
    
                if ($folderId) {
                    try {
    
                        $drive = app(DriveService::class);
    
                        $res = $drive->subirArchivo(
                            $nombreFinal,
                            $item['file'], // objeto completo
                            $folderId,
                            $item['file']->getMimeType()
                        );
    
                        if (is_string($res)) {
                            $urlDrive = $res;
                        } elseif (is_array($res) && isset($res['webViewLink'])) {
                            $urlDrive = $res['webViewLink'];
                        }
    
                    } catch (\Exception $e) {
    
                        \Log::error('❌ Error Drive: ' . $e->getMessage());
                        $this->addError('archivo', 'Error al subir archivo a Google Drive.');
                    }
                } else {
                    $this->addError('archivo', 'No se encontró carpeta de destino en Drive.');
                }
            }
    
            $this->modelo->archivos()->create([
                'nombre' => $item['nombre'],
                'archivo' => $rutaStorage,
                'archivo_drive_url' => $urlDrive,
            ]);
        }
    
        $this->reset('nuevosArchivos');
        $this->mount();
    
        $this->dispatch('toast', type: 'success', message: 'Archivos guardados.');
    }
    


  
    public function render()
    {
        return view('livewire.shared.archivos-adjuntos-crud', [
            'archivos' => $this->modelo->archivos()->latest()->get(),
        ]);
    }
}
