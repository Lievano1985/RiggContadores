<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\User;

use App\Models\CarpetaDrive;
use App\Services\DriveService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class DatosGenerales extends Component
{
    use WithFileUploads;

    public Cliente $cliente;
    public bool $modoEdicion = false;
    public $archivoContrato;
    public int $modoKey = 0;

    // Campos editables
    public $nombre, $razon_social, $rfc, $curp, $correo, $telefono, $direccion;
    public $nombre_comercial, $codigo_postal, $tipo_persona, $tiene_trabajadores;
    public $inicio_obligaciones, $fin_obligaciones, $contrato, $vigencia;
    public $representante_legal, $rfc_representante, $correo_representante;

    public function mount(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->fill($cliente->toArray());

        $this->tiene_trabajadores = $cliente->tiene_trabajadores ? 1 : 0;
        $this->inicio_obligaciones = optional($cliente->inicio_obligaciones)->toDateString();
        $this->fin_obligaciones = optional($cliente->fin_obligaciones)->toDateString();
        $this->vigencia = optional($cliente->vigencia)->toDateString();
    }

    public function guardar()
    {
        $this->validaciones();
    
        $this->cliente->update([
            'nombre' => $this->nombre,
            'rfc' => $this->rfc,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'nombre_comercial' => $this->nombre_comercial,
            'razon_social' => $this->razon_social,
            'curp' => $this->curp,
            'codigo_postal' => $this->codigo_postal,
            'tipo_persona' => $this->tipo_persona,
            'tiene_trabajadores' => $this->tiene_trabajadores,
            'inicio_obligaciones' => $this->inicio_obligaciones,
            'fin_obligaciones' => $this->fin_obligaciones,
            'contrato' => $this->contrato,
            'vigencia' => $this->vigencia,
            'representante_legal' => $this->representante_legal,
            'rfc_representante' => $this->rfc_representante,
            'correo_representante' => $this->correo_representante,
        ]);
    
        //  Sincronizar datos en el usuario si existe
        $user = User::where('cliente_id', $this->cliente->id)->first();
        if ($user) {
            $user->update([
                'name' => $this->nombre,
                'email' => $this->correo,
            ]);
        }
    
        if ($this->archivoContrato) {
            $this->procesarArchivoContrato();
        }
    
        $this->modoEdicion = false;
        $this->modoKey++; // fuerza repintado del switch

        session()->flash('message', 'Datos generales actualizados correctamente.');
        $this->dispatch('DatosFiscalesActualizados');

    }
    public function updatedClienteTieneTrabajadores($value)
{
    $this->cliente->tiene_trabajadores = $value;
    $this->loadObligacionesDisponibles();

    // Calcular obligaciones válidas y huérfanas
    $allowed = $this->obligacionesDisponibles->pluck('id')->toArray();
    $orphan  = array_diff($this->obligacionesSeleccionadas, $allowed);

    if (!empty($orphan)) {
        // 1. Eliminar relación cliente-obligación
        $this->cliente->obligaciones()->detach($orphan);

        // 2. Obtener asignaciones afectadas
        $asignaciones = \App\Models\ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->whereIn('obligacion_id', $orphan)
            ->get();

        // 3. Eliminar tareas y luego asignaciones
        foreach ($asignaciones as $asignacion) {
            \App\Models\TareaAsignada::where('obligacion_cliente_contador_id', $asignacion->id)->delete();
            $asignacion->delete();
        }

        // 4. Actualizar lista local
        $this->obligacionesSeleccionadas = array_values(
            array_intersect($this->obligacionesSeleccionadas, $allowed)
        );

        // 5. Disparar evento Livewire
        $this->dispatch('obligacionActualizada');
    }
}


    public function validaciones()
    {
        $this->validate([
            'razon_social' => 'required|string|max:255',
            'rfc' => 'required|string|max:13',
            'correo' => 'required|email',
            'tipo_persona' => 'required|in:fisica,moral',
            'vigencia' => 'nullable|date',
            'inicio_obligaciones' => 'nullable|date',
            'fin_obligaciones' => 'nullable|date',
            'archivoContrato' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Validaciones personalizadas
        if ($this->tipo_persona === 'fisica' && strlen($this->rfc) !== 13) {
            throw ValidationException::withMessages([
                'rfc' => 'El RFC para persona física debe tener 13 caracteres.',
            ]);
        }

        if ($this->tipo_persona === 'moral' && strlen($this->rfc) !== 12) {
            throw ValidationException::withMessages([
                'rfc' => 'El RFC para persona moral debe tener 12 caracteres.',
            ]);
        }

        if (!empty($this->curp) && strlen($this->curp) !== 18) {
            throw ValidationException::withMessages([
                'curp' => 'La CURP debe tener exactamente 18 caracteres.',
            ]);
        }

        if (!empty($this->rfc_representante) && strlen($this->rfc_representante) !== 13) {
            throw ValidationException::withMessages([
                'rfc_representante' => 'El RFC del representante debe tener 13 caracteres.',
            ]);
        }
    }

    public function procesarArchivoContrato()
    {
        $despacho = $this->cliente->despacho;
        $nombreArchivo = Str::slug($this->cliente->nombre) . '-contrato.' . $this->archivoContrato->getClientOriginalExtension();

        // Google Drive
        if (in_array($despacho->politica_almacenamiento, ['drive_only', 'both'])) {
            $subcarpeta = CarpetaDrive::where('cliente_id', $this->cliente->id)
                ->where('nombre', '3-Generales')
                ->first();

            if ($subcarpeta) {
                try {
                    $driveService = app(DriveService::class);
                    $driveService->subirArchivo(
                        $nombreArchivo,
                        file_get_contents($this->archivoContrato->getRealPath()),
                        $subcarpeta->drive_folder_id,
                        $this->archivoContrato->getMimeType()
                    );
                } catch (\Exception $e) {
                    $this->addError('contrato', 'Error al subir el contrato a Drive: ' . $e->getMessage());
                    \Log::error('Error al subir el contrato a Drive: ' . $e->getMessage());
                }
            } else {
                $this->addError('contrato', 'No se encontró la carpeta "3-Generales" en Google Drive.');
            }
        }

        // Laravel Storage
        if (in_array($despacho->politica_almacenamiento, ['storage_only', 'both'])) {
            if ($this->cliente->contrato && Storage::disk('public')->exists($this->cliente->contrato)) {
                Storage::disk('public')->delete($this->cliente->contrato);
            }

            $path = $this->archivoContrato->storeAs(
                "clientes/{$this->cliente->id}/contratos",
                $nombreArchivo,
                'public'
            );

            $this->cliente->update(['contrato' => $path]);
        }

        $this->archivoContrato = null;
    }


    public function render()
    {
        return view('livewire.clientes.datos-generales');
    }
}
