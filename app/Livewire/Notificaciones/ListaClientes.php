<?php

namespace App\Livewire\Notificaciones;

use App\Livewire\Shared\HasPerPage;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cliente;
use App\Services\BrevoService;



class ListaClientes extends Component
{
    use WithPagination, HasPerPage;
    public BrevoService $brevoService;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';


    public $modalFormVisible = false;
    public $confirmingDelete = false;
    public $clienteId;

    // Campos del cliente
    public $nombre;
    public $rfc;
    public $correo;
    public $tipo_persona;
    public $telefono;

 

    // Filtro de busqueda 
    public $buscar = '';


    protected $paginationTheme = 'tailwind';


    public function render()
    {
        $clientes = Cliente::with('despacho')
            ->when($this->buscar, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('nombre', 'like', '%' . $this->buscar . '%')
                        ->orWhere('rfc', 'like', '%' . $this->buscar . '%')
                        ->orWhere('razon_social', 'like', '%' . $this->buscar . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $this->buscar . '%');
                });
            });

        if (in_array($this->sortField, ['nombre', 'rfc', 'tipo_persona', 'created_at'], true)) {
            $clientes->orderBy($this->sortField, $this->sortDirection);
        } else {
            $clientes->latest();
        }

        $clientes = $clientes->paginate($this->perPageValue($clientes, 10));

   
        return view('livewire.notificaciones.lista-clientes', compact('clientes'));
    }



    //filtro-------------
    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['nombre', 'rfc', 'tipo_persona'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }
}
