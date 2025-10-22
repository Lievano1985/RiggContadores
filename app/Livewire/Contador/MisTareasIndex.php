<?php

namespace App\Livewire\Contador;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\TareaAsignada;
use App\Models\CarpetaDrive;
use App\Services\DriveService;

class MisTareasIndex extends Component
{
    use WithPagination, WithFileUploads;

    public ?string $buscar = '';
    public ?string $estatus = '';
    public ?string $vence_desde = null;
    public ?string $vence_hasta = null;

    public bool $openModal = false;
    public ?int $tareaId = null;
    public $archivo;                   // UploadedFile|null
    public ?string $comentario = null;
    public string $periodoSeleccionado;
    public array $periodosDisponibles = [];
    protected $queryString = [
        'buscar'      => ['except' => ''],
        'estatus'     => ['except' => ''],
        'vence_desde' => ['except' => null],
        'vence_hasta' => ['except' => null],
    ];

    protected function rules()
    {
        return [
            'comentario' => ['nullable', 'string', 'max:500'],
            'archivo'    => ['nullable', 'file', 'mimes:pdf,zip,jpg,png'],
        ];
    }

    public function updatingBuscar()
    {
        $this->resetPage();
    }
    public function updatingEstatus()
    {
        $this->resetPage();
    }
    public function updatingVenceDesde()
    {
        $this->resetPage();
    }
    public function updatingVenceHasta()
    {
        $this->resetPage();
    }


    public function mount()
    {
        $this->periodoSeleccionado = $this->periodoActual();
        $this->periodosDisponibles = $this->generarPeriodosDisponibles();
    }

    private function periodoActual(): string
    {
        return now()->format('Y-m');
    }

    private function generarPeriodosDisponibles(): array
    {
        $lista = [];
        for ($i = 0; $i < 12; $i++) {
            $fecha = now()->subMonths($i);
            $lista[] = $fecha->format('Y-m');
        }
        return $lista;
    }
    public function render()
    {


        // OJO: eager load correcto: obligacion (OCC) y su relación obligacion (catálogo)
        $tareas = TareaAsignada::with([
            'cliente',
            'tareaCatalogo',
            'obligacionClienteContador.obligacion',
        ])
            ->where('contador_id', Auth::id())
            ->where('periodo', $this->periodoSeleccionado) // 👈 filtro por periodo seleccionado


            ->when($this->buscar, function ($q) {
                $q->where(function ($w) {
                    $w->whereHas('cliente', function ($c) {
                        $c->where('nombre', 'like', "%{$this->buscar}%")
                            ->orWhere('razon_social', 'like', "%{$this->buscar}%");
                    })->orWhereHas('tareaCatalogo', function ($t) {
                        $t->where('nombre', 'like', "%{$this->buscar}%");
                    })->orWhereHas('obligacionClienteContador.obligacion', function ($o) { // tengo qie cambiar igual esta linea?
                        $o->where('nombre', 'like', "%{$this->buscar}%");
                    });
                });
            })
            ->when($this->estatus, fn($q) => $q->where('estatus', $this->estatus))
            ->when($this->vence_desde, fn($q) => $q->whereDate('fecha_limite', '>=', $this->vence_desde))
            ->when($this->vence_hasta, fn($q) => $q->whereDate('fecha_limite', '<=', $this->vence_hasta))
            ->orderByRaw("CASE
                WHEN estatus='asignada' THEN 1
                WHEN estatus='iniciando' THEN 2
                WHEN estatus='en_progreso' THEN 3
                WHEN estatus='terminada' THEN 4
                WHEN estatus='revisada' THEN 5
                WHEN estatus='rechazada' THEN 6
                ELSE 7 END")
            ->orderBy('fecha_limite')
            ->paginate(10);

        return view('livewire.contador.mis-tareas-index', compact('tareas'));
    }

    // --- Transiciones ---

    public function iniciar(int $id)
    {
        $tarea = TareaAsignada::where('contador_id', Auth::id())->findOrFail($id);

        if ($tarea->estatus !== 'asignada') {
            session()->flash('error', 'La tarea ya fue iniciada o completada.');
            return;
        }

        $tarea->update([
            'estatus' => 'en_progreso',
            'fecha_inicio' => now(),
        ]);

        session()->flash('success', 'Tarea iniciada correctamente.');
    }


    public function marcarEnProgreso(int $id)
    {
        $t = $this->findMine($id);
        if (!in_array($t->estatus, ['asignada'])) {
            $this->dispatch('toast', type: 'warning', message: 'No es posible mover a En progreso.');
            return;
        }
        if (empty($t->fecha_inicio)) {
            $t->fecha_inicio = now();
        }
        $t->estatus = 'en_progreso';
        $t->save();

        $this->dispatch('toast', type: 'success', message: 'Tarea en progreso.');
    }

    public function abrirModalTerminar(int $id)
    {
        $t = $this->findMine($id);
        if (!in_array($t->estatus, ['en_progreso'])) {
            $this->dispatch('toast', type: 'warning', message: 'Solo puedes terminar tareas en progreso.');
            return;
        }
        $this->tareaId = $t->id;
        $this->resetValidation();
        $this->reset(['archivo', 'comentario']);
        $this->openModal = true;
    }

    public function terminar()
    {
        $this->validate();

        $t = $this->findMine($this->tareaId);

        // Subir archivo si viene
        $rutaStorage = $t->archivo;
        $linkDrive   = $t->archivo_drive_url; // <-- tu columna real

        if ($this->archivo) {
            $cliente = $t->cliente;
            $politica = $cliente->despacho->politica_almacenamiento ?? 'storage_only';
            $nombre = now()->format('Ymd_His') . '_tarea_' . $t->id . '.' . $this->archivo->getClientOriginalExtension();

            if (in_array($politica, ['storage_only', 'both'])) {
                $rutaStorage = $this->archivo->storeAs(
                    "clientes/{$cliente->id}/tareas",
                    $nombre,
                    'public'
                );
            }

            if (in_array($politica, ['drive_only', 'both'])) {
                // Usar carpeta específica si viene en la tarea (carpeta_drive_id)
                $folderId = null;
                if (!empty($t->carpeta_drive_id)) {
                    $cd = CarpetaDrive::find($t->carpeta_drive_id);
                    $folderId = $cd?->drive_folder_id;
                }

                // Fallback opcional
                if (!$folderId) {
                    $cd = CarpetaDrive::where('cliente_id', $cliente->id)
                        ->where('nombre', 'like', '%Archivos en proceso%')
                        ->first();
                    $folderId = $cd?->drive_folder_id;
                }

                if ($folderId) {
                    /** @var DriveService $drive */
                    $drive = app(DriveService::class);
                    $res = $drive->subirArchivo(
                        $nombre,
                        $this->archivo,
                        $folderId,
                        $this->archivo->getMimeType()
                    );
                    // Guarda link/ID devuelto por tu servicio
                    if (is_string($res)) {
                        $linkDrive = $res;
                    } elseif (is_array($res) && isset($res['webViewLink'])) {
                        $linkDrive = $res['webViewLink'];
                    }
                }
            }
        }

        // Cerrar tarea
        $t->estatus = 'realizada';
        $t->fecha_termino = now(); // <-- tu columna real

        $t->comentario = $this->comentario ?: $t->comentario;
        $t->archivo = $rutaStorage;
        $t->archivo_drive_url = $linkDrive; // <-- tu columna real
        $t->save();

        $this->reset(['openModal', 'tareaId', 'archivo', 'comentario']);
        $this->dispatch('toast', type: 'success', message: 'Tarea terminada.');
    }

    // --- Helpers ---

    private function findMine(int $id): TareaAsignada
    {
        return TareaAsignada::where('contador_id', Auth::id())->findOrFail($id);
    }
}
