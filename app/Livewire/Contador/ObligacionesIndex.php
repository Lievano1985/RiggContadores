<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * Componente: ObligacionesIndex
 * Función: Listado y acciones de obligaciones del contador logueado.
 * Incluye:
 * - Filtros automáticos por fecha actual (NO se toca)
 * - Filtros manuales por ejercicio y mes (campos BD)
 * - Subida de archivos Storage / Drive
 */

namespace App\Livewire\Contador;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\CarpetaDrive;
use App\Services\DriveService;

class ObligacionesIndex extends Component
{
    use WithPagination, WithFileUploads;

    // =========================================================
    // FILTROS
    // =========================================================
    public ?string $buscar = '';
    public ?string $estatus = '';
    public ?string $ejercicioSeleccionado = null;
    public ?string $mesSeleccionado = null;

    public array $ejerciciosDisponibles = [];

    public array $mesesDisponibles = [
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

    // =========================================================
    // MODAL
    // =========================================================
    public bool $openModal = false;
    public ?int $selectedId = null;

    public ?string $numero_operacion = null;
    public $archivo = null;
    public ?string $fecha_finalizado = null;
    // =========================================================
    // RECHAZOS
    // =========================================================
    public bool $soloLectura = false;
    public ?string $comentario = null;

    // =========================================================
    // LISTENERS
    // =========================================================
    protected $listeners = [
        'archivos-ok-obligaciones'    => 'continuarGuardado',
        'archivos-error-obligaciones' => 'cancelarGuardado',
    ];


    // =========================================================
    // QUERY STRING
    // =========================================================
    protected $queryString = [
        'buscar' => ['except' => ''],
        'estatus' => ['except' => ''],
        'ejercicioSeleccionado' => ['except' => null],
        'mesSeleccionado' => ['except' => null],
    ];

    // =========================================================
    // VALIDACIÓN
    // =========================================================
    protected function rules()
    {
        return [
            'numero_operacion' => ['required', 'string', 'max:100'],
            'fecha_finalizado' => ['nullable', 'date'],
        ];
    }

    // =========================================================
    // CICLO DE VIDA
    // =========================================================
    public function mount(): void
    {
        $this->cargarEjerciciosDisponibles();
    }

    // =========================================================
    // HOOKS
    // =========================================================
    public function updatingBuscar()
    {
        $this->resetPage();
    }
    public function updatingEstatus()
    {
        $this->resetPage();
    }

    public function updatedEjercicioSeleccionado()
    {
        $this->resetPage();
        $this->mesSeleccionado = null;
    }

    public function updatedMesSeleccionado()
    {
        $this->resetPage();
    }

    // =========================================================
    // RENDER
    // =========================================================
    public function render()
    {
        $q = ObligacionClienteContador::query()
            ->with(['cliente', 'obligacion'])
            ->where('contador_id', Auth::id())

            // 🔹 FILTRO AUTOMÁTICO (NO TOCAR)
            ->where(function ($q) {
                $q->where('estatus', '!=', 'finalizado')
                    ->orWhereDate('fecha_vencimiento', '>=', now());
            })

            // 🔹 FILTROS MANUALES
            ->when(
                $this->ejercicioSeleccionado,
                fn($w) => $w->where('ejercicio', $this->ejercicioSeleccionado)
            )
            ->when(
                $this->mesSeleccionado,
                fn($w) => $w->where('mes', $this->mesSeleccionado)
            )
            ->when(
                $this->estatus,
                fn($w) => $w->where('estatus', $this->estatus)
            )

            ->when($this->buscar, function ($w) {
                $bus = trim($this->buscar);
                $w->where(function ($x) use ($bus) {
                    $x->whereHas(
                        'cliente',
                        fn($c) =>
                        $c->where('nombre', 'like', "%{$bus}%")
                            ->orWhere('razon_social', 'like', "%{$bus}%")
                    )
                        ->orWhereHas(
                            'obligacion',
                            fn($o) =>
                            $o->where('nombre', 'like', "%{$bus}%")
                        );
                });
            })

            ->orderByRaw("CASE
                WHEN estatus='asignada' THEN 1
                WHEN estatus='en_progreso' THEN 2
                WHEN estatus='realizada' THEN 3
                WHEN estatus='enviada_cliente' THEN 4
                WHEN estatus='respuesta_cliente' THEN 5
                WHEN estatus='respuesta_revisada' THEN 6
                WHEN estatus='finalizado' THEN 7
                WHEN estatus='reabierta' THEN 8
                ELSE 99 END")
            ->orderBy('fecha_vencimiento', 'asc');

        $obligaciones = $q->paginate(10);

        return view('livewire.contador.obligaciones-index', compact('obligaciones'));
    }

    // =========================================================
    // CARGA SELECTS
    // =========================================================
    private function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = ObligacionClienteContador::query()
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
    // ACCIONES
    // =========================================================
    public function iniciarObligacion(int $id): void
    {
        $o = $this->findMine($id);

        if ($o->estatus !== 'asignada') {
            session()->flash('error', 'Solo puedes iniciar obligaciones asignadas.');
            return;
        }

        $o->update([
            'estatus' => 'en_progreso',
            'fecha_inicio' => now(),
        ]);

        session()->flash('success', 'Obligación iniciada.');
    }

    public function openResultModal(int $id): void
    {
        $o = $this->findMine($id);

        $this->resetValidation();
        $this->reset(['archivo', 'numero_operacion']);

        $this->selectedId = $o->id;
        $this->numero_operacion = $o->numero_operacion;
        $this->fecha_finalizado = optional($o->fecha_finalizado)->toDateString();

        $this->openModal = true;
    }

    // 🔹 BOTÓN PADRE
    public function saveResult(): void
    {
        $this->dispatch('guardar-archivos-adjuntos', origen: 'obligaciones');
    }

    /* ==========================================
       SOLO SI ARCHIVOS OK
    ========================================== */
    public function continuarGuardado(): void
    {
        $this->validate();

        $o = $this->findMine((int)$this->selectedId);

        if ($this->hayTareasPendientes($o->id)) {
            $this->dispatch('notify', message: 'Cierra tareas ligadas primero');
            return;
        }

        if ($this->archivo instanceof UploadedFile) {
            $upload = $this->subirArchivoResultado($o, $this->archivo);
            $o->archivo_resultado = $upload['storage'] ?? $o->archivo_resultado;
        }

        $o->numero_operacion = $this->numero_operacion;
        $o->fecha_finalizado = $this->fecha_finalizado;

        if (in_array($o->estatus, ['asignada', 'en_progreso'], true)) {
            $o->estatus = 'realizada';
            $o->fecha_termino = now();
            $o->fecha_inicio ??= now();
        }

        $o->save();

        $this->reset([
            'openModal',
            'selectedId',
            'archivo',
            'numero_operacion',
            'fecha_finalizado'
        ]);

        $this->dispatch(
            'notify',
            message: 'Obligación guardada correctamente'
        );
    }

    /* ==========================================
       SI ARCHIVOS FALLAN
    ========================================== */
    public function cancelarGuardado(): void
    {
        $this->dispatch(
            'notify',
            message: 'Corrige los archivos antes de continuar'
        );
    }

    // =========================================================
    // HELPERS
    // =========================================================
    private function findMine(int $id): ObligacionClienteContador
    {
        return ObligacionClienteContador::where('contador_id', Auth::id())
            ->findOrFail($id);
    }

    private function hayTareasPendientes(int $id): bool
    {
        return TareaAsignada::where('obligacion_cliente_contador_id', $id)
            ->whereNotIn('estatus', ['realizada', 'revisada', 'cerrada'])
            ->exists();
    }

    // =========================================================
    // RECHAZO (IGUAL QUE TAREAS)
    // =========================================================
    public function verRechazoObligacion(int $id): void
    {
        $o = $this->findMine($id);

        if ($o->estatus !== 'rechazada') {
            return;
        }

        $this->selectedId = $o->id;
        $this->comentarioRechazo = $o->comentario;
        $this->soloLectura = true;

        $this->openModal = true;
    }

    public function corregirObligacion(int $id): void
    {
        $o = $this->findMine($id);

        if ($o->estatus !== 'rechazada') {
            return;
        }

        $o->update([
            'estatus' => 'en_progreso',
            'fecha_inicio' => now(),
            'fecha_termino' => null,
        ]);

        $this->selectedId = $o->id;
        $this->soloLectura = false;

        $this->openModal = true;
    }
}
