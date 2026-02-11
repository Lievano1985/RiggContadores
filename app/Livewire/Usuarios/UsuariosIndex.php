<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use App\Models\Despacho;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsuariosIndex extends Component
{
    use WithPagination;

    public $modalFormVisible = false;
    public $modoEdicion = false;
    public $usuario;

    public $name, $email, $password, $rol, $despacho_id;
    public $roles = [], $despachos = [];

    protected $paginationTheme = 'tailwind';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($this->modoEdicion ? ',' . $this->usuario->id : ''),
            'rol' => 'required|string',
            'password' => $this->modoEdicion ? 'nullable|min:6' : 'required|min:6',
        ];
    }

    public function mount()
    {
        if (auth()->user()->hasRole('super_admin')) {
            $this->roles = Role::pluck('name')->toArray();
            $this->despachos = Despacho::all();
        } elseif (auth()->user()->hasRole('admin_despacho')) {
            $this->roles = ['contador', 'supervisor'];
            $this->despacho_id = auth()->user()->despacho_id;
        }
    }

    public function render()
    {
        $query = User::with('roles', 'despacho');

        if (auth()->user()->hasRole('admin_despacho')) {
            $query->whereHas('roles', fn($q) => $q->wherein('name', ['contador', 'supervisor']))
                ->where('despacho_id', auth()->user()->despacho_id);
        }

        $usuarios = $query->paginate(10);

        return view('livewire.usuarios.usuarios-index', compact('usuarios'));
    }

    public function crear()
    {
        $this->resetForm();
        $this->modoEdicion = false;
        $this->modalFormVisible = true;
    }

    public function editar($id)
    {
        $usuario = User::findOrFail($id);

        // Validación extra: si es admin_despacho solo puede editar contadores de su despacho
        // Validación extra: si es admin_despacho solo puede editar contadores de su despacho
        if (
            auth()->user()->hasRole('admin_despacho') &&
            (
                $usuario->despacho_id !== auth()->user()->despacho_id ||
                !$usuario->hasAnyRole(['contador', 'supervisor']) // Corrección aquí
            )
        ) {
            abort(403, 'No tienes permiso para editar este usuario.');
        }

        $this->usuario = $usuario;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->rol = $usuario->roles->first()->name ?? '';
        $this->despacho_id = $usuario->despacho_id;
        $this->password = '';
        $this->modoEdicion = true;
        $this->modalFormVisible = true;
    }

    public function guardar()
    {
        $this->validate();

        // Asignar automáticamente el despacho si es admin_despacho
        if (auth()->user()->hasRole('admin_despacho')) {
            $this->despacho_id = auth()->user()->despacho_id;
        }

        if ($this->modoEdicion) {
            $this->usuario->update([
                'name' => $this->name,
                'email' => $this->email,
                'despacho_id' => $this->despacho_id,
                'password' => $this->password ? bcrypt($this->password) : $this->usuario->password,
            ]);
            $this->usuario->syncRoles([$this->rol]);
        } else {
            $nuevo = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => bcrypt($this->password),
                'despacho_id' => $this->despacho_id,
            ]);
            $nuevo->assignRole($this->rol);
        }

        $this->modalFormVisible = false;
        $this->resetForm();
    }

    public function eliminar($id)
    {
        $usuario = User::findOrFail($id);

        // Validación: admin_despacho solo puede eliminar contadores de su despacho
        if (
            auth()->user()->hasRole('admin_despacho') &&
            ($usuario->despacho_id !== auth()->user()->despacho_id || !$usuario->hasRole('contador'))
        ) {
            abort(403, 'No tienes permiso para eliminar este usuario.');
        }

        $usuario->delete();
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'password', 'rol', 'usuario']);

        if (auth()->user()->hasRole('admin_despacho')) {
            $this->despacho_id = auth()->user()->despacho_id;
        } else {
            $this->despacho_id = null;
        }
    }
}
