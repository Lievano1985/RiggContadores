<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\OperationalDashboardBuilder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardSupervisor extends Component
{
    public array $dashboard = [];
    public string $filtroEjercicio = '';
    public string $filtroMes = '';
    public string $filtroContador = '';
    public string $filtroClienteEnvios = '';

    public function mount(OperationalDashboardBuilder $builder): void
    {
        $this->filtroEjercicio = (string) now()->year;
        $this->filtroMes = str_pad((string) now()->month, 2, '0', STR_PAD_LEFT);
        $this->refreshDashboardData($builder);
    }

    public function updatedFiltroEjercicio(OperationalDashboardBuilder $builder): void
    {
        $this->refreshDashboardData($builder);
    }

    public function updatedFiltroMes(OperationalDashboardBuilder $builder): void
    {
        $this->refreshDashboardData($builder);
    }

    public function updatedFiltroContador(OperationalDashboardBuilder $builder): void
    {
        $this->refreshDashboardData($builder);
    }

    public function updatedFiltroClienteEnvios(OperationalDashboardBuilder $builder): void
    {
        $this->refreshDashboardData($builder);
    }

    protected function refreshDashboardData(OperationalDashboardBuilder $builder): void
    {
        $this->dashboard = $builder->build(Auth::user(), 'Supervisor', [
            'ejercicio' => $this->filtroEjercicio,
            'mes' => $this->filtroMes,
            'contador_id' => $this->filtroContador,
            'cliente_envio_id' => $this->filtroClienteEnvios,
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-operativo');
    }
}
