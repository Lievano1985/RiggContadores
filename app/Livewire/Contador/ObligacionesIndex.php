<?php

namespace App\Livewire\Contador;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\ObligacionClienteContador;
use App\Models\TareaAsignada;
use App\Models\CarpetaDrive;
use App\Services\DriveService;
use Illuminate\Http\UploadedFile;

class ObligacionesIndex extends Component
{
    use WithPagination, WithFileUploads;

    // === Filtros ===
    public ?string $buscar = '';
    public ?string $estatus = '';
    public ?int $ejercicioSeleccionado = null;
    public ?int $mesSeleccionado = null;

    protected $queryString = [
        'buscar' => ['except' => ''],
        'estatus' => ['except' => ''],
        'ejercicioSeleccionado' => ['except' => null],
        'mesSeleccionado' => ['except' => null],
    ];

    // === Modal y datos ===
    public $openModal = false;
    public ?ObligacionClienteContador $selectedObligacion = null;
    public ?string $numero_operacion = null;
    public $archivo;
    public ?string $fecha_vencimiento = null;

    // === Meses y años ===
    public array $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public array $ejercicios = [];

    public function mount()
    {
        $this->ejercicioSeleccionado = now()->year;
        $this->mesSeleccionado = now()->month;
        $this->ejercicios = range(now()->year - 3, now()->year + 1);
    }

    // === Reglas ===
    protected function rules()
    {
        return [
            'archivo' => ['nullable', 'file', 'mimes:pdf,zip,jpg,png'],
            'numero_operacion' => ['required', 'string', 'max:100'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    // === Mapeo de periodicidades ===
    private function mesesSegunPeriodicidad(string $periodicidad): array
    {
        return match ($periodicidad) {
            'mensual' => range(1, 12),
            'bimestral' => [1, 3, 5, 7, 9, 11],
            'trimestral' => [3, 6, 9, 12],
            'cuatrimestral' => [4, 8, 12],
            'anual' => [12],
            default => [],
        };
    }

    // === Render principal ===
    public function render()
    {
        $mesActual = $this->mesSeleccionado ?? now()->month;

        // 1️⃣ Consulta base
        $query = ObligacionClienteContador::with(['cliente', 'obligacion'])
            ->where('contador_id', Auth::id())
            ->where('ejercicio', $this->ejercicioSeleccionado)
            ->when($this->estatus, fn($q) => $q->where('estatus', $this->estatus))
            ->when($this->buscar, function ($q) {
                $texto = $this->buscar;
                $q->where(function ($w) use ($texto) {
                    $w->whereHas('cliente', function ($c) use ($texto) {
                        $c->where('nombre', 'like', "%{$texto}%")
                          ->orWhere('razon_social', 'like', "%{$texto}%");
                    })->orWhereHas('obligacion', function ($o) use ($texto) {
                        $o->where('nombre', 'like', "%{$texto}%");
                    });
                });
            })
            ->orderBy('fecha_vencimiento');

        // 2️⃣ Paginamos antes del filtrado de periodicidad
        $obligaciones = $query->paginate(10);

        // 3️⃣ Filtramos en memoria las que aplican a este mes
        $obligaciones->setCollection(
            $obligaciones->getCollection()->filter(function ($obligacion) use ($mesActual) {
                $periodicidad = $obligacion->obligacion->periodicidad ?? 'mensual';
                $mesesValidos = $this->mesesSegunPeriodicidad($periodicidad);
                return in_array($mesActual, $mesesValidos);
            })->values()
        );

        return view('livewire.contador.obligaciones-index', [
            'obligaciones' => $obligaciones,
        ]);
    }

    // === Acciones ===
    public function iniciarObligacion(int $id)
    {
        $registro = ObligacionClienteContador::where('contador_id', Auth::id())->findOrFail($id);

        if ($registro->estatus !== 'asignada') {
            session()->flash('error', 'Esta obligación ya fue iniciada o completada.');
            return;
        }

        $registro->update([
            'estatus' => 'en_progreso',
            'fecha_inicio' => now(),
        ]);

        session()->flash('success', 'Obligación iniciada correctamente.');
    }

    public function openResultModal(int $id)
    {
        $this->reset(['archivo', 'numero_operacion', 'fecha_vencimiento']);

        $this->selectedObligacion = ObligacionClienteContador::with('obligacion', 'cliente.despacho')
            ->where('contador_id', Auth::id())
            ->findOrFail($id);

        $this->numero_operacion = $this->selectedObligacion->numero_operacion;
        $this->fecha_vencimiento = optional($this->selectedObligacion->fecha_vencimiento)->format('Y-m-d');

        $this->openModal = true;
    }

    public function saveResult()
    {
        $this->validate();

        $o = $this->selectedObligacion;
        if (!$o || $o->contador_id !== Auth::id()) {
            session()->flash('error', 'No tienes permiso para esta obligación.');
            return;
        }

        if ($this->hayTareasPendientes($o->id)) {
            session()->flash('error', 'No puedes cerrar la obligación: aún hay tareas ligadas sin terminar.');
            return;
        }

        // === Subida de archivo ===
        $upload = $this->subirArchivoResultado($o, $this->archivo);

        $o->archivo_resultado = $upload['storage'] ?? null;
        $o->numero_operacion = $this->numero_operacion;
        $o->fecha_vencimiento = $this->fecha_vencimiento;

        if (in_array($o->estatus, ['en_progreso', 'iniciando'])) {
            $o->estatus = 'declaracion_realizada';
            $o->fecha_termino = now();
        }

        $o->save();

        $this->reset(['openModal', 'archivo', 'numero_operacion', 'fecha_vencimiento']);
        session()->flash('success', 'Resultado guardado correctamente.');
    }

    private function subirArchivoResultado(ObligacionClienteContador $obligacion, ?UploadedFile $file): array
    {
        if (!$file) return [];

        $out = ['storage' => null];
        $nombre = now()->format('Ymd_His') . '_' . Str::slug($obligacion->obligacion->nombre ?? 'archivo') . '.' . $file->getClientOriginalExtension();
        $folder = "clientes/{$obligacion->cliente_id}/obligaciones/{$obligacion->id}";
        $out['storage'] = $file->storeAs($folder, $nombre, 'public');

        return $out;
    }

    private function hayTareasPendientes(int $obligacionId): bool
    {
        return TareaAsignada::where('obligacion_cliente_contador_id', $obligacionId)
            ->whereNotIn('estatus', ['realizada', 'revisada'])
            ->exists();
    }
}
