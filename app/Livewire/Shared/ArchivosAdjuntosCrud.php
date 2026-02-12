<?php

/**
 * Componente Livewire: ArchivosAdjuntosCrud
 * Autor: Luis Liévano - JL3 Digital
 */

namespace App\Livewire\Shared;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\DriveService;
use App\Models\CarpetaDrive;
use Livewire\Attributes\On;

class ArchivosAdjuntosCrud extends Component
{
    use WithFileUploads;

    public Model $modelo;
    public array $nuevosArchivos = [];
    public ?string $origen = null;

    protected function rules()
    {
        return [
            'nuevosArchivos' => ['array'],
            'nuevosArchivos.*.nombre' => ['required','string','max:255'],
            'nuevosArchivos.*.file' => [
                'required','file',
                'mimes:pdf,zip,jpg,jpeg,png',
                'max:10240'
            ],
        ];
    }

    public function mount(): void
    {
        $this->resetFormulario();
    }

    private function resetFormulario(): void
    {
        $this->reset(['nuevosArchivos']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function agregarArchivo(): void
    {
        $this->nuevosArchivos[] = ['nombre'=>'','file'=>null];
    }

    public function quitarArchivo(int $index): void
    {
        unset($this->nuevosArchivos[$index]);
        $this->nuevosArchivos = array_values($this->nuevosArchivos);
    }

    public function eliminarArchivo(int $archivoId): void
    {
        $archivo = $this->modelo->archivos()->findOrFail($archivoId);

        if ($archivo->archivo) {
            Storage::disk('public')->delete($archivo->archivo);
        }

        $archivo->delete();
        $this->resetFormulario();

        $this->dispatch('notify', message:'Archivo eliminado');
    }

    /* ==========================================
       EVENTO DESDE PADRE
    ========================================== */
    #[On('guardar-archivos-adjuntos')]
    public function ejecutarDesdePadre($origen = null)
    {
        $this->origen = $origen;
    
        try {
    
            $this->js("window.dispatchEvent(new CustomEvent('spinner-on'))");
    
            $this->guardar();
    
            // devolver según origen
            $this->dispatch("archivos-ok-$origen");
    
        } catch (\Throwable $e) {
    
/*             $this->reset('nuevosArchivos');
 */            $this->resetErrorBag();
            $this->resetValidation();
    
            $this->dispatch("archivos-error-$origen");
    
        } finally {
    
            $this->js("window.dispatchEvent(new CustomEvent('spinner-off'))");
        }
    }
    
    


    public function guardar(): void
    {
        $this->validate();
    
        $modelo = $this->modelo;
        $cliente = $modelo->cliente ?? $modelo->obligacionClienteContador?->cliente;
        $despacho = $cliente->despacho;
        $politica = $despacho->politica_almacenamiento;
    
        foreach ($this->nuevosArchivos as $item) {
    
            if ($this->modelo->archivos()
                ->where('nombre', $item['nombre'])
                ->exists()) {
                $this->addError(
                    'nuevosArchivos',
                    "Ya existe '{$item['nombre']}'"
                );
                throw new \Exception('Duplicado');
            }
    
            $extension = $item['file']->getClientOriginalExtension();
    
            /* ===============================
               NOMBRE FINAL DEL ARCHIVO
               mm-aa-rfc-nombre-manual-ss.ext
            =============================== */
       /*      $fecha = now();
    
            $mm = $fecha->format('m');
            $aa = $fecha->format('y');
            $ss = $fecha->format('s');
     */

            $mesPeriodo = str_pad($modelo->mes ?? now()->month, 2, '0', STR_PAD_LEFT);
            $anioPeriodo = substr((string)($modelo->ejercicio ?? now()->year), -2);
            
            $mm = $mesPeriodo;
            $aa = $anioPeriodo;
            $ss = now()->format('s'); 
            $rfc = \Str::upper($cliente->rfc);
            $nombreManual = \Str::slug($item['nombre'], '-');
    
            $nombreFinal = "{$mm}-{$aa}-{$rfc}-{$nombreManual}-{$ss}.{$extension}";
            /* =============================== */
    
            $rutaStorage = null;
            $urlDrive = null;
    
            if (in_array($politica, ['storage_only', 'both'])) {
                $rutaStorage = $item['file']->storeAs(
                    'adjuntos',
                    $nombreFinal,
                    'public'
                );
            }
    
            if (in_array($politica, ['drive_only', 'both'])) {
    
                $folderId = null;
    
                if ($modelo->carpeta_drive_id) {
                    $cd = CarpetaDrive::find($modelo->carpeta_drive_id);
                    $folderId = $cd?->drive_folder_id;
                }
    
                if ($folderId) {
                    $drive = app(DriveService::class);
                    $res = $drive->subirArchivo(
                        $nombreFinal,
                        $item['file'],
                        $folderId,
                        $item['file']->getMimeType()
                    );
    
                    $urlDrive = is_array($res)
                        ? ($res['webViewLink'] ?? null)
                        : $res;
                }
            }
    
            $this->modelo->archivos()->create([
                'nombre' => $nombreFinal, // 👈 guardamos el nombre real
                'archivo' => $rutaStorage,
                'archivo_drive_url' => $urlDrive,
            ]);
            
        }
    
        $this->resetFormulario();
    }
    
    public function render()
    {
        return view('livewire.shared.archivos-adjuntos-crud',[
            'archivos'=>$this->modelo->archivos()->latest()->get(),
        ]);
    }
}
