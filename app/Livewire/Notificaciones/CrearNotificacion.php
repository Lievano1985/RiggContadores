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
use App\Models\Solicitud;
use App\Services\BrevoService;
use App\Services\DriveService;
use App\Services\SolicitudHistorialService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class CrearNotificacion extends Component
{
    public $cliente;

    public string $tipoEnvio = 'obligaciones';
    public ?int $solicitudSeleccionada = null;

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
        abort_unless($this->usuarioPuedeCrearNotificaciones(), 403);

        $this->cliente = $cliente;
        $this->autorizarCliente();
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

        $this->obligacionesDisponibles = $query->get()
            ->sortBy(fn ($oc) => mb_strtolower($oc->obligacion->nombre ?? ''))
            ->values();

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
            })->values();
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
        $this->autorizarCliente();

        $this->validate([
            'asunto' => 'required',
            'cc' => 'nullable|string',
            'mensaje' => 'required',
            'tipoEnvio' => 'required|in:obligaciones,solicitudes',
            'obligacionesSeleccionadas' => 'required_if:tipoEnvio,obligaciones|array',
            'solicitudSeleccionada' => 'required_if:tipoEnvio,solicitudes|nullable|integer',
            'archivoIdsSeleccionados' => 'required_if:tipoEnvio,solicitudes|nullable|array',
            'archivoIdsSeleccionados.*' => 'integer|distinct',
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

        if ($this->tipoEnvio === 'solicitudes') {
            $this->enviarSolicitud($correosCc);
            return;
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
        $nombreDespacho = $this->cliente->despacho?->nombre ?: config('app.name');
        $periodoTexto = ($this->mesesManual[(int) $this->periodo_mes] ?? $this->periodo_mes)
            . ' ' . $this->periodo_ejercicio;

        $response = $brevo->enviarNotificacionClientePlantilla(
            $this->cliente->correo,
            $this->cliente->nombre,
            $this->asunto,
            $this->mensaje,
            $periodoTexto,
            $attachments,
            $correosCc,
            $nombreDespacho
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
        $this->autorizarCliente();

        return view('livewire.notificaciones.crear-notificacion', [
            'solicitudesDisponibles' => $this->tipoEnvio === 'solicitudes'
                ? $this->solicitudesCerradas()->get()->filter(fn (Solicitud $solicitud) => $this->archivosResultado($solicitud)->isNotEmpty())
                : collect(),
        ]);
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

    private function usuarioPuedeCrearNotificaciones(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin_despacho', 'supervisor']) ?? false;
    }

    public function updatedTipoEnvio(): void
    {
        $this->reset(['solicitudSeleccionada', 'obligacionesSeleccionadas', 'archivosDisponibles', 'archivoIdsSeleccionados']);
        $this->resetValidation();
    }

    public function updatedSolicitudSeleccionada(): void
    {
        $this->autorizarCliente();
        $this->archivoIdsSeleccionados = [];
        $solicitud = $this->solicitudesCerradas()->find($this->solicitudSeleccionada);
        $this->archivosDisponibles = $solicitud
            ? $this->archivosResultado($solicitud)->map(fn (ArchivoAdjunto $archivo) => [
                'id' => $archivo->id,
                'nombre' => $archivo->nombre,
                'url' => $this->resolverUrlArchivo($archivo),
                'origen_tipo' => 'solicitud',
                'origen_nombre' => $solicitud->titulo,
                'detalle' => 'Archivo de resultado',
            ])->values()->all()
            : [];
    }

    private function autorizarCliente(): void
    {
        $usuario = auth()->user();
        abort_unless($this->usuarioPuedeCrearNotificaciones(), 403);
        abort_unless($usuario->hasRole('super_admin') || (int) $usuario->despacho_id === (int) $this->cliente->despacho_id, 403);
    }

    private function solicitudesCerradas()
    {
        return Solicitud::query()
            ->where('cliente_id', $this->cliente->id)
            ->where('estado', 'cerrada')
            ->with([
                'archivos',
                'requerimientos' => fn ($query) => $query->where('tipo', 'resultado')
                    ->whereIn('estado', ['respondido', 'validado'])->with('archivos'),
            ])
            ->orderByDesc('cerrada_at');
    }

    private function archivosResultado(Solicitud $solicitud): Collection
    {
        $nombresFormulario = collect($solicitud->resumen_formulario)
            ->where('type', 'file')->pluck('value')->flatten()->filter();

        return $solicitud->archivos->toBase()
            ->filter(fn (ArchivoAdjunto $archivo) => $nombresFormulario->containsStrict($archivo->nombre))
            ->concat($solicitud->requerimientos->flatMap->archivos)
            ->filter(fn (ArchivoAdjunto $archivo) => $archivo->tieneArchivoStorage() || $archivo->tieneArchivoDrive())
            ->unique('id');
    }

    private function enviarSolicitud(array $correosCc): void
    {
        if (!filter_var($this->cliente->correo, FILTER_VALIDATE_EMAIL)) {
            $this->addError('correoCliente', 'El cliente necesita un correo válido para recibir los documentos.');
            return;
        }

        $solicitud = $this->solicitudesCerradas()->find($this->solicitudSeleccionada);
        if (!$solicitud) {
            $this->addError('solicitudSeleccionada', 'Selecciona una solicitud cerrada de este cliente.');
            return;
        }

        $archivos = $this->archivosResultado($solicitud)->whereIn('id', $this->archivoIdsSeleccionados);
        if ($archivos->isEmpty() || $archivos->count() !== count($this->archivoIdsSeleccionados)) {
            $this->addError('archivoIdsSeleccionados', 'Selecciona archivos de resultado de esta solicitud.');
            return;
        }

        $contenidos = [];
        foreach ($archivos as $archivo) {
            try {
                $contenido = $archivo->tieneArchivoStorage() && Storage::disk('public')->exists($archivo->archivo)
                    ? Storage::disk('public')->get($archivo->archivo)
                    : ($archivo->tieneArchivoDrive() ? app(DriveService::class)->descargarArchivo($archivo->archivo_drive_url) : null);
            } catch (Throwable $error) {
                report($error);
                $contenido = null;
            }

            if ($contenido === null) {
                $this->addError('archivoIdsSeleccionados', "No se pudo adjuntar {$archivo->nombre}. El correo no se envió.");
                return;
            }

            $contenidos[$archivo->id] = $contenido;
        }

        $adjuntos = $archivos->count() > 3
            ? [$this->empaquetarMultiplesArchivosEnZip($archivos, $contenidos)]
            : $archivos->map(fn (ArchivoAdjunto $archivo) => $this->construirAdjuntoBrevo($archivo, $contenidos[$archivo->id]))->values()->all();

        if (in_array(null, $adjuntos, true)) {
            $this->addError('archivoIdsSeleccionados', 'No se pudieron preparar los adjuntos. El correo no se envió.');
            return;
        }

        $mes = $solicitud->mes;
        $ejercicio = $solicitud->ejercicio;
        $periodo = $mes && $ejercicio ? ($this->mesesManual[(int) $mes] ?? $mes) . ' ' . $ejercicio : $solicitud->titulo;

        $response = app(BrevoService::class)->enviarNotificacionClientePlantilla(
            $this->cliente->correo,
            $this->cliente->nombre ?: $this->cliente->razon_social ?: 'Cliente',
            $this->asunto,
            $this->mensaje,
            $periodo,
            $adjuntos,
            $correosCc,
            $this->cliente->despacho?->nombre ?: config('app.name')
        );

        if (!$response) {
            $this->dispatch('notify', message: 'Error al enviar el correo. Intenta nuevamente.');
            return;
        }

        DB::transaction(function () use ($solicitud, $archivos, $correosCc, $mes, $ejercicio) {
            $notificacion = NotificacionCliente::create([
                'cliente_id' => $this->cliente->id,
                'solicitud_id' => $solicitud->id,
                'user_id' => Auth::id(),
                'asunto' => $this->asunto,
                'cc' => $correosCc ? implode(', ', $correosCc) : null,
                'mensaje' => $this->mensaje,
                'periodo_mes' => $mes,
                'periodo_ejercicio' => $ejercicio,
            ]);
            $notificacion->archivos()->sync($archivos->pluck('id'));
            SolicitudHistorialService::registrar(
                $solicitud,
                'documentos_enviados_cliente',
                'Documentos enviados al cliente',
                $this->asunto,
                Auth::id(),
                datos: ['notificacion_id' => $notificacion->id, 'archivos' => $archivos->pluck('nombre')->all()]
            );
        });

        $this->reset(['solicitudSeleccionada', 'archivosDisponibles', 'archivoIdsSeleccionados', 'asunto', 'cc', 'mensaje']);
        $this->dispatch('notificacion-enviada');
        $this->dispatch('notify', message: 'Notificación enviada correctamente');
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
                    'url' => $this->resolverUrlArchivo($archivo),
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
                    'url' => $this->resolverUrlArchivo($archivo),
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

    private function resolverUrlArchivo(ArchivoAdjunto $archivo): ?string
    {
        if ($archivo->tieneArchivoDrive()) {
            return $archivo->archivo_drive_url;
        }

        if ($archivo->tieneArchivoStorage()) {
            return Storage::disk('public')->url($archivo->archivo);
        }

        return null;
    }

    private function construirAdjuntoBrevo(ArchivoAdjunto $archivo, ?string $contenido = null): ?array
    {
        if (
            $contenido === null && (!$archivo->tieneArchivoStorage() || !Storage::disk('public')->exists($archivo->archivo))
        ) {
            return null;
        }

        $nombreOriginal = $archivo->nombre ?? basename($archivo->archivo ?? '');
        $contenido ??= Storage::disk('public')->get($archivo->archivo);
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

    private function empaquetarMultiplesArchivosEnZip(Collection $archivos, array $contenidos = []): ?array
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
                $nombreOriginal = $archivo->nombre ?? basename($archivo->archivo ?? '');
                $contenido = $contenidos[$archivo->id] ?? Storage::disk('public')->get($archivo->archivo);
                $nombreUnico = $this->resolverNombreZipUnico($nombreOriginal, $nombresUsados);
                if (!$zip->addFromString($nombreUnico, $contenido)) {
                    throw new \RuntimeException('No se pudo agregar un archivo al ZIP.');
                }
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
