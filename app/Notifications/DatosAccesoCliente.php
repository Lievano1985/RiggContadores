<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DatosAccesoCliente extends Notification
{
    public $email;
    public $password;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Bienvenido a RIGG')
            ->markdown('emails.acceso-cliente', [
                'nombre' => $notifiable->name,
                'email' => $this->email,
                'password' => $this->password,
                'url' => url('/login'),
            ]);
    }
    
}
