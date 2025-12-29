<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Obligacion;
use Livewire\Component;
use App\Services\GeneradorObligaciones;

class RegularizacionObligaciones extends Component
{
    public Cliente $cliente;

    public array $obligacionesSeleccionadas = [];
    public int $mes = 1;
    public int $anio;
    public array $resumen = [];
    protected $listeners = [];
    protected GeneradorObligaciones $generador;
    public string $buscarObligacion = '';

    public function boot(GeneradorObligaciones $generador)
    {
        $this->generador = $generador;
    }

    public function mount()
    {
        $this->anio = now()->year;
        $this->mes = now()->month;
    }

    public function updated($property)
    {
        $this->reset('resumen');
    }

    public function generar()
    {
        $this->validate([
            'obligacionesSeleccionadas' => 'required|array|min:1',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020|max:' . (now()->year + 1),
        ]);
        if ($this->anio >= now()->year && $this->mes >= now()->month) {
            $this->addError('mes', 'Solo se pueden generar obligaciones para meses pasados.');
            return;
        }


        $resultado = $this->generador->generarManualClienteObligaciones(
            $this->cliente,
            $this->obligacionesSeleccionadas,
            $this->anio,
            $this->mes
        );

        $this->resumen = $resultado;

        $this->dispatch('obligacionActualizada'); // por si otro tab depende de ello
    }

    public function render()
    {
        return view('livewire.clientes.regularizacion-obligaciones', [
            'obligacionesFiltradas' => Obligacion::query()
                ->where('activa', true)
                ->when($this->buscarObligacion, fn ($q) =>
                    $q->where('nombre', 'like', '%' . $this->buscarObligacion . '%')
                )
                ->orderBy('nombre')
                ->get(),
        ]);
    }
    



    public function getMesesDisponiblesProperty(): array
    {
        $mesActual = now()->month;
        $anioActual = now()->year;

        if ($this->anio < $anioActual) {
            return range(1, 12);
        }

        if ($this->anio === $anioActual) {
            return range(1, $mesActual - 1); // Solo meses pasados
        }

        return []; // Año futuro: no permitir nada
    }
    public function updatedAnio()
    {
        if (!in_array($this->mes, $this->mesesDisponibles)) {
            $this->mes = $this->mesesDisponibles[0] ?? null;
        }

        $this->reset('resumen');
    }

    public function getObligacionesFiltradasProperty()
{
    return Obligacion::query()
        ->where('activa', true)
        ->when($this->buscarObligacion, function ($query) {
            $query->where('nombre', 'like', '%' . $this->buscarObligacion . '%');
        })
        ->orderBy('nombre')
        ->get();
}

}
