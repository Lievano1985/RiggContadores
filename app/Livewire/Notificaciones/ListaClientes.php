<?php

namespace App\Livewire\Notificaciones;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cliente;
use App\Services\BrevoService;



class ListaClientes extends Component
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
           

      
        $clientes = $clientes->latest()->paginate(10);

   
        return view('livewire.notificaciones.lista-clientes', compact('clientes'));
    }



    //filtro-------------
    public function updatedBuscar()
    {
        $this->resetPage();
    }
}
