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
    public bool $openModal = false;
    public ?int $tareaId = null;
    public $archivo = null;
    public ?string $comentario = null;
    public ?string $nuevoEstatus = null;

    // -----------------------------
    // QueryString (opcional)
    // -----------------------------
    protected $queryString = [
        'buscar'    => ['except' => ''],
        'estatus'   => ['except' => ''],
        'ejercicio' => ['except' => null],
        'mes'       => ['except' => null],
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
        // 1) Cargar años disponibles (solo años con datos reales en fecha_limite)
        $this->cargarEjerciciosDisponibles();

        // 2) Definir ejercicio default:
        //    - si el año actual existe en datos => usarlo
        //    - si no existe => usar el más reciente disponible
        $anioActual = (string) now()->year;

        if (!empty($this->ejerciciosDisponibles)) {
            $this->ejercicio = in_array($anioActual, $this->ejerciciosDisponibles, true)
                ? $anioActual
                : $this->ejerciciosDisponibles[0]; // el más reciente (orderByDesc)
        } else {
            // no hay datos -> deja año actual
            $this->ejercicio = $anioActual;
        }

        // 3) Cargar meses del ejercicio seleccionado
        $this->cargarMesesDisponibles();

        // 4) Definir mes default:
        //    - si el mes actual existe para ese ejercicio => usarlo
        //    - si no => usar el primer mes disponible (ordenado asc)
        $mesActual = (string) now()->month; // "12"

        if (!empty($this->mesesDisponibles)) {
            $this->mes = in_array($mesActual, $this->mesesDisponibles, true)
                ? $mesActual
                : $this->mesesDisponibles[0];
        } else {
            $this->mes = $mesActual;
        }
    }

    // =========================================================
    // REACCIONES A FILTROS (reset paginación)
    // =========================================================
    public function updatingBuscar()  { $this->resetPage(); }
    public function updatingEstatus() { $this->resetPage(); }

    /**
     * Cuando cambia el ejercicio:
     * - recalcula meses disponibles para ese año
     * - y ajusta $mes si ya no existe
     */
    public function updatedEjercicio($value): void
    {
        $this->resetPage();

        // normaliza a string (por si llega null/int)
        $this->ejercicio = $value !== null ? (string) $value : null;

        $this->cargarMesesDisponibles();

        // Si el mes seleccionado ya no existe en este año, asigna el primero disponible
        if ($this->mes !== null && !in_array($this->mes, $this->mesesDisponibles, true)) {
            $this->mes = $this->mesesDisponibles[0] ?? null;
        }

        // Si no hay mes seleccionado (por ejemplo al limpiar), setea uno válido si existe
        if ($this->mes === null && !empty($this->mesesDisponibles)) {
            $this->mes = $this->mesesDisponibles[0];
        }
    }

    public function updatedMes($value): void
    {
        $this->resetPage();
        $this->mes = $value !== null ? (string) $value : null;
    }

    // =========================================================
    // CARGA DE COMBOS (BD) - BASADO EN fecha_limite
    // =========================================================
    private function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = TareaAsignada::query()
            ->where('contador_id', Auth::id())
            ->whereNotNull('fecha_limite')
            ->selectRaw('YEAR(fecha_limite) as anio')
            ->distinct()
            ->orderByDesc('anio') // más reciente primero (ej: 2026, 2025)
            ->pluck('anio')
            ->map(fn($v) => (string) $v)
            ->values()
            ->all();
    }

    private function cargarMesesDisponibles(): void
    {
        if (!$this->ejercicio) {
            $this->mesesDisponibles = [];
            return;
        }

        $this->mesesDisponibles = TareaAsignada::query()
            ->where('contador_id', Auth::id())
            ->whereNotNull('fecha_limite')
            ->whereYear('fecha_limite', (int) $this->ejercicio)
            ->selectRaw('MONTH(fecha_limite) as mes')
            ->distinct()
            ->orderBy('mes') // asc: 1..12
            ->pluck('mes')
            ->map(fn($v) => (string) $v)
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

            // Filtro por ejercicio/mes (SIEMPRE usando fecha_limite)
            ->when($this->ejercicio, fn($q) => $q->whereYear('fecha_limite', (int) $this->ejercicio))
            ->when($this->mes, fn($q) => $q->whereMonth('fecha_limite', (int) $this->mes))

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

        $this->tareaId = $t->id;
        $this->comentario = $t->comentario;
        $this->nuevoEstatus = null;
        $this->archivo = null;

        $this->resetValidation();
        $this->openModal = true;
    }

    public function guardarSeguimiento(): void
    {
        $this->validate();
    
        $t = $this->findMine((int) $this->tareaId);
    
        $rutaStorage = $t->archivo;
        $linkDrive   = $t->archivo_drive_url;
    
        if ($this->archivo instanceof \Illuminate\Http\UploadedFile) {
            $cliente  = $t->cliente;
            $politica = $cliente->despacho->politica_almacenamiento ?? 'storage_only';
            $nombre   = now()->format('Ymd_His') . '_tarea_' . $t->id . '.' . $this->archivo->getClientOriginalExtension();
    
            // Laravel Storage
            if (in_array($politica, ['storage_only', 'both'])) {
                $rutaStorage = $this->archivo->storeAs("clientes/{$cliente->id}/tareas", $nombre, 'public');
            }
    
            // Google Drive
            if (in_array($politica, ['drive_only', 'both'])) {
                $folderId = null;
    
                // Si se asignó carpeta específica
                if (!empty($t->carpeta_drive_id)) {
                    $cd = CarpetaDrive::find($t->carpeta_drive_id);
                    $folderId = $cd?->drive_folder_id;
                }
    
                // Carpeta fallback
                if (!$folderId) {
                    $cd = CarpetaDrive::where('cliente_id', $cliente->id)
                        ->where('nombre', 'like', '%Archivos en proceso%')
                        ->first();
                    $folderId = $cd?->drive_folder_id;
                }
    
                if ($folderId) {
                    try {
                        $drive = app(DriveService::class);
    
                        $res = $drive->subirArchivo(
                            $nombre,
                            $this->archivo,
                            $folderId,
                            $this->archivo->getMimeType()
                        );
    
                        if (is_string($res)) {
                            $linkDrive = $res;
                        } elseif (is_array($res) && isset($res['webViewLink'])) {
                            $linkDrive = $res['webViewLink'];
                        }
                    } catch (\Exception $e) {
                        \Log::error('❌ Error al subir archivo de tarea a Drive: ' . $e->getMessage());
                        $this->addError('archivo', 'Error al subir archivo a Google Drive.');
                    }
                } else {
                    $this->addError('archivo', 'No se encontró carpeta válida en Drive.');
                }
            }
        }
    
        // Actualización de estatus y fechas si aplica
        if ($this->nuevoEstatus) {
            if ($this->nuevoEstatus === 'en_progreso' && empty($t->fecha_inicio)) {
                $t->fecha_inicio = now();
            }
    
            if (in_array($this->nuevoEstatus, ['realizada', 'cerrada']) && empty($t->fecha_termino)) {
                $t->fecha_termino = now();
            }
    
            $t->estatus = $this->nuevoEstatus;
        }
    
        $t->comentario = $this->comentario ?: $t->comentario;
        $t->archivo = $rutaStorage;
        $t->archivo_drive_url = $linkDrive;
        $t->save();
    
        $this->reset(['openModal', 'tareaId', 'archivo', 'comentario', 'nuevoEstatus']);
    
        $this->dispatch('toast', type: 'success', message: 'Seguimiento guardado.');
    }
    

    public function iniciar(int $id): void
    {
        $t = $this->findMine($id);

        if ($t->estatus !== 'asignada') {
            $this->dispatch('toast', type: 'warning', message: 'Solo puedes iniciar tareas en estatus asignada.');
            return;
        }

        $t->update([
            'estatus' => 'en_progreso',
            'fecha_inicio' => now(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Tarea iniciada.');
    }

    private function findMine(int $id): TareaAsignada
    {
        return TareaAsignada::where('contador_id', Auth::id())->findOrFail($id);
    }
}
