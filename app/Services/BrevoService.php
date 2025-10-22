<?php
// app/Services/BrevoService.php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\ApiException;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

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
}
