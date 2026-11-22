<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Servicios;

use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;

class LogLocalEnviadorCorreo implements EnviadorCorreoInterface
{
    private string $rutaArchivoLog;

    public function __construct()
    {
        // Apunta a la raíz del proyecto: app-gzr/storage/logs/correos_enviados.log
        $directorioLogs = __DIR__ . '/../../../../storage/logs';

        if (!is_dir($directorioLogs)) {
            mkdir($directorioLogs, 0775, true);
        }

        $this->rutaArchivoLog = $directorioLogs . '/correos_enviados.log';
    }

    public function enviarCorreoVerificacion(string $correoDestino, string $apodoUsuario, string $enlaceVerificacion): void
    {
        $contenido = "Hola {$apodoUsuario},\n\n"
                   . "Gracias por registrarte. Haz clic en el siguiente enlace para activar tu cuenta:\n"
                   . "{$enlaceVerificacion}\n";

        $this->escribirEnLog('VERIFICACIÓN DE CUENTA', $correoDestino, $contenido);
    }

    public function enviarCorreoRecuperacion(string $correoDestino, string $apodoUsuario, string $enlaceRecuperacion): void
    {
        $contenido = "Hola {$apodoUsuario},\n\n"
                   . "Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente enlace (válido por 1 hora):\n"
                   . "{$enlaceRecuperacion}\n";

        $this->escribirEnLog('RECUPERACIÓN DE CONTRASEÑA', $correoDestino, $contenido);
    }

    private function escribirEnLog(string $tipoCorreo, string $destinatario, string $cuerpo): void
    {
        $fecha = date('Y-m-d H:i:s');
        $separador = str_repeat('=', 70);

        $registro = "[{$fecha}] [{$tipoCorreo}] Para: {$destinatario}\n"
                  . "{$separador}\n"
                  . "{$cuerpo}"
                  . "{$separador}\n\n";

        file_put_contents($this->rutaArchivoLog, $registro, FILE_APPEND | LOCK_EX);
    }
}
