<?php

/**
 * Autor: Luis Liévano - JL3 Digital
 *
 * Componente: ObligacionesIndex
 * Función: Listado y acciones de obligaciones del contador logueado.
 * Incluye:
 * - Filtros por estatus, año, mes, búsqueda por cliente/obligación.
 * - Guardado de resultados (archivo, número de operación, vencimiento).
 * - Subida de archivo a Laravel Storage y/o Google Drive.
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
use App\Services\DriveService;

class ObligacionesIndex extends Component
{
    use WithPagination, WithFileUploads;

    // =========================================================
    // PROPIEDADES: Filtros y estado UI
    // =========================================================

    public ?string $buscar = '';
    public ?string $estatus = '';
    public ?string $ejercicioSeleccionado = null;
    public ?string $mesSeleccionado = null;

    public array $ejerciciosDisponibles = [];
    public array $mesesDisponibles = [];

    public array $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    // =========================================================
    // PROPIEDADES: Modal de resultados
    // =========================================================

    public bool $openModal = false;
    public ?int $selectedId = null;

    public ?string $numero_operacion = null;
    public $archivo = null; // UploadedFile|null
    public ?string $fecha_vencimiento = null;
    public ?string $fecha_finalizado = null;

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
            'archivo' => ['nullable', 'file', 'mimes:pdf,zip,jpg,jpeg,png'],
            'numero_operacion' => ['required', 'string', 'max:100'],
            'fecha_finalizado' => [ 'nullable','date'],
        ];
    }

    // =========================================================
    // CICLO DE VIDA
    // =========================================================

    public function mount(): void
    {
        $this->ejercicioSeleccionado = (string) now()->year;
        $this->mesSeleccionado = (string) now()->month;

        $this->cargarEjerciciosDisponibles();

        if (!in_array($this->ejercicioSeleccionado, $this->ejerciciosDisponibles, true)) {
            $this->ejercicioSeleccionado = $this->ejerciciosDisponibles[0] ?? (string) now()->year;
        }

        $this->cargarMesesDisponibles();

        if (!in_array($this->mesSeleccionado, $this->mesesDisponibles, true)) {
            $this->mesSeleccionado = $this->mesesDisponibles[0] ?? null;
        }
    }

    // =========================================================
    // HOOKS
    // =========================================================

    public function updatingBuscar() { $this->resetPage(); }
    public function updatingEstatus() { $this->resetPage(); }

    public function updatedEjercicioSeleccionado(): void
    {
        $this->resetPage();
        $this->cargarMesesDisponibles();

        if (!in_array($this->mesSeleccionado, $this->mesesDisponibles, true)) {
            $this->mesSeleccionado = $this->mesesDisponibles[0] ?? null;
        }
    }

    public function updatedMesSeleccionado(): void
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
            ->when($this->ejercicioSeleccionado, fn($w) => $w->whereYear('fecha_vencimiento', (int) $this->ejercicioSeleccionado))
            ->when($this->mesSeleccionado, fn($w) => $w->whereMonth('fecha_vencimiento', (int) $this->mesSeleccionado))
            ->when($this->estatus, fn($w) => $w->where('estatus', $this->estatus))
            ->when($this->buscar, function ($w) {
                $bus = trim($this->buscar);
                $w->where(function ($x) use ($bus) {
                    $x->whereHas('cliente', fn($c) => $c->where('nombre', 'like', "%{$bus}%")->orWhere('razon_social', 'like', "%{$bus}%"))
                      ->orWhereHas('obligacion', fn($o) => $o->where('nombre', 'like', "%{$bus}%"));
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
    // ACCIONES: Obligación
    // =========================================================

    public function iniciarObligacion(int $id): void
    {
        $o = $this->findMine($id);

        if ($o->estatus !== 'asignada') {
            session()->flash('error', 'Solo puedes iniciar obligaciones en estatus asignada.');
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
        $this->reset(['archivo', 'numero_operacion', 'fecha_vencimiento']);

        $this->selectedId = $o->id;
        $this->numero_operacion = $o->numero_operacion;
        $this->fecha_finalizado = optional($o->fecha_finalizado)->toDateString();

        $this->openModal = true;
    }

    public function saveResult(): void
    {
        $this->validate();

        $o = $this->findMine((int) $this->selectedId);

        // ⛔ Verificación de tareas pendientes
        if ($this->hayTareasPendientes($o->id)) {
            session()->flash('error', 'No puedes guardar el resultado hasta cerrar todas las tareas ligadas.');
            return;
        }

        // 📎 Subida de archivo (si se seleccionó uno)
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

        $this->reset(['openModal', 'selectedId', 'archivo', 'numero_operacion', 'fecha_vencimiento']);

        session()->flash('success', 'Resultado guardado correctamente.');
    }

    // =========================================================
    // SUBIDA DE ARCHIVO (Laravel Storage y/o Google Drive)
    // =========================================================

    private function subirArchivoResultado(ObligacionClienteContador $obligacion, UploadedFile $file): array
{
    $out = ['storage' => null, 'drive' => null];
    $cliente = $obligacion->cliente;
    $despacho = $cliente->despacho;

    $nombre = now()->format('Ymd_His') . '_' . Str::slug($obligacion->obligacion->nombre ?? 'archivo') . '.' . $file->getClientOriginalExtension();

    // ✅ Laravel Storage
    if (in_array($despacho->politica_almacenamiento, ['storage_only', 'both'])) {
        $carpeta = "clientes/{$cliente->id}/obligaciones/{$obligacion->id}";
        $out['storage'] = $file->storeAs($carpeta, $nombre, 'public');
    }

    // ✅ Google Drive
    if (in_array($despacho->politica_almacenamiento, ['drive_only', 'both'])) {
        $folderId = null;

        // 🔍 Buscar drive_folder_id real desde tabla carpeta_drive
        if ($obligacion->carpeta_drive_id) {
            $cd = \App\Models\CarpetaDrive::find($obligacion->carpeta_drive_id);
            $folderId = $cd?->drive_folder_id;
        }

        if ($folderId) {
            try {
                $drive = app(\App\Services\DriveService::class);

                $res = $drive->subirArchivo(
                    $nombre,
                    $file, // ✅ Pasar objeto completo, NO file_get_contents
                    $folderId,
                    $file->getMimeType()
                );

                // Guardar enlace si lo devuelve
                if (is_string($res)) {
                    $out['drive'] = $res;
                } elseif (is_array($res) && isset($res['webViewLink'])) {
                    $out['drive'] = $res['webViewLink'];
                }
            } catch (\Exception $e) {
                \Log::error('❌ Error al subir archivo a Drive: ' . $e->getMessage());
                $this->addError('archivo', 'Error al subir archivo a Google Drive.');
            }
        } else {
            $this->addError('archivo', 'No se encontró carpeta de destino en Drive.');
        }
    }

    return $out;
}


    // =========================================================
    // HELPERS
    // =========================================================

    private function findMine(int $id): ObligacionClienteContador
    {
        return ObligacionClienteContador::where('contador_id', Auth::id())->findOrFail($id);
    }

    private function hayTareasPendientes(int $obligacionId): bool
    {
        $terminadas = ['realizada', 'revisada', 'cerrada'];

        return TareaAsignada::where('obligacion_cliente_contador_id', $obligacionId)
            ->whereNotIn('estatus', $terminadas)
            ->exists();
    }

    private function cargarEjerciciosDisponibles(): void
    {
        $this->ejerciciosDisponibles = ObligacionClienteContador::query()
            ->where('contador_id', Auth::id())
            ->whereNotNull('fecha_vencimiento')
            ->selectRaw('YEAR(fecha_vencimiento) as anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn($v) => (string) $v)
            ->values()
            ->all();
    }

    private function cargarMesesDisponibles(): void
    {
        if (!$this->ejercicioSeleccionado) {
            $this->mesesDisponibles = [];
            $this->mesSeleccionado = null;
            return;
        }

        $this->mesesDisponibles = ObligacionClienteContador::query()
            ->where('contador_id', Auth::id())
            ->whereNotNull('fecha_vencimiento')
            ->whereYear('fecha_vencimiento', (int) $this->ejercicioSeleccionado)
            ->selectRaw('MONTH(fecha_vencimiento) as mes')
            ->distinct()
            ->orderBy('mes')
            ->pluck('mes')
            ->map(fn($v) => (string) $v)
            ->values()
            ->all();
    }
}
