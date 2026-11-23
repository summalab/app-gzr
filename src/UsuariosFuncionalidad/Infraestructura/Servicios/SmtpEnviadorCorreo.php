<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Servicios;

use Core\EnvLoader;
use InvalidArgumentException;
use RuntimeException;
use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;
class SmtpEnviadorCorreo implements EnviadorCorreoInterface
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;
    private int $timeout;

    public function __construct()
    {
        $this->host       = EnvLoader::obtener('SMTP_HOST', 'localhost');
        $this->port       = (int)EnvLoader::obtener('SMTP_PORT', '587');
        $this->encryption = strtolower(EnvLoader::obtener('SMTP_ENCRYPTION', 'tls'));
        $this->user       = EnvLoader::obtener('SMTP_USER');
        $this->pass       = EnvLoader::obtener('SMTP_PASS');
        $this->fromEmail  = EnvLoader::obtener('SMTP_FROM_EMAIL', $this->user);
        $this->fromName   = EnvLoader::obtener('SMTP_FROM_NAME', 'Ecosistema de Mapas');
        $this->timeout    = (int)EnvLoader::obtener('SMTP_TIMEOUT', '15');
    }
  
    public function enviarCorreoVerificacion(string $correoDestino, string $apodoUsuario, string $enlaceVerificacion): void
    {
        $correoLimpio = trim($correoDestino);
        if ($correoLimpio === '' || filter_var($correoLimpio, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException($this->mensaje('usuario.correo_destino_invalido'));
        }

        $cuerpo = $this->mensaje('usuario.correo_verificacion_cuerpo', [
            'apodo' => $apodoUsuario,
            'enlace' => $enlaceVerificacion
        ]);
        $asunto = $this->mensaje('usuario.correo_verificacion_asunto') . ' - ' . $this->fromName;

        $this->enviarPorSmtp($correoLimpio, $asunto, $cuerpo);
    }

    public function enviarCorreoRecuperacion(string $correoDestino, string $apodoUsuario, string $enlaceRecuperacion): void
    {
        $correoLimpio = trim($correoDestino);
        if ($correoLimpio === '' || filter_var($correoLimpio, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException($this->mensaje('usuario.correo_destino_invalido'));
        }

        $cuerpo = $this->mensaje('usuario.correo_recuperacion_cuerpo', [
            'apodo' => $apodoUsuario,
            'enlace' => $enlaceRecuperacion
        ]);
        $asunto = $this->mensaje('usuario.correo_recuperacion_asunto') . ' - ' . $this->fromName;

        $this->enviarPorSmtp($correoLimpio, $asunto, $cuerpo);
    }

    private function mensaje(string $clave, array $parametros = []): string
    {
        $mensajes = require dirname(__DIR__, 3) . '/Shared/Infraestructura/Idiomas/es.php';
        $valor = $mensajes;
        foreach (explode('.', $clave) as $segmento) {
            $valor = is_array($valor) ? ($valor[$segmento] ?? $clave) : $clave;
        }
        if (!is_string($valor)) return $clave;
        foreach ($parametros as $nombre => $reemplazo) {
            $valor = str_replace('{' . $nombre . '}', (string)$reemplazo, $valor);
        }
        return $valor;
    }

    private function enviarPorSmtp(string $destinatario, string $asunto, string $cuerpoTexto): void
    {
        if ($this->fromEmail === '' || filter_var($this->fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Error de configuración SMTP: SMTP_FROM_EMAIL no es válido en .env.');
        }

        $protocolo = ($this->encryption === 'ssl') ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client(
            "{$protocolo}{$this->host}:{$this->port}",
            $errno,
            $errstr,
            $this->timeout
        );

        if ($socket === false) {
            throw new RuntimeException("Error al conectar con SMTP ({$this->host}:{$this->port}): [{$errno}] {$errstr}");
        }

        stream_set_timeout($socket, $this->timeout);

        try {
            $this->leerRespuesta($socket, 220);

            $dominioLocal = gethostname() ?: 'localhost';
            $this->enviarComando($socket, "EHLO {$dominioLocal}", 250);

            if ($this->encryption === 'tls') {
                $this->enviarComando($socket, 'STARTTLS', 220);
                $metodoTls = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                if (!stream_socket_enable_crypto($socket, true, $metodoTls)) {
                    throw new RuntimeException('No se pudo iniciar el cifrado TLS 1.2/1.3 en SMTP.');
                }
                $this->enviarComando($socket, "EHLO {$dominioLocal}", 250);
            }

            if ($this->user !== '' && $this->pass !== '') {
                $this->enviarComando($socket, 'AUTH LOGIN', 334);
                $this->enviarComando($socket, base64_encode($this->user), 334);
                $this->enviarComando($socket, base64_encode($this->pass), 235);
            }

            $this->enviarComando($socket, "MAIL FROM:<{$this->fromEmail}>", 250);
            $this->enviarComando($socket, "RCPT TO:<{$destinatario}>", 250);
            $this->enviarComando($socket, 'DATA', 354);

            $mensaje = $this->construirMensajeMime($destinatario, $asunto, $cuerpoTexto);
            $this->enviarComando($socket, $mensaje . "\r\n.", 250);

            $this->enviarComando($socket, 'QUIT', 221);
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    private function enviarComando($socket, string $comando, int $codigoEsperado): void
    {
        if (fwrite($socket, $comando . "\r\n") === false) {
            throw new RuntimeException('Error al escribir en el socket SMTP.');
        }

        $this->leerRespuesta($socket, $codigoEsperado);
    }

    private function leerRespuesta($socket, int $codigoEsperado): string
    {
        $respuesta = '';

        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }

        if ($respuesta === '') {
            throw new RuntimeException('El servidor SMTP cerró la conexión inesperadamente.');
        }

        $codigoRecibido = (int)substr($respuesta, 0, 3);
        if ($codigoRecibido !== $codigoEsperado) {
            throw new RuntimeException("Error SMTP. Se esperaba {$codigoEsperado}, se recibió: {$respuesta}");
        }

        return $respuesta;
    }

    private function construirMensajeMime(string $destinatario, string $asunto, string $cuerpoTexto): string
    {
        $asuntoCodificado = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
        $remitenteCodificado = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
        $fecha = date('r');
        $messageId = sprintf('<%s.%s@%s>', bin2hex(random_bytes(8)), time(), $this->host);

        // Normalizar saltos de línea y aplicar dot-stuffing (RFC 5321)
        $cuerpoNormalizado = str_replace(["\r\n", "\r"], "\n", $cuerpoTexto);
        $cuerpoNormalizado = str_replace("\n", "\r\n", $cuerpoNormalizado);
        $cuerpoSeguro = preg_replace('/^\./m', '..', $cuerpoNormalizado);

        $cabeceras = [
            "Date: {$fecha}",
            "From: {$remitenteCodificado} <{$this->fromEmail}>",
            "To: <{$destinatario}>",
            "Subject: {$asuntoCodificado}",
            "Message-ID: {$messageId}",
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        return implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpoSeguro;
    }


}


