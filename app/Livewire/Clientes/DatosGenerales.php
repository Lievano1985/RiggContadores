<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\User;

use App\Models\CarpetaDrive;
use App\Services\DriveService;
use Illuminate\Support\Facades\Hash;
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
    public $correo_usuario_cliente;
    public $nuevo_password_cliente, $nuevo_password_cliente_confirmation;

    public function mount(Cliente $cliente)
    {
        $this->cliente = $cliente;
        $this->fill($cliente->toArray());

        $this->tiene_trabajadores = $cliente->tiene_trabajadores ? 1 : 0;
        $this->inicio_obligaciones = optional($cliente->inicio_obligaciones)->toDateString();
        $this->fin_obligaciones = optional($cliente->fin_obligaciones)->toDateString();
        $this->vigencia = optional($cliente->vigencia)->toDateString();
        $this->correo_usuario_cliente = $cliente->usuario?->email ?? $cliente->correo;
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

        $this->correo_usuario_cliente = $this->correo;

        if ($this->archivoContrato) {
            $this->procesarArchivoContrato();
        }

        $this->modoEdicion = false;
        $this->modoKey++; // fuerza repintado del switch

        $this->dispatch('notify', message: 'Datos generales actualizados correctamente.');
        $this->dispatch('DatosFiscalesActualizados');
    }

    public function resetearPasswordCliente(): void
    {
        if (! $this->puedeResetearPassword()) {
            abort(403);
        }

        $usuario = User::where('cliente_id', $this->cliente->id)->first();

        if (! $usuario) {
            $this->dispatch('notify', message: 'Este cliente no tiene un usuario de acceso creado.');
            return;
        }

        $this->sincronizarCorreoClienteYUsuario($usuario);

        $this->validate([
            'nuevo_password_cliente' => 'required|string|min:8|confirmed',
        ], [], [
            'nuevo_password_cliente' => 'nueva contrasena',
        ]);

        $usuario->update([
            'password' => Hash::make($this->nuevo_password_cliente),
        ]);

        $this->reset('nuevo_password_cliente', 'nuevo_password_cliente_confirmation');

        $this->dispatch('notify', message: 'La contrasena del cliente se actualizo correctamente.');
    }

    public function actualizarCorreoUsuarioCliente(): void
    {
        if (! $this->puedeResetearPassword()) {
            abort(403);
        }

        $usuario = User::where('cliente_id', $this->cliente->id)->first();

        if (! $usuario) {
            $this->dispatch('notify', message: 'Este cliente no tiene un usuario creado.');
            return;
        }

        $this->validate([
            'correo_usuario_cliente' => 'required|email',
        ], [], [
            'correo_usuario_cliente' => 'correo del usuario',
        ]);

        $emailEnUso = User::query()
            ->where('email', $this->correo_usuario_cliente)
            ->where('id', '!=', $usuario->id)
            ->exists();

        if ($emailEnUso) {
            $this->addError('correo_usuario_cliente', 'Ya existe otro usuario con ese correo.');
            return;
        }

        $usuario->update([
            'email' => $this->correo_usuario_cliente,
        ]);

        $this->cliente->update([
            'correo' => $this->correo_usuario_cliente,
        ]);

        $this->cliente->refresh();
        $this->correo = $this->correo_usuario_cliente;

        $this->dispatch('notify', message: 'El correo del cliente y su usuario se actualizaron correctamente.');
    }

    public function crearAccesoCliente(): void
    {
        if (! $this->puedeResetearPassword()) {
            abort(403);
        }

        $usuarioExistente = User::where('cliente_id', $this->cliente->id)->first();

        if ($usuarioExistente) {
            $this->dispatch('notify', message: 'Este cliente ya tiene un usuario de acceso creado.');
            return;
        }

        if (blank($this->correo)) {
            $this->addError('nuevo_password_cliente', 'El cliente no tiene correo definido para crear el acceso.');
            return;
        }

        $emailEnUso = User::where('email', $this->correo)->exists();

        if ($emailEnUso) {
            $this->addError('nuevo_password_cliente', 'Ya existe otro usuario con ese correo. Actualiza el correo del cliente o usa otro.');
            return;
        }

        $this->validate([
            'nuevo_password_cliente' => 'required|string|min:8|confirmed',
        ], [], [
            'nuevo_password_cliente' => 'nueva contrasena',
        ]);

        $usuario = User::create([
            'name' => $this->cliente->nombre,
            'email' => $this->correo,
            'password' => Hash::make($this->nuevo_password_cliente),
            'cliente_id' => $this->cliente->id,
            'despacho_id' => $this->cliente->despacho_id,
        ]);

        $usuario->assignRole('cliente');

        $this->cliente->update([
            'correo' => $this->correo,
        ]);

        $this->cliente->refresh();
        $this->correo_usuario_cliente = $this->correo;

        $this->reset('nuevo_password_cliente', 'nuevo_password_cliente_confirmation');

        $this->dispatch('notify', message: 'El usuario del cliente se creo correctamente.');
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

        // CURP obligatorio solo para persona física
        if ($this->tipo_persona === 'fisica' && strlen($this->curp) !== 18) {
            throw ValidationException::withMessages([
                'curp' => 'La CURP para persona física debe tener exactamente 18 caracteres.',
            ]);
        }


        if (!empty($this->rfc_representante) && strlen($this->rfc_representante) !== 13) {
            throw ValidationException::withMessages([
                'rfc_representante' => 'El RFC del representante debe tener 13 caracteres.',
            ]);
        }
    }
    public function updatedTipoPersona($value)
    {
        if ($value === 'moral') {
            $this->curp = null;
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
        return view('livewire.clientes.datos-generales', [
            'usuarioCliente' => User::where('cliente_id', $this->cliente->id)->first(),
            'puedeResetearPassword' => $this->puedeResetearPassword(),
        ]);
    }

    private function puedeResetearPassword(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin_despacho', 'super_admin']);
    }

    private function sincronizarCorreoClienteYUsuario(User $usuario): void
    {
        $this->validate([
            'correo' => 'required|email',
        ], [], [
            'correo' => 'correo',
        ]);

        $emailEnUso = User::query()
            ->where('email', $this->correo)
            ->where('id', '!=', $usuario->id)
            ->exists();

        if ($emailEnUso) {
            throw ValidationException::withMessages([
                'correo' => 'Ya existe otro usuario con ese correo.',
            ]);
        }

        $usuario->update([
            'email' => $this->correo,
            'name' => $this->nombre,
        ]);

        $this->cliente->update([
            'correo' => $this->correo,
            'nombre' => $this->nombre,
        ]);

        $this->cliente->refresh();
        $this->correo_usuario_cliente = $this->correo;
    }
}
