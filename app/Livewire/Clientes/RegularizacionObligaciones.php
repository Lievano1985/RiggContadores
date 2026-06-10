<?php
/**
 * Componente Livewire: RegularizacionObligaciones
 * Autor: Luis Liévano - JL3 Digital
 *
 * Lógica:
 * - Permite generar obligaciones atrasadas para meses pasados y el mes actual (NO futuro).
 * - Año: 2010 -> año actual.
 * - Mes: si año actual => 1..mes actual (incluye mes actual). Si año pasado => 1..12.
 * - Muestra solo obligaciones periódicas (excluye únicas).
 * - Unifica fuente de obligaciones (sin duplicidad render vs computed).
 */

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Obligacion;
use App\Models\ObligacionClienteContador;
use App\Models\RegularizacionFiscal;
use Livewire\Component;
use App\Services\GeneradorObligaciones;
use Carbon\Carbon;

class RegularizacionObligaciones extends Component
{
    public Cliente $cliente;

    public array $obligacionesSeleccionadas = [];
    public int $mes;
    public int $anio;

    public array $resumen = [];
    public string $buscarObligacion = '';

    protected GeneradorObligaciones $generador;

    public function boot(GeneradorObligaciones $generador)
    {
        $this->generador = $generador;
    }

    public function mount()
    {
        $this->anio = now()->year;
        $this->mes  = now()->month; // incluye mes en curso (permitido)
    }

    /* ============================================================
     | Reglas de validación
     |============================================================ */
    protected function rules(): array
    {
        return [
            'obligacionesSeleccionadas' => 'required|array|min:1',
            'obligacionesSeleccionadas.*' => 'integer|exists:obligaciones,id',
            'mes'  => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2010|max:' . now()->year,
        ];
    }

    /* ============================================================
     | Periodos permitidos
     |============================================================ */
    public function getAniosDisponiblesProperty(): array
    {
        return range(2010, now()->year);
    }

    public function getMesesDisponiblesProperty(): array
    {
        $anioActual = now()->year;
        $mesActual  = now()->month;

        // Año pasado: 1..12
        if ($this->anio < $anioActual) {
            return range(1, 12);
        }

        // Año actual: 1..mes actual (incluye el que está corriendo)
        if ($this->anio === $anioActual) {
            return range(1, $mesActual);
        }

        // No debería pasar por validación (anio max = año actual)
        return [];
    }

    /* ============================================================
     | Normalización al cambiar año/mes/selecciones
     |============================================================ */
    public function updatedAnio(): void
    {
        // Si el mes elegido ya no es válido para el nuevo año, ajusta
        $meses = $this->mesesDisponibles;

        if (empty($meses)) {
            // En la práctica no debería ocurrir por validación
            $this->mes = now()->month;
        } elseif (!in_array($this->mes, $meses, true)) {
            $this->mes = end($meses); // por UX: lo más “cercano” (último disponible)
        }

        $this->reset('resumen');
    }

    public function updatedMes(): void
    {
        $this->reset('resumen');
    }

    public function updatedObligacionesSeleccionadas(): void
    {
        $this->reset('resumen');
    }

    /* ============================================================
     | Generación
     |============================================================ */
     public function generar()
     {
         $this->validate();
     
         $periodoSeleccionado = Carbon::create($this->anio, $this->mes, 1)->startOfMonth();
         $periodoActual       = now()->startOfMonth();
     
         if ($periodoSeleccionado->greaterThan($periodoActual)) {
             $this->addError('mes', 'No se pueden generar obligaciones para meses futuros.');
             return;
         }
     
         $resultado = $this->generador->generarManualClienteObligaciones(
             $this->cliente,
             $this->obligacionesSeleccionadas,
             $this->anio,
             $this->mes
         );
     
         $this->resumen = $resultado;

         $this->registrarRegularizacion($resultado);
     
         $this->dispatch('DatosFiscalesActualizados');
         $this->dispatch('obligacionActualizada');
         $this->dispatch('notify', message:  'Obligaciónes Generadas correctamente.');

         // ===============================
         // RESET CAMPOS DESPUÉS DE GUARDAR
         // ===============================
         $this->reset([
             'obligacionesSeleccionadas',
             'buscarObligacion',
         ]);
     

         $this->anio = now()->year;
         $this->mes  = now()->month;
     
         // (opcional)
         $this->reset('resumen');
     }

     protected function registrarRegularizacion(array $resultado): void
     {
         $regularizacion = RegularizacionFiscal::create([
             'cliente_id' => $this->cliente->id,
             'user_id' => auth()->id(),
             'anio' => $this->anio,
             'mes' => $this->mes,
             'generadas' => $resultado['generadas'] ?? 0,
             'ya_existian' => $resultado['ya_existian'] ?? 0,
             'omitidas' => $resultado['omitidas'] ?? 0,
             'obligaciones_solicitadas' => $this->obligacionesSeleccionadas,
             'resumen' => $resultado,
         ]);

         $idsGeneradas = $resultado['ids_generadas'] ?? [];
         $idsYaExistian = $resultado['ya_existian_ids'] ?? [];

         $idsExistentes = ObligacionClienteContador::query()
             ->where('cliente_id', $this->cliente->id)
             ->where('ejercicio', $this->anio)
             ->where('mes', $this->mes)
             ->whereIn('obligacion_id', $idsYaExistian)
             ->pluck('id')
             ->all();

         $regularizacion->obligaciones()->syncWithoutDetaching(
             array_values(array_unique([...$idsGeneradas, ...$idsExistentes]))
         );
     }
     

    /* ============================================================
     | Obligaciones (solo periódicas)
     |============================================================ */
    public function getObligacionesFiltradasProperty()
    {
        return Obligacion::query()
            ->where('activa', true)
            // EXCLUIR ÚNICAS (asumiendo campo boolean 'unica')
            ->where('periodicidad', '!=', 'unica')

            ->when($this->buscarObligacion, function ($query) {
                $query->where('nombre', 'like', '%' . $this->buscarObligacion . '%');
            })
            ->orderBy('nombre')
            ->get();
    }
    public function quitarObligacion($id)
    {
        $this->obligacionesSeleccionadas = array_values(
            array_diff($this->obligacionesSeleccionadas, [$id])
        );
    }

    public function getHistorialRegularizacionesProperty()
    {
        return RegularizacionFiscal::query()
            ->where('cliente_id', $this->cliente->id)
            ->with(['usuario', 'obligaciones.obligacion', 'obligaciones.tareasAsignadas'])
            ->latest()
            ->get();
    }
    
    public function render()
    {
        return view('livewire.clientes.regularizacion-obligaciones');
    }
}
