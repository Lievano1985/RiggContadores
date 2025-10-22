@component('mail::message')
<h1 style="font-size: 22px; color: #374151;">🎉 Hola {{ $nombre }}</h1>

<p style="font-size: 16px; color: #4B5563;">
Tu cuenta ha sido creada exitosamente en <strong>RIGG CONTADORES</strong>.
</p>

<p style="font-size: 16px; color: #4B5563;">Aquí tienes tus credenciales de acceso:</p>

<ul style="font-size: 16px; color: #1F2937;">
    <li><strong>📧 Correo:</strong> <code>{{ $email }}</code></li>
    <li><strong>🔐 Contraseña temporal:</strong> <code>{{ $password }}</code></li>
</ul>

<br>

{{-- Botón estilo ámbar --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td align="center">
      <a href="{{ $url }}" target="_blank"
         style="display: inline-block;
                padding: 12px 24px;
                font-size: 16px;
                color: white;
                background-color: #d97706;
                border-radius: 6px;
                text-decoration: none;
                text-align: center;">
        Iniciar sesión
      </a>
    </td>
  </tr>
</table>


<br>

<p style="font-size: 14px; color: #6B7280;">
Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:
</p>

<p style="font-size: 14px; word-break: break-all;">
    🔗 <a href="{{ $url }}">{{ $url }}</a>
</p>

<br>

<p style="font-size: 14px; color: #4B5563;">
Gracias,<br>
<strong>Equipo de RIGG CONTADORES</strong>
</p>
@endcomponent
