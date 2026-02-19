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
use App\Services\BrevoService;
use Illuminate\Support\Facades\Storage;

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

        // 1️⃣ Crear registro en BD (temporal)
        $notificacion = NotificacionCliente::create([
            'cliente_id' => $this->cliente->id,
            'user_id' => Auth::id(),
            'asunto' => $this->asunto,
            'mensaje' => $this->mensaje,
            'periodo_mes' => $this->periodo_mes,
            'periodo_ejercicio' => $this->periodo_ejercicio,
        ]);

        // 2️⃣ Guardar relaciones obligaciones
        $notificacion->obligaciones()
            ->sync($this->obligacionesSeleccionadas);

        // 3️⃣ Guardar relaciones archivos
        $notificacion->archivos()
            ->sync(collect($this->archivosSeleccionados)->pluck('id'));

        // 4️⃣ Preparar adjuntos para Brevo
        $attachments = [];

        $archivos = ArchivoAdjunto::whereIn(
            'id',
            collect($this->archivosSeleccionados)->pluck('id')
        )->get();

        foreach ($archivos as $archivo) {

            // Solo archivos que existen en Storage
            if ($archivo->tieneArchivoStorage()) {
        
                if (Storage::disk('public')->exists($archivo->archivo)) {
        
                    $attachments[] = [
                        'name' => $archivo->nombre ?? basename($archivo->archivo),
                        'content' => base64_encode(
                            Storage::disk('public')->get($archivo->archivo)
                        ),
                    ];
                }
            }
        }
        
     
        // 5️⃣ Enviar correo con Brevo
        $brevo = new BrevoService();

        $response = $brevo->enviarNotificacionClientePlantilla(
            $this->cliente->correo,
            $this->cliente->nombre,
            $this->mensaje,
            $this->periodo_mes . '/' . $this->periodo_ejercicio,
            $attachments
        );


        // 6️⃣ Validar respuesta
        if (!$response) {

            // Si falla el envío, eliminar notificación creada
            $notificacion->delete();

            $this->dispatch(
                'notify',
                message: 'Error al enviar el correo. Intenta nuevamente.'
            );

            return;
        }

        // 7️⃣ Solo si el correo fue exitoso → actualizar estatus
        ObligacionClienteContador::whereIn('id', $this->obligacionesSeleccionadas)
            ->update(['estatus' => 'enviada_cliente']);

        // 8️⃣ Limpiar formulario
        $this->asunto = '';
        $this->mensaje = '';
        $this->obligacionesSeleccionadas = [];
        $this->archivosSeleccionados = [];

        $this->dispatch(
            'notify',
            message: 'Notificación enviada correctamente'
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
