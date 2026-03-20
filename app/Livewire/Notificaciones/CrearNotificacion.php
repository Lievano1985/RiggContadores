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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

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
