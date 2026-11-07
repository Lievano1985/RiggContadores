<?php

namespace App\Services;

use App\Models\CarpetaDrive;
use App\Models\Solicitud;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SolicitudArchivosFormulario
{
    public function guardar(Solicitud $solicitud, array $respuestas, string $propiedad, array $nombresEditados = []): array
    {
        $cliente = $solicitud->cliente;
        $politica = $cliente?->despacho?->politica_almacenamiento;
        $rutasLocales = [];
        $archivosDrive = [];

        try {
            return DB::transaction(function () use ($solicitud, $respuestas, $propiedad, $nombresEditados, $cliente, $politica, &$rutasLocales, &$archivosDrive) {
                $archivosGuardados = [];
                $consecutivo = $solicitud->archivos()->count();

                foreach ($solicitud->campos_formulario as $campo) {
                    $key = $campo['key'] ?? null;
                    if (!$key || ($campo['type'] ?? null) !== 'file' || empty($respuestas[$key])) {
                        continue;
                    }

                    $nombres = (array) ($solicitud->datos_formulario[$key] ?? []);
                    foreach ($respuestas[$key] as $archivo) {
                        $extension = strtolower($archivo->getClientOriginalExtension());
                        if (preg_match('/^(?:php\d*|phtml|phar|cgi|pl|shtml)$/i', $extension)) {
                            throw ValidationException::withMessages([$propiedad . '.' . $key => 'Este tipo de archivo no está permitido.']);
                        }

                        $nombreEditado = trim($nombresEditados[sha1($archivo->getFilename())] ?? '');
                        $nombre = self::nombreArchivo($cliente?->rfc ?? '', $nombreEditado, $archivo, ++$consecutivo);
                        $ruta = null;
                        $urlDrive = null;

                        if (in_array($politica, ['storage_only', 'both'], true)) {
                            $ruta = $archivo->storeAs("adjuntos/solicitudes/{$solicitud->id}/" . Str::uuid(), $nombre, 'public');
                            if ($ruta) {
                                $rutasLocales[] = $ruta;
                            }
                        }

                        if (in_array($politica, ['drive_only', 'both'], true)) {
                            $folderId = CarpetaDrive::find($solicitud->carpeta_drive_id)?->drive_folder_id;
                            if ($folderId) {
                                $drive = app(DriveService::class);
                                $id = $drive->subirArchivo($nombre, $archivo, $folderId, $archivo->getMimeType());
                                if ($id) {
                                    $archivosDrive[] = $id;
                                    $urlDrive = "https://drive.google.com/file/d/{$id}/view";
                                }
                            }
                        }

                        if (!$ruta && !$urlDrive) {
                            throw ValidationException::withMessages([$propiedad . '.' . $key => 'No se pudo guardar el archivo. Intenta nuevamente.']);
                        }

                        $solicitud->archivos()->create([
                            'nombre' => $nombre,
                            'archivo' => $ruta ?: null,
                            'archivo_drive_url' => $urlDrive,
                        ]);
                        $nombres[] = $nombre;
                    }

                    $archivosGuardados[$key] = $nombres;
                }

                return $archivosGuardados;
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($rutasLocales);
            foreach ($archivosDrive as $id) {
                app(DriveService::class)->eliminarArchivo($id);
            }
            throw $error;
        }
    }
    public static function nombreArchivo(string $rfc, string $nombre, $archivo, int $consecutivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreOriginal = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
        if ($extension !== '' && str_ends_with(strtolower($nombre), ".{$extension}")) {
            $nombre = substr($nombre, 0, -strlen($extension) - 1);
        }

        $rfcNormalizado = mb_strtoupper(trim($rfc));
        $nombreBase = $nombre !== '' ? $nombre : $nombreOriginal;
        $nombreNormalizado = preg_replace('~[<>:"/\\\\|?*\x00-\x1F]~u', '-', $nombreBase);
        $nombreNormalizado = mb_strcut(rtrim($nombreNormalizado, ' .'), 0, 150) ?: 'archivo';
        $sufijo = preg_match('/^[a-z0-9]{1,16}$/', $extension) ? ".{$extension}" : '';

        return "{$rfcNormalizado}-{$nombreNormalizado}-{$consecutivo}{$sufijo}";
    }

}
