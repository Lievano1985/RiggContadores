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
use App\Models\TareaAsignada;
use App\Services\BrevoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class CrearNotificacion extends Component
{
    public $cliente;

    public $periodo_mes;
    public $periodo_ejercicio;

    public $asunto = '';
    public $cc = '';
    public $mensaje = '';

    public $obligacionesDisponibles = [];
    public $obligacionesSeleccionadas = [];

    public $archivosDisponibles = [];
    public $archivoIdsSeleccionados = [];
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
        $this->archivosDisponibles = [];
        $this->archivoIdsSeleccionados = [];
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
            ->whereHas('obligacion', function ($q) {
                $q->where('requiere_envio_cliente', true);
            })
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

    public function quitarArchivo($id): void
    {
        $this->archivoIdsSeleccionados = array_values(
            array_diff($this->archivoIdsSeleccionados, [(string) $id, (int) $id])
        );
    }



    // ============================
    // Cuando cambia periodo
    // ============================

    public function updatedPeriodoMes()
    {
        $this->cargarObligaciones();
        $this->obligacionesSeleccionadas = [];
        $this->archivosDisponibles = [];
        $this->archivoIdsSeleccionados = [];
    }



    // ============================
    // Cuando se seleccionan obligaciones
    // ============================

    public function updatedObligacionesSeleccionadas()
    {
        $this->cargarArchivosDisponibles();
    }

    // ============================
    // Guardar notificación
    // ============================

    public function guardar()
    {

        $this->validate([
            'asunto' => 'required',
            'cc' => 'nullable|string',
            'mensaje' => 'required',
            'obligacionesSeleccionadas' => 'required|array|min:1',
            'archivoIdsSeleccionados' => 'nullable|array',
        ]);

        $correosCc = $this->parsearCorreosCc();

        if (!empty($correosCc)) {
            $validator = Validator::make(
                ['cc' => $correosCc],
                ['cc.*' => 'email']
            );

            if ($validator->fails()) {
                $this->addError('cc', 'Uno o más correos en CC no tienen un formato válido.');
                return;
            }
        }

        // 1️⃣ Crear registro en BD (temporal)
        $notificacion = NotificacionCliente::create([
            'cliente_id' => $this->cliente->id,
            'user_id' => Auth::id(),
            'asunto' => $this->asunto,
            'cc' => !empty($correosCc) ? implode(', ', $correosCc) : null,
            'mensaje' => $this->mensaje,
            'periodo_mes' => $this->periodo_mes,
            'periodo_ejercicio' => $this->periodo_ejercicio,
        ]);

        // 2️⃣ Guardar relaciones obligaciones
        $notificacion->obligaciones()
            ->sync($this->obligacionesSeleccionadas);

        // 3️⃣ Guardar relaciones archivos
        $notificacion->archivos()
            ->sync($this->archivoIdsSeleccionados);

        // 4️⃣ Preparar adjuntos para Brevo
        $attachments = [];

        $archivos = ArchivoAdjunto::whereIn(
            'id',
            $this->archivoIdsSeleccionados
        )->get();

        $archivosValidos = $archivos->filter(function (ArchivoAdjunto $archivo) {
            return $archivo->tieneArchivoStorage() &&
                Storage::disk('public')->exists($archivo->archivo);
        })->values();

        if ($archivosValidos->count() > 3) {
            $adjuntoZip = $this->empaquetarMultiplesArchivosEnZip($archivosValidos);
            if ($adjuntoZip) {
                $attachments[] = $adjuntoZip;
            }
        } else {
            foreach ($archivosValidos as $archivo) {
                $adjunto = $this->construirAdjuntoBrevo($archivo);
                if ($adjunto) {
                    $attachments[] = $adjunto;
                }
            }
        }
        
     
        // 5️⃣ Enviar correo con Brevo
        $brevo = new BrevoService();

        $response = $brevo->enviarNotificacionClientePlantilla(
            $this->cliente->correo,
            $this->cliente->nombre,
            $this->asunto,
            $this->mensaje,
            $this->periodo_mes . '/' . $this->periodo_ejercicio,
            $attachments,
            $correosCc
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
        $this->cc = '';
        $this->mensaje = '';
        $this->obligacionesSeleccionadas = [];
        $this->archivosDisponibles = [];
        $this->archivoIdsSeleccionados = [];

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

    private function parsearCorreosCc(): array
    {
        if (!is_string($this->cc) || trim($this->cc) === '') {
            return [];
        }

        return collect(preg_split('/[;,]+/', $this->cc) ?: [])
            ->map(fn ($correo) => trim($correo))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function cargarArchivosDisponibles(): void
    {
        if (empty($this->obligacionesSeleccionadas)) {
            $this->archivosDisponibles = [];
            $this->archivoIdsSeleccionados = [];
            return;
        }

        $obligaciones = ObligacionClienteContador::query()
            ->whereIn('id', $this->obligacionesSeleccionadas)
            ->with(['obligacion:id,nombre'])
            ->get()
            ->keyBy('id');

        $archivosObligacion = ArchivoAdjunto::query()
            ->where('archivoable_type', ObligacionClienteContador::class)
            ->whereIn('archivoable_id', $this->obligacionesSeleccionadas)
            ->get()
            ->map(function (ArchivoAdjunto $archivo) use ($obligaciones) {
                $obligacion = $obligaciones->get((int) $archivo->archivoable_id);

                return [
                    'id' => $archivo->id,
                    'nombre' => $archivo->nombre ?? basename($archivo->archivo ?: ''),
                    'origen_tipo' => 'obligacion',
                    'origen_id' => (int) $archivo->archivoable_id,
                    'origen_nombre' => $obligacion?->obligacion?->nombre ?? 'Obligacion',
                    'detalle' => 'Archivo de obligacion',
                ];
            });

        $tareas = TareaAsignada::query()
            ->whereIn('obligacion_cliente_contador_id', $this->obligacionesSeleccionadas)
            ->with(['tareaCatalogo:id,nombre', 'obligacionClienteContador.obligacion:id,nombre'])
            ->get();

        $tareasPorId = $tareas->keyBy('id');

        $archivosTarea = ArchivoAdjunto::query()
            ->where('archivoable_type', TareaAsignada::class)
            ->whereIn('archivoable_id', $tareasPorId->keys()->all())
            ->get()
            ->map(function (ArchivoAdjunto $archivo) use ($tareasPorId) {
                $tarea = $tareasPorId->get((int) $archivo->archivoable_id);

                return [
                    'id' => $archivo->id,
                    'nombre' => $archivo->nombre ?? basename($archivo->archivo ?: ''),
                    'origen_tipo' => 'tarea',
                    'origen_id' => (int) $archivo->archivoable_id,
                    'origen_nombre' => $tarea?->tareaCatalogo?->nombre ?? 'Tarea',
                    'detalle' => 'Tarea de ' . ($tarea?->obligacionClienteContador?->obligacion?->nombre ?? 'obligacion'),
                ];
            });

        $this->archivosDisponibles = $archivosObligacion
            ->concat($archivosTarea)
            ->unique('id')
            ->sortBy([
                ['origen_tipo', 'asc'],
                ['origen_nombre', 'asc'],
                ['nombre', 'asc'],
            ])
            ->values()
            ->all();

        $this->archivoIdsSeleccionados = collect($this->archivosDisponibles)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function construirAdjuntoBrevo(ArchivoAdjunto $archivo): ?array
    {
        if (
            !$archivo->tieneArchivoStorage() ||
            !Storage::disk('public')->exists($archivo->archivo)
        ) {
            return null;
        }

        $nombreOriginal = $archivo->nombre ?? basename($archivo->archivo);
        $contenido = Storage::disk('public')->get($archivo->archivo);
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if (in_array($extension, ['cer', 'key'], true)) {
            return $this->empaquetarArchivoEnZip($nombreOriginal, $contenido);
        }

        return [
            'name' => $nombreOriginal,
            'content' => base64_encode($contenido),
        ];
    }

    private function empaquetarArchivoEnZip(string $nombreOriginal, string $contenido): ?array
    {
        $tmpBase = tempnam(sys_get_temp_dir(), 'brevo_zip_');
        if ($tmpBase === false) {
            logger()->error('No se pudo crear archivo temporal para zip.', [
                'archivo' => $nombreOriginal,
            ]);
            return null;
        }

        $zipPath = $tmpBase . '.zip';
        @unlink($tmpBase);

        try {
            $zip = new ZipArchive();
            $status = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($status !== true) {
                logger()->error('No se pudo abrir zip temporal para adjunto Brevo.', [
                    'archivo' => $nombreOriginal,
                    'status' => $status,
                ]);
                @unlink($zipPath);
                return null;
            }

            $zip->addFromString($nombreOriginal, $contenido);
            $zip->close();

            $zipContenido = @file_get_contents($zipPath);
            @unlink($zipPath);

            if ($zipContenido === false) {
                logger()->error('No se pudo leer zip temporal para adjunto Brevo.', [
                    'archivo' => $nombreOriginal,
                ]);
                return null;
            }

            $baseNombre = pathinfo($nombreOriginal, PATHINFO_FILENAME);

            return [
                'name' => $baseNombre . '.zip',
                'content' => base64_encode($zipContenido),
            ];
        } catch (Throwable $e) {
            @unlink($zipPath);
            logger()->error('Error al comprimir adjunto para Brevo.', [
                'archivo' => $nombreOriginal,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function empaquetarMultiplesArchivosEnZip(Collection $archivos): ?array
    {
        $tmpBase = tempnam(sys_get_temp_dir(), 'brevo_lote_');
        if ($tmpBase === false) {
            logger()->error('No se pudo crear archivo temporal para zip global de adjuntos.');
            return null;
        }

        $zipPath = $tmpBase . '.zip';
        @unlink($tmpBase);

        try {
            $zip = new ZipArchive();
            $status = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($status !== true) {
                logger()->error('No se pudo abrir zip global de adjuntos para Brevo.', [
                    'status' => $status,
                ]);
                @unlink($zipPath);
                return null;
            }

            $nombresUsados = [];

            foreach ($archivos as $archivo) {
                $nombreOriginal = $archivo->nombre ?? basename($archivo->archivo);
                $contenido = Storage::disk('public')->get($archivo->archivo);
                $nombreUnico = $this->resolverNombreZipUnico($nombreOriginal, $nombresUsados);
                $zip->addFromString($nombreUnico, $contenido);
            }

            $zip->close();

            $zipContenido = @file_get_contents($zipPath);
            @unlink($zipPath);

            if ($zipContenido === false) {
                logger()->error('No se pudo leer zip global de adjuntos para Brevo.');
                return null;
            }

            $nombreZip = 'adjuntos_' . now()->format('Ymd_His') . '.zip';

            return [
                'name' => $nombreZip,
                'content' => base64_encode($zipContenido),
            ];
        } catch (Throwable $e) {
            @unlink($zipPath);
            logger()->error('Error al comprimir adjuntos en zip global para Brevo.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function resolverNombreZipUnico(string $nombre, array &$nombresUsados): string
    {
        if (!isset($nombresUsados[$nombre])) {
            $nombresUsados[$nombre] = 1;
            return $nombre;
        }

        $indice = $nombresUsados[$nombre];
        $nombresUsados[$nombre] = $indice + 1;

        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $extension = pathinfo($nombre, PATHINFO_EXTENSION);
        $sufijo = '_' . $indice;

        return $extension !== ''
            ? $base . $sufijo . '.' . $extension
            : $base . $sufijo;
    }
}
