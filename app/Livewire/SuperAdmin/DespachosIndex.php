<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Despacho;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DespachosIndex extends Component
{
    use WithPagination;

    public $despachoId;
    public $confirmingDelete = false;
    public $modalFormVisible = false;
    public $isEdit = false;

    public $nombre, $rfc, $correo_contacto, $telefono_contacto, $drive_folder_id, $politica_almacenamiento;

    public $admin_name;
    public $admin_email;
    public $admin_password;

    protected $listeners = ['cerrarModal' => 'resetForm'];

    public function rules()
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'rfc' => 'required|string|max:13|unique:despachos,rfc,' . $this->despachoId,
            'correo_contacto' => 'nullable|email',
            'telefono_contacto' => 'nullable|string|max:20',
            'drive_folder_id' => 'nullable|string|max:255',
            'politica_almacenamiento' => 'required|in:storage_only,drive_only,both',
        ];

        if (!$this->isEdit) {
            $rules = array_merge($rules, [
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|unique:users,email',
                'admin_password' => 'required|string|min:8',
            ]);
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.super-admin.despachos-index', [
            'despachos' => Despacho::latest()->paginate(10)
        ]);
    }

    public function crear()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->modalFormVisible = true;
    }

    public function editar($id)
    {
        $this->resetForm();
        $this->despachoId = $id;
        $this->isEdit = true;

        $despacho = Despacho::findOrFail($id);
        $this->nombre = $despacho->nombre;
        $this->rfc = $despacho->rfc;
        $this->correo_contacto = $despacho->correo_contacto;
        $this->telefono_contacto = $despacho->telefono_contacto;
        $this->drive_folder_id = $despacho->drive_folder_id;
        $this->politica_almacenamiento = $despacho->politica_almacenamiento;

        $admin = User::where('despacho_id', $despacho->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'admin_despacho'))
            ->first();

        $this->admin_name = $admin?->name;
        $this->admin_email = $admin?->email;

        $this->modalFormVisible = true;
    }

    public function guardar()
    {
        $this->validate();

        $despacho = Despacho::updateOrCreate(
            ['id' => $this->despachoId],
            [
                'nombre' => $this->nombre,
                'rfc' => $this->rfc,
                'correo_contacto' => $this->correo_contacto,
                'telefono_contacto' => $this->telefono_contacto,
                'drive_folder_id' => $this->drive_folder_id,
                'politica_almacenamiento' => $this->politica_almacenamiento,
            ]
        );

        if (!$this->isEdit) {
            $yaExisteAdmin = User::where('despacho_id', $despacho->id)
                ->whereHas('roles', fn($q) => $q->where('name', 'admin_despacho'))
                ->exists();

            if ($yaExisteAdmin) {
                $this->addError('admin_email', 'Este despacho ya tiene un administrador asignado.');
                return;
            }

            $admin = User::create([
                'name' => $this->admin_name,
                'email' => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'despacho_id' => $despacho->id,
            ]);

            $admin->assignRole('admin_despacho');
        }

        $this->resetForm();
    }

    public function confirmarEliminar($id)
    {
        $this->despachoId = $id;
        $this->confirmingDelete = true;
    }

    public function eliminar()
    {
        Despacho::findOrFail($this->despachoId)->delete();
        $this->confirmingDelete = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset([
            'despachoId', 'nombre', 'rfc', 'correo_contacto', 'telefono_contacto',
            'drive_folder_id', 'politica_almacenamiento',
            'admin_name', 'admin_email', 'admin_password',
            'modalFormVisible', 'confirmingDelete', 'isEdit'
        ]);
    }
}
