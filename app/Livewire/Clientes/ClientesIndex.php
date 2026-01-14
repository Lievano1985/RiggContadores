<?php

namespace App\Livewire\Clientes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cliente;
use App\Services\DriveService;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use App\Notifications\DatosAccesoCliente;
use App\Services\BrevoService;



class ClientesIndex extends Component
{
    use WithPagination;
    public BrevoService $brevoService;


    public $modalFormVisible = false;
    public $confirmingDelete = false;
    public $clienteId;

    // Campos del cliente
    public $nombre;
    public $rfc;
    public $correo;
    public $tipo_persona;
    public $telefono;
    public $direccion;
    public $nombre_comercial;
    public $razon_social;
    public $curp;
    public $codigo_postal;
    public $tiene_trabajadores = false;
    public $inicio_obligaciones;
    public $fin_obligaciones;
    public $contrato;
    public $vigencia;
    public $representante_legal;
    public $rfc_representante;
    public $correo_representante;

    // Definir la propiedad para DriveService
    protected $driveService;

    protected $paginationTheme = 'tailwind';

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'rfc' => 'required|string|min:12|max:13',
            'correo' => 'required|email|max:255',
            'tipo_persona' => 'required|in:fisica,moral',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'razon_social' => 'nullable|string|max:255',
            'curp' => $this->tipo_persona === 'fisica'
                ? 'required|string|size:18'
                : 'nullable',
            'codigo_postal' => 'nullable|string|max:10',
            'tiene_trabajadores' => 'boolean',
        ];
    }



    public function __construct()
    {
        // Inyectar el servicio DriveService
        $this->driveService = app(DriveService::class);
    }

    public function render()
    {
        $clientes = Cliente::with('despacho')
            ->withCount([

                // TOTAL obligaciones activas
                'obligacionesAsignadas as total_obligaciones' => function ($q) {
                    $q->where('is_activa', true);
                },

                // Obligaciones activas SIN contador
                'obligacionesAsignadas as obligaciones_pendientes' => function ($q) {

                    $q->where('is_activa', true)
                        ->whereIn('id', function ($sub) {
                            $sub->selectRaw('MAX(id)')
                                ->from('obligacion_cliente_contador')
                                ->groupBy('obligacion_id');
                        })
                        ->where(function ($q2) {
                            $q2->whereNull('contador_id')
                                ->orWhere('contador_id', 0);
                        });
                },


                // TAREAS vigentes SIN contador
                'tareasAsignadas as tareas_pendientes' => function ($q) {

                    $q->whereNotIn('estatus', ['cancelada', 'finalizada'])

                        ->where(function ($q2) {
                            $q2->whereNull('contador_id')
                                ->orWhere('contador_id', 0);
                        });
                }

            ]);

        if (!auth()->user()->hasRole('super_admin')) {
            $clientes->where('despacho_id', auth()->user()->despacho_id);
        }

        $clientes = $clientes->latest()->paginate(10);

        foreach ($clientes as $c) {

            $c->asignaciones_completas =
                $c->total_obligaciones > 0 &&
                $c->obligaciones_pendientes == 0 &&
                $c->tareas_pendientes == 0;
        }
        foreach ($clientes as $c) {

            $c->asignaciones_completas =
                $c->total_obligaciones > 0 &&
                $c->obligaciones_pendientes == 0 &&
                $c->tareas_pendientes == 0;

            // 👇 SOLO llamamos la función
            $c->pendientes_detalle = $this->Tooltip($c);
        }
        return view('livewire.clientes.clientes-index', compact('clientes'));
    }




    public function abrirModalCrear()
    {
        $this->reset([
            'clienteId',
            'nombre',
            'rfc',
            'correo',
            'tipo_persona',
            'telefono',
            'direccion',
            'nombre_comercial',
            'razon_social',
            'curp',
            'codigo_postal',
            'tiene_trabajadores',
            'inicio_obligaciones',
            'fin_obligaciones',
            'contrato',
            'vigencia',
            'representante_legal',
            'rfc_representante',
            'correo_representante'
        ]);
        $this->modalFormVisible = true;
    }

    public function abrirModalEditar($id)
    {
        $cliente = Cliente::findOrFail($id);

        $this->clienteId = $cliente->id;
        $this->nombre = $cliente->nombre;
        $this->rfc = $cliente->rfc;
        $this->correo = $cliente->correo;
        $this->tipo_persona = $cliente->tipo_persona;
        $this->telefono = $cliente->telefono;
        $this->direccion = $cliente->direccion;
        $this->nombre_comercial = $cliente->nombre_comercial;
        $this->razon_social = $cliente->razon_social;
        $this->curp = $cliente->curp;
        $this->codigo_postal = $cliente->codigo_postal;
        $this->tiene_trabajadores = $cliente->tiene_trabajadores;
        $this->inicio_obligaciones = $cliente->inicio_obligaciones;
        $this->fin_obligaciones = $cliente->fin_obligaciones;
        $this->contrato = $cliente->contrato;
        $this->vigencia = $cliente->vigencia;
        $this->representante_legal = $cliente->representante_legal;
        $this->rfc_representante = $cliente->rfc_representante;
        $this->correo_representante = $cliente->correo_representante;

        $this->modalFormVisible = true;
    }

    public function guardar()
    {
        $despacho = auth()->user()->despacho;

        if (!$despacho) {
            $this->addError('despacho_id', 'No se puede asignar el cliente porque el usuario no tiene despacho asignado.');
            return;
        }

        $this->validate();
        // Validación de longitud RFC según tipo de persona
        if ($this->tipo_persona === 'fisica' && strlen($this->rfc) !== 13) {
            $this->addError('rfc', 'El RFC para persona física debe tener 13 caracteres.');
            return;
        }
        if ($this->tipo_persona === 'moral' && strlen($this->rfc) !== 12) {
            $this->addError('rfc', 'El RFC para persona moral debe tener 12 caracteres.');
            return;
        }
        if (!empty($this->curp) && strlen($this->curp) !== 18) {
            $this->addError('curp', 'La CURP debe tener exactamente 18 caracteres.');
            return;
        }

        if (!empty($this->rfc_representante) && strlen($this->rfc_representante) !== 13) {
            $this->addError('rfc_representante', 'El RFC del representante debe tener 13 caracteres.');
            return;
        }
        try {
            $esNuevo = !$this->clienteId;

            $cliente = Cliente::updateOrCreate(
                ['id' => $this->clienteId],
                [
                    'despacho_id' => $despacho->id,
                    'nombre' => $this->nombre,
                    'rfc' => $this->rfc,
                    'correo' => $this->correo,
                    'tipo_persona' => $this->tipo_persona,
                    'telefono' => $this->telefono,
                    'direccion' => $this->direccion,
                    'nombre_comercial' => $this->nombre_comercial,
                    'razon_social' => $this->razon_social,
                    'curp' => $this->curp,
                    'codigo_postal' => $this->codigo_postal,
                    'tiene_trabajadores' => $this->tiene_trabajadores,
                    'inicio_obligaciones' => $this->inicio_obligaciones,
                    'fin_obligaciones' => $this->fin_obligaciones,
                    'contrato' => $this->contrato,
                    'vigencia' => $this->vigencia,
                    'representante_legal' => $this->representante_legal,
                    'rfc_representante' => $this->rfc_representante,
                    'correo_representante' => $this->correo_representante,
                ]
            );

            if ($esNuevo && in_array($despacho->politica_almacenamiento, ['drive_only', 'both']) && $despacho->drive_folder_id) {
                // Crear estructura de carpetas en Drive
                $this->driveService->crearEstructuraCliente(
                    $cliente->id,
                    $cliente->nombre,
                    $despacho->drive_folder_id
                );
            }


            if ($esNuevo && !User::where('email', $this->correo)->exists()) {
                $password = Str::random(10); // Genera una contraseña aleatoria segura

                $user = User::create([
                    'name' => $this->nombre,
                    'email' => $this->correo,
                    'password' => Hash::make($password),
                    'cliente_id' => $cliente->id,
                    'despacho_id' => $despacho->id,
                ]);

                $user->assignRole('cliente');

                // Enviar notificación por correo con los datos de acceso

                app(BrevoService::class)->enviarCredencialesCliente(
                    $user->email,
                    $user->name,
                    $user->email,
                    $password
                );
            }

            session()->flash('message', $this->clienteId ? 'Cliente actualizado.' : 'Cliente creado correctamente.');
            $this->modalFormVisible = false;
            $this->resetPage();
        } catch (\Exception $e) {
            logger()->error('Error al guardar cliente', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            $this->addError('general', 'Ocurrió un error al guardar el cliente. Revisa el log.');
        }
    }

    public function confirmarEliminar($id)
    {
        $this->clienteId = $id;
        $this->confirmingDelete = true;
    }

    public function eliminar()
    {
        $cliente = Cliente::findOrFail($this->clienteId);

        // Eliminar usuario relacionado si existe y tiene rol cliente
        $usuario = User::where('cliente_id', $cliente->id)->first();
        if ($usuario && $usuario->hasRole('cliente')) {
            $usuario->delete();
        }

        // Eliminar el cliente
        $cliente->delete();

        $this->confirmingDelete = false;
        $this->resetPage();

        session()->flash('message', 'Cliente y usuario eliminados correctamente.');
    }

    public function tooltip($cliente)
    {
        return [
            'obligaciones' => $cliente->obligacionesAsignadas()
                ->where('is_activa', true)
                ->where(function ($q) {
                    $q->whereNull('contador_id')
                        ->orWhere('contador_id', 0);
                })
                ->with('obligacion')
                ->get()
                ->pluck('obligacion.nombre')
                ->toArray(),

            'tareas' => $cliente->tareasAsignadas()
                ->whereNotIn('estatus', ['cancelada', 'finalizada'])
                ->where(function ($q) {
                    $q->whereNull('contador_id')
                        ->orWhere('contador_id', 0);
                })
                ->with('tareaCatalogo')
                ->get()
                ->pluck('tareaCatalogo.nombre')
                ->toArray(),
        ];
    }
}
