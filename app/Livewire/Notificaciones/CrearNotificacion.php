<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * Componente: CrearNotificacion
 * Función:
 * Permite al administrador crear una notificación al cliente
 * seleccionando obligaciones del periodo y adjuntando sus archivos.
 */

namespace App\Livewire\Notificaciones;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

use App\Models\NotificacionCliente;
use App\Models\ObligacionClienteContador;
use App\Models\ArchivoAdjunto;

class CrearNotificacion extends Component
{
    public $cliente;

    public $periodo_mes;
    public $periodo_ejercicio;

    public $asunto = '';
    public $mensaje = '';

    public $obligacionesDisponibles = [];
    public $obligacionesSeleccionadas = [];

    public $archivosSeleccionados = [];
    public $buscarObligacion = '';
    public $obligacionesFiltradas = [];

    public $ejerciciosDisponibles = [];

    public $mesesManual = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];
    // ============================
    // Inicialización
    // ============================

    public function mount($cliente)
    {
        $this->cliente = $cliente;
        /* 
        $this->periodo_mes = now()->month;
        $this->periodo_ejercicio = now()->year; */
        $this->cargarEjerciciosDisponibles();   // 👈 nuevo

        $this->cargarObligaciones();
    }

    private function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles =
            ObligacionClienteContador::query()
            ->where('cliente_id', $this->cliente->id)
            ->whereNotNull('ejercicio')
            ->pluck('ejercicio')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }



    public function updatedPeriodoEjercicio()
    {
        $this->periodo_mes = '';
        $this->cargarObligaciones();
        $this->obligacionesSeleccionadas = [];
        $this->archivosSeleccionados = [];
    }


    // ============================
    // Cargar obligaciones
    // ============================

    public function cargarObligaciones()
    {
        // Solo cuando ambos combos tengan valor
        if (empty($this->periodo_ejercicio) || empty($this->periodo_mes)) {
            $this->obligacionesDisponibles = [];
            $this->obligacionesFiltradas = [];
            return;
        }

        $query = ObligacionClienteContador::where('cliente_id', $this->cliente->id)
            ->whereIn('estatus', ['finalizado', 'enviada_cliente'])
            ->where('ejercicio', (int) $this->periodo_ejercicio)
            ->where('mes', (int) $this->periodo_mes)
            ->with('obligacion');

        $this->obligacionesDisponibles = $query->get();

        // Inicialmente todas
        $this->obligacionesFiltradas = $this->obligacionesDisponibles;
    }


    public function updatedBuscarObligacion()
    {
        $texto = mb_strtolower($this->buscarObligacion);

        $this->obligacionesFiltradas =
            $this->obligacionesDisponibles->filter(function ($oc) use ($texto) {
                return str_contains(
                    mb_strtolower($oc->obligacion->nombre ?? ''),
                    $texto
                );
            });
    }
    public function quitarObligacion($id)
    {
        $this->obligacionesSeleccionadas =
            array_values(
                array_diff($this->obligacionesSeleccionadas, [$id])
            );

        $this->updatedObligacionesSeleccionadas();
    }



    // ============================
    // Cuando cambia periodo
    // ============================

    public function updatedPeriodoMes()
    {
        $this->cargarObligaciones();
        $this->obligacionesSeleccionadas = [];
        $this->archivosSeleccionados = [];
    }



    // ============================
    // Cuando se seleccionan obligaciones
    // ============================

    public function updatedObligacionesSeleccionadas()
    {
        $this->archivosSeleccionados = [];

        foreach ($this->obligacionesSeleccionadas as $obligacionId) {
            $archivos = ArchivoAdjunto::where('archivoable_type', ObligacionClienteContador::class)
                ->where('archivoable_id', $obligacionId)
                ->get();


            foreach ($archivos as $archivo) {
                $this->archivosSeleccionados[] = $archivo;
            }
        }
    }

    // ============================
    // Guardar notificación
    // ============================

    public function guardar()
    {
        $this->validate([
            'asunto' => 'required',
            'mensaje' => 'required',
            'obligacionesSeleccionadas' => 'required|array|min:1',
        ]);

        $notificacion = NotificacionCliente::create([
            'cliente_id' => $this->cliente->id,
            'user_id' => Auth::id(),
            'asunto' => $this->asunto,
            'mensaje' => $this->mensaje,
            'periodo_mes' => $this->periodo_mes,
            'periodo_ejercicio' => $this->periodo_ejercicio,
        ]);

        // Guardar obligaciones
        $notificacion->obligaciones()
            ->sync($this->obligacionesSeleccionadas);

        // Guardar archivos
        $notificacion->archivos()
            ->sync(collect($this->archivosSeleccionados)->pluck('id'));

        // Marcar obligaciones como enviadas
        ObligacionClienteContador::whereIn('id', $this->obligacionesSeleccionadas)
            ->update(['estatus' => 'enviada_cliente']);

        // Limpieza
        $this->asunto = '';
        $this->mensaje = '';
        $this->obligacionesSeleccionadas = [];
        $this->archivosSeleccionados = [];

        $this->dispatch(
            'notify',
            message: 'Notificación registrada correctamente'
        );
    }

    // ============================
    // Render
    // ============================

    public function render()
    {

        return view('livewire.notificaciones.crear-notificacion');
    }
}
