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

        foreach ($this->nuevosArchivos as $item) {

            $nombreFinal = now()->format('Ymd_His') . '_' . $item['file']->getClientOriginalName();

            $rutaStorage = $item['file']->storeAs(
                'adjuntos',
                $nombreFinal,
                'public'
            );

            $this->modelo->archivos()->create([
                'nombre' => $item['nombre'],
                'archivo' => $rutaStorage,
                'archivo_drive_url' => null, // Drive se puede integrar después
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
