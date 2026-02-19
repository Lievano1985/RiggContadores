<?php
// app/Services/BrevoService.php

namespace App\Services;
use Brevo\Client\Model\SendSmtpEmailAttachment;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\ApiException;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use App\Models\ArchivoAdjunto;

class BrevoService
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', env('BREVO_API_KEY'));

        $this->apiInstance = new TransactionalEmailsApi(
            new Client(),
            $config
        );
    }

    /**
     * Envía correo con las credenciales al cliente utilizando una plantilla de Brevo.
     */
    public function enviarCredencialesCliente(string $email, string $nombre, string $usuario, string $password)
    {
        // URL de acceso al sistema (ajusta según tu entorno)
        $loginUrl = config('app.url') . '/login';

        $sendSmtpEmail = new SendSmtpEmail([
            'to' => [
                [
                    'email' => $email,
                    'name' => $nombre,
                ],
            ],
            'templateId' => 3,
            'params' => [
                'nombre'   => $nombre,
                'email'    => $usuario,
                'password' => $password,
                'url'      => config('app.url') . '/login',
            ],
        ]);

        try {
            $this->apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (ApiException $e) {
            logger()->error('Error al enviar correo con Brevo', [
                'mensaje' => $e->getMessage(),
                'respuesta' => $e->getResponseBody(),
            ]);
        }
    }
    /**
     * Envía notificación personalizada con adjuntos.
     */
    public function enviarNotificacionClientePlantilla(
        string $email,
        string $nombre,
        string $mensaje,
        string $periodo,
        array $attachments = []
     ) {
    
        $data = [
            'to' => [
                [
                    'email' => $email,
                    'name'  => $nombre,
                ],
            ],
            'templateId' => 7,
            'params' => [
                'nombre'  => $nombre,
                'mensaje' => $mensaje,
                'periodo' => $periodo,
                'empresa' => config('app.name'),
            ],
        ];
    
        if (!empty($attachments)) {
    
            $attachmentObjects = [];
    
            foreach ($attachments as $file) {
                $attachmentObjects[] = new \Brevo\Client\Model\SendSmtpEmailAttachment([
                    'name' => $file['name'],
                    'content' => $file['content'],
                ]);
            }
    
            $data['attachment'] = $attachmentObjects;
        }
    
        $sendSmtpEmail = new SendSmtpEmail($data);
    
        try {
            return $this->apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (ApiException $e) {
    
            logger()->error('Error al enviar notificación con Brevo', [
                'mensaje' => $e->getMessage(),
                'respuesta' => $e->getResponseBody(),
            ]);
    
            return false;
        }
    }
    
}
