<?php

namespace App\Services;

use App\Models\CarpetaDrive;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DriveService
{
    protected $googleService;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google-service-account.json'));
        $client->addScope(Google_Service_Drive::DRIVE);
    
        // =======================
        // CAMBIO: Ajustar hora en local
        // =======================
        if (app()->environment('local')) {
            try {
                // Pedimos la hora al servidor de Google (cabezal Date del response)
                $response = \Illuminate\Support\Facades\Http::get("https://www.googleapis.com/discovery/v1/apis/drive/v3/rest");
                if ($response->successful()) {
                    $googleDate = $response->header('Date'); // Ej: "Thu, 09 Oct 2025 14:12:43 GMT"
                    $timestamp = strtotime($googleDate);
    
                    // Forzar reloj interno del cliente Google
                    if (method_exists($client, 'setClock')) {
                        $client->setClock(function () use ($timestamp) {
                            return $timestamp;
                        });
                    }
    
                    /* \Log::info("⏰ Usando hora de Google en local: {$googleDate}"); */
                }
            } catch (\Exception $e) {
                \Log::warning("⚠️ No se pudo obtener hora de Google, usando reloj local. " . $e->getMessage());
            }
        }
    
        $this->googleService = new Google_Service_Drive($client);
    }
    

    public function crearEstructuraCliente(int $clienteId, string $clienteNombre, string $carpetaDespachoId): ?string
    {
        $carpetaClienteId = $this->crearCarpeta($clienteNombre, $carpetaDespachoId);
        if (!$carpetaClienteId) return null;

        $carpetaPrincipal = CarpetaDrive::create([
            'cliente_id' => $clienteId,
            'nombre' => $clienteNombre,
            'tipo' => 'principal',
            'drive_folder_id' => $carpetaClienteId,
            'parent_id' => null,
        ]);

        $estructura = [
            '1-Documentos y Trámites' => [
                '1-Laboral' => [
                    '1-Nóminas (pagos)',
                    '2-Trabajadores' => ['1-Vigentes', '2-Bajas', '3-Movimientos Al Salario'],
                    '3-Reglamento Actas y Asambleas Laborales' => ['1-Contrato y Reglamento', '2-Proyecto PTU'],
                    '4-Responsivas',
                ],
                '2-Administrador Personas',
                '3-Activos',
                '4-Manuales' => ['1-Aviso de Privacidad', '2-PLD'],
                '5-Actas y Libros Sociales' => ['1-Acta Constitutiva', '2-Asambleas', '3-Socios y Acciones'],
            ],
            '2-Firmas Electrónicas' => ['1-FIEL', '2-CSD', '3-IMSS'],
            '3-Generales' => ['1-Logos', '2-Información General'],
            '4-Obligaciones y Contribuciones' => [
                '1-Estados de Cuenta',
                '2-Impuesto y derechos Estatales' => ['1-Imp. Nomina', '2-Predial', '3-Permisos Municipales'],
                '3-Impuestos Federales',
                '4-IMSS-Infonavit' => [
                    '01-Cédulas de Determinación',
                    '02-Opiniones',
                    '03-Sipare',
                    '04-Prima de Riesgo',
                    '05-Respaldo SUA',
                    '06-Confrontas',
                    '07-Cobranza',
                    '08-Registro Buzón IMSS',
                ],
                '5-Fonacot',
                '6-Buzón Sat' => ['1-Opinión', '2-Constancias SF', '3-Avisos-Solicitudes', '4-Mi Portal', '5-Requerimientos'],
                '7-Contabilidad Reportes_Pólizas',
                '8-Papeles de trabajo',
                '9-Patrimonial',
            ],
        ];

        $crearRecursivo = function ($estructura, $parentDriveId, $parentDbId) use (&$crearRecursivo, $clienteId) {
            foreach ($estructura as $nombre => $contenido) {
                if (is_int($nombre)) {
                    $nombre = $contenido;
                    $contenido = [];
                }

                $folderId = $this->crearCarpeta($nombre, $parentDriveId);
                if (!$folderId) continue;

                $registro = CarpetaDrive::create([
                    'cliente_id' => $clienteId,
                    'nombre' => $nombre,
                    'tipo' => 'subcarpeta',
                    'drive_folder_id' => $folderId,
                    'parent_id' => $parentDbId,
                ]);

                if (is_array($contenido) && !empty($contenido)) {
                    $crearRecursivo($contenido, $folderId, $registro->id);
                }
            }
        };

        $crearRecursivo($estructura, $carpetaClienteId, $carpetaPrincipal->id);
        return $carpetaClienteId;
    }

    public function crearCarpeta(string $nombre, string $parentId = null): ?string
    {
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $nombre,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        if ($parentId) {
            $fileMetadata->setParents([$parentId]);
        }

        try {
            $folder = $this->googleService->files->create($fileMetadata, [
                'fields' => 'id',
                'supportsAllDrives' => true,
            ]);
            return $folder->id ?? null;
        } catch (\Exception $e) {
            \Log::error("❌ No se pudo crear la carpeta '{$nombre}': " . $e->getMessage());
            return null;
        }
    }

    public function subirArchivo(string $nombre, mixed $contenido, string $parentId, string $mimeType = 'application/octet-stream'): ?string
    {
        try {
            if (is_object($contenido) && method_exists($contenido, 'getRealPath')) {
                $contenido = file_get_contents($contenido->getRealPath());
            }

            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $nombre,
                'parents' => [$parentId],
            ]);

            $file = $this->googleService->files->create($fileMetadata, [
                'data' => $contenido,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id',
                'supportsAllDrives' => true,
            ]);

            \Log::info("✅ Archivo subido correctamente a Drive: {$nombre}", [
                'archivo_id' => $file->id,
            ]);

            return $file->id ?? null;
        } catch (\Exception $e) {
            \Log::error('❌ Error al subir archivo a Drive: ' . $e->getMessage());
            return null;
        }
    }
}
