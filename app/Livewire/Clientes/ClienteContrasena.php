<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Contrasena;
use App\Models\CarpetaDrive;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Services\DriveService;

class ClienteContrasena extends Component
{
    use WithFileUploads;

    public $clienteId;
    public $contrasenas = [];
    public $cliente;
    public $form = [
        'id' => null,
        'portal' => '',
        'url' => '',
        'usuario' => '',
        'correo' => '',
        'contrasena' => '',
        'vencimiento' => '',
        'archivo_certificado' => null,
        'archivo_clave' => null,
        'destino_archivo' => null,

    ];

    public $archivoCertificado;
    public $archivoClave;
    public $modalFormVisible = false;
    public $isEditing = false;

    protected $rules = [
        'form.portal' => 'required|string|max:255',
        'form.contrasena' => 'required|string|max:255',
        'archivoCertificado' => 'nullable|file|mimes:cer',
        'archivoClave' => 'nullable|file|mimes:key',
        'form.destino_archivo' => 'required|string|in:1-fiel,2-csd,3-imss,otros',

    ];

    protected DriveService $driveService;

    public function boot(DriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function mount(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->clienteId = $cliente->id;
    }

    public function loadContrasenas()
    {
        $this->contrasenas = Contrasena::where('cliente_id', $this->clienteId)->get();
    }

    public function crear()
    {
        $this->reset('form', 'archivoCertificado', 'archivoClave');
        $this->form['id'] = null;
        $this->isEditing = false;
        $this->modalFormVisible = true;
    }

    public function editar($id)
    {
        $registro = Contrasena::findOrFail($id);
        $this->form = $registro->toArray();
        $this->isEditing = true;
        $this->modalFormVisible = true;
    }

    public function guardar()
    {
        $this->validate();

        $politica = $this->cliente->despacho->politica_almacenamiento;
        $rutaCertificado = null;
        $rutaClave = null;

        // 📁 Cargar carpetas del cliente para "2-Firmas electrónicas"
        $carpetaFirmas = CarpetaDrive::where('cliente_id', $this->clienteId)
            ->where('nombre', '2-Firmas electrónicas')
            ->first();

        // Laravel Storage: subir archivos y guardar solo ruta relativa en BD
        if (in_array($politica, ['storage_only', 'both'])) {
            if ($this->archivoCertificado) {
                $rutaCertificado = $this->archivoCertificado->store("clientes/{$this->clienteId}/certificados", 'public');
                $this->form['archivo_certificado'] = $rutaCertificado; // guardar solo la ruta
            }

            if ($this->archivoClave) {
                $rutaClave = $this->archivoClave->store("clientes/{$this->clienteId}/certificados", 'public');
                $this->form['archivo_clave'] = $rutaClave;
            }
        }

        // Google Drive: subir pero NO sobrescribir lo guardado en BD
        $destino = $this->form['destino_archivo'];

        if (in_array($politica, ['drive_only', 'both']) && $carpetaFirmas) {
            if ($this->archivoCertificado || $this->archivoClave) {

                // Obtener carpeta destino desde BD si es una de las 3 fijas
                if (in_array($destino, ['1-fiel', '2-csd', '3-imss'])) {
                    $carpetaDestino = CarpetaDrive::where('cliente_id', $this->clienteId)
                        ->where('nombre', $destino)
                        ->first();

                    $carpetaPortalId = $carpetaDestino?->drive_folder_id;
                } else {
                    // Si es 'otros', crear subcarpeta con nombre del portal dentro de "2-Firmas electrónicas"
                    $carpetaPortalId = $this->driveService->crearCarpeta(
                        $this->form['portal'],
                        $carpetaFirmas->drive_folder_id
                    );

                    // Registrar en BD si no existe
                    CarpetaDrive::firstOrCreate([
                        'cliente_id' => $this->clienteId,
                        'nombre' => $this->form['portal'],
                        'tipo' => 'subcarpeta',
                    ], [
                        'drive_folder_id' => $carpetaPortalId,
                    ]);
                }

                if ($carpetaPortalId) {
                    // Subir certificado
                    if ($this->archivoCertificado) {
                        $this->driveService->subirArchivo(
                            $this->archivoCertificado->getClientOriginalName(),
                            $this->archivoCertificado,
                            $carpetaPortalId,
                            $this->archivoCertificado->getMimeType()
                        );
                    }

                    // Subir clave
                    if ($this->archivoClave) {
                        $this->driveService->subirArchivo(
                            $this->archivoClave->getClientOriginalName(),
                            $this->archivoClave,
                            $carpetaPortalId,
                            $this->archivoClave->getMimeType()
                        );
                    }
                }
            }
        }



        $this->form['cliente_id'] = $this->clienteId;
        $this->form['vencimiento'] = $this->form['vencimiento'] ?: null;

        if ($this->isEditing && $this->form['id']) {
            Contrasena::findOrFail($this->form['id'])->update($this->form);
        } else {
            Contrasena::create($this->form);
        }

        $this->modalFormVisible = false;
        $this->loadContrasenas();
        session()->flash('message', 'Contraseña guardada exitosamente.');
    }


    public function eliminar($id)
    {
        Contrasena::findOrFail($id)->delete();
        $this->loadContrasenas();
    }

    public function render()
    {
        if (empty($this->contrasenas)) {
            $this->loadContrasenas();
        }

        return view('livewire.clientes.cliente-contrasena');
    }
}
