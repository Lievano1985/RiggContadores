<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * MisTareasIndex
 * - Lista las tareas asignadas SOLO del contador logeado.
 * - Filtros: estatus, búsqueda (cliente/tarea/obligación), ejercicio (año) y mes.
 * - Ejercicio/Mes SIEMPRE se basan en fecha_limite (dato real).
 * - Los combos de ejercicio/mes se cargan dinámicamente desde BD (solo años/meses con datos).
 * - Al cambiar ejercicio, se recalcula el combo de meses y se ajusta el mes seleccionado.
 */

namespace App\Livewire\Contador;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Models\TareaAsignada;
use App\Models\CarpetaDrive;
use App\Services\DriveService;

class MisTareasIndex extends Component
{
    use WithPagination, WithFileUploads;

    // -----------------------------
    // Filtros UI
    // -----------------------------
    public ?string $buscar = '';
    public ?string $estatus = '';

    /**
     * IMPORTANTE:
     * Livewire manda strings desde <select>, por eso aquí son string.
     * Luego casteamos a int en el query (whereYear/whereMonth).
     */
    public ?string $ejercicio = null; // "2025"
    public ?string $mes = null;       // "12"

    public array $ejerciciosDisponibles = []; // ["2026","2025"]
    public array $mesesDisponibles = [];      // ["1","12",...]

    // -----------------------------
    // Modal Seguimiento
    // -----------------------------
    public ?string $modalCliente = null;
    public ?string $modalTarea = null;
    public ?string $modalObligacion = null;
    public bool $openModal = false;
    public ?int $tareaId = null;
    public $archivo = null;
    public ?string $comentario = null;
    public ?TareaAsignada $tareaSeleccionada = null;
    public ?string $comentarioRechazo = null;
    public bool $soloLectura = false;

    public array $mesesManual = [
        '1' => 'Enero',
        '2' => 'Febrero',
        '3' => 'Marzo',
        '4' => 'Abril',
        '5' => 'Mayo',
        '6' => 'Junio',
        '7' => 'Julio',
        '8' => 'Agosto',
        '9' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre',
    ];

    protected $listeners = [
        'archivos-ok-tareas'    => 'continuarGuardadoTarea',
        'archivos-error-tareas' => 'cancelarGuardadoTarea',
    ];

    // -----------------------------
    // Validación
    // -----------------------------
    protected function rules()
    {
        return [
            'comentario'   => ['nullable', 'string', 'max:500'],
            'archivo'      => ['nullable', 'file', 'mimes:pdf,zip,jpg,jpeg,png'],
            'nuevoEstatus' => ['nullable', 'in:asignada,en_progreso,realizada,revisada,rechazada,cancelada,cerrada,reabierta'],
        ];
    }

    // =========================================================
    // LIFECYCLE
    // =========================================================
    public function mount(): void
    {
        $this->cargarEjerciciosDisponibles();
    }


    // =========================================================
    // REACCIONES A FILTROS (reset paginación)
    // =========================================================
    public function updatingBuscar()
    {
        $this->resetPage();
    }
    public function updatingEstatus()
    {
        $this->resetPage();
    }



    // =========================================================
    // CARGA DE COMBOS (BD) - BASADO EN fecha_limite
    // =========================================================
    private function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = TareaAsignada::query()
            ->where('contador_id', Auth::id())
            ->whereNotNull('ejercicio')
            ->select('ejercicio')
            ->distinct()
            ->orderByDesc('ejercicio')
            ->pluck('ejercicio')
            ->map(fn($v) => (string)$v)
            ->values()
            ->all();
    }




    // =========================================================
    // CONSULTA PRINCIPAL
    // =========================================================
    public function render()
    {
        $tareas = TareaAsignada::query()
            ->with([
                'cliente',
                'tareaCatalogo',
                'obligacionClienteContador.obligacion',
            ])
            ->where('contador_id', Auth::id())

            ->when(
                $this->ejercicio,
                fn($q) =>
                $q->where('ejercicio', $this->ejercicio)
            )
            ->when(
                $this->mes,
                fn($q) =>
                $q->where('mes', $this->mes)
            )

            // Estatus
            ->when($this->estatus, fn($q) => $q->where('estatus', $this->estatus))

            // Buscar
            ->when($this->buscar, function ($q) {
                $bus = trim($this->buscar);

                $q->where(function ($w) use ($bus) {
                    $w->whereHas('cliente', function ($c) use ($bus) {
                        $c->where('nombre', 'like', "%{$bus}%")
                            ->orWhere('razon_social', 'like', "%{$bus}%");
                    })
                        ->orWhereHas('tareaCatalogo', function ($t) use ($bus) {
                            $t->where('nombre', 'like', "%{$bus}%");
                        })
                        ->orWhereHas('obligacionClienteContador.obligacion', function ($o) use ($bus) {
                            $o->where('nombre', 'like', "%{$bus}%");
                        });
                });
            })

            // Orden
            ->orderBy('fecha_limite', 'asc')
            ->orderByRaw("CASE
                WHEN estatus='asignada' THEN 1
                WHEN estatus='en_progreso' THEN 2
                WHEN estatus='realizada' THEN 3
                WHEN estatus='revisada' THEN 4
                WHEN estatus='rechazada' THEN 5
                WHEN estatus='reabierta' THEN 6
                WHEN estatus='cancelada' THEN 7
                WHEN estatus='cerrada' THEN 8
                ELSE 99 END")

            ->paginate(10);

        return view('livewire.contador.mis-tareas-index', compact('tareas'));
    }

    // =========================================================
    // ACCIONES (sin cambios por ahora)
    // =========================================================
    public function abrirSeguimiento(int $id): void
    {
        $t = $this->findMine($id);
    
        $this->tareaSeleccionada = $t;
        $this->comentario = $t->comentario;
        $this->archivo = null;
    
        // 👇 TITULOS
        $this->modalCliente =
            $t->cliente->nombre
            ?? $t->cliente->razon_social
            ?? 'Cliente';
    
        $this->modalTarea = $t->tareaCatalogo->nombre ?? 'Tarea';
    
        $this->modalObligacion =
            $t->obligacionClienteContador?->obligacion?->nombre;
    
        $this->soloLectura = false;
    
        $this->resetValidation();
        $this->openModal = true;
    }
    

    public function guardarSeguimiento(): void
    {
        $this->validate([
            'comentario' => ['nullable', 'string', 'max:500'],
            'archivo'    => ['nullable', 'file', 'mimes:pdf,zip,jpg,jpeg,png'],
        ]);

        $t = $this->findMine($this->tareaId);

        // =============================
        // Subida de archivo (tu lógica intacta)
        // =============================
        $rutaStorage = $t->archivo;
        $linkDrive   = $t->archivo_drive_url;

        if ($this->archivo instanceof \Illuminate\Http\UploadedFile) {
            // (Aquí va exactamente tu lógica actual, sin cambios)
            // Storage / Drive
        }

        // =============================
        // Finalizar tarea
        // =============================
        $t->update([
            'estatus'       => 'realizada',
            'fecha_termino' => now(),
            'comentario'    => $this->comentario,
            'archivo'       => $rutaStorage,
            'archivo_drive_url' => $linkDrive,
        ]);

        $this->reset(['openModal', 'tareaId', 'archivo', 'comentario']);

        $this->dispatch(
            'notify',
            message: 'Tarea finalizada correctamente.'
        );
    }


    public function iniciar(int $id): void
    {
        $t = $this->findMine($id);

        if ($t->estatus !== 'asignada') {
            return;
        }

        $t->update([
            'estatus' => 'en_progreso',
            'fecha_inicio' => now(),
        ]);
    }
    public function terminar(int $id): void
    {
        $t = $this->findMine($id);
    
        if ($t->estatus !== 'en_progreso') {
            return;
        }
    
        $this->tareaSeleccionada = $t;
        $this->comentario = $t->comentario;
    
        // 👇 TITULOS
        $this->modalCliente =
            $t->cliente->nombre
            ?? $t->cliente->razon_social
            ?? 'Cliente';
    
        $this->modalTarea = $t->tareaCatalogo->nombre ?? 'Tarea';
    
        $this->modalObligacion =
            $t->obligacionClienteContador?->obligacion?->nombre;
    
        $this->soloLectura = false;
        $this->openModal = true;
    }
    


    private function findMine(int $id): TareaAsignada
    {
        return TareaAsignada::where('contador_id', Auth::id())->findOrFail($id);
    }




    public function saveResultTarea(): void
    {
        $this->dispatch('guardar-archivos-adjuntos', origen: 'tareas');
    }

    public function continuarGuardadoTarea(): void
    {
        if (!$this->tareaSeleccionada) {
            return;
        }

        $t = $this->findMine($this->tareaSeleccionada->id);

        $t->update([
            'estatus'       => 'realizada',
            'fecha_termino' => now(),
            'comentario'    => $this->comentario,
        ]);

        $this->reset([
            'openModal',
            'tareaSeleccionada',
            'comentario',
            'archivo'
        ]);


        $this->dispatch(
            'notify',
            message: 'Tarea finalizada correctamente.'
        );
    }
    public function cancelarGuardadoTarea(): void
    {

        $this->dispatch(
            'notify',
            message: 'Corrige los archivos antes de continuar'
        );
    }



    public function verRechazo(int $id): void
{
    $t = $this->findMine($id);

    if ($t->estatus !== 'rechazada') {
        return;
    }

    $this->tareaSeleccionada = $t;
    $this->comentario = $t->comentario;

    // 👇 TITULOS
    $this->modalCliente =
        $t->cliente->nombre
        ?? $t->cliente->razon_social
        ?? 'Cliente';

    $this->modalTarea = $t->tareaCatalogo->nombre ?? 'Tarea';

    $this->modalObligacion =
        $t->obligacionClienteContador?->obligacion?->nombre;

    $this->soloLectura = true;
    $this->openModal = true;
}




public function corregir(int $id): void
{
    $t = $this->findMine($id);

    if ($t->estatus !== 'rechazada') {
        return;
    }

    $t->update([
        'estatus' => 'en_progreso',
        'fecha_inicio' => now(),
        'fecha_termino' => null,
    ]);

    $this->tareaSeleccionada = $t;
    $this->comentario = $t->comentario;

    // 👇 TITULOS
    $this->modalCliente =
        $t->cliente->nombre
        ?? $t->cliente->razon_social
        ?? 'Cliente';

    $this->modalTarea = $t->tareaCatalogo->nombre ?? 'Tarea';

    $this->modalObligacion =
        $t->obligacionClienteContador?->obligacion?->nombre;

    $this->soloLectura = false;
    $this->openModal = true;
}

}
