<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Servicios;

use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;

class NativoMailEnviadorCorreo implements EnviadorCorreoInterface
{
    private string $remitente;
    private string $nombreRemitente;

    public function __construct(
        string $remitente = 'no-reply@tudominio.com',
        string $nombreRemitente = 'Ecosistema de Mapas'
    ) {
        $this->remitente = $remitente;
        $this->nombreRemitente = $nombreRemitente;
    }

    public function enviarCorreoVerificacion(string $correoDestino, string $apodoUsuario, string $enlaceVerificacion): void
    {
        $asunto = 'Activa tu cuenta - ' . $this->nombreRemitente;

        $mensaje = "Hola {$apodoUsuario},\r\n\r\n"
                 . "Gracias por registrarte en nuestra plataforma.\r\n"
                 . "Para activar tu cuenta, haz clic en el siguiente enlace:\r\n\r\n"
                 . "{$enlaceVerificacion}\r\n\r\n"
                 . "Este enlace es válido por 24 horas.\r\n"
                 . "Si no creaste esta cuenta, puedes ignorar este mensaje.\r\n";

        $this->despacharCorreo($correoDestino, $asunto, $mensaje);
    }

    public function enviarCorreoRecuperacion(string $correoDestino, string $apodoUsuario, string $enlaceRecuperacion): void
    {
        $asunto = 'Recuperación de contraseña - ' . $this->nombreRemitente;

        $mensaje = "Hola {$apodoUsuario},\r\n\r\n"
                 . "Recibimos una solicitud para restablecer la contraseña de tu cuenta.\r\n"
                 . "Haz clic en el siguiente enlace para crear una nueva clave:\r\n\r\n"
                 . "{$enlaceRecuperacion}\r\n\r\n"
                 . "Por seguridad, este enlace expirará en 1 hora.\r\n"
                 . "Si no solicitaste este cambio, tu contraseña actual sigue siendo segura.\r\n";

        $this->despacharCorreo($correoDestino, $asunto, $mensaje);
    }

    private function despacharCorreo(string $destinatario, string $asunto, string $mensaje): void
    {
        // Codificación UTF-8 en el asunto para soportar tildes y eñes sin caracteres extraños
        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';

        $cabeceras = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            "From: {$this->nombreRemitente} <{$this->remitente}>",
            "Reply-To: {$this->remitente}",
            'X-Mailer: PHP/' . phpversion()
        ];

        $cabecerasTexto = implode("\r\n", $cabeceras);

        // El operador @ evita que PHP imprima advertencias en pantalla si el servidor local no tiene SMTP
        $enviado = @mail($destinatario, $asuntoCodificado, $mensaje, $cabecerasTexto);

        if (!$enviado) {
            error_log("Fallo al enviar correo nativo hacia: {$destinatario} | Asunto: {$asunto}");
        }
    }
}
