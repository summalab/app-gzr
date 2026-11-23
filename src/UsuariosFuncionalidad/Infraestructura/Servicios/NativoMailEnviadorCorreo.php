<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Servicios;

use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;
class NativoMailEnviadorCorreo implements EnviadorCorreoInterface
{
    private string $remitente;
    private string $nombreRemitente;

    public function __construct()
    {
        $this->remitente = 'no-reply@tudominio.com';
        $this->nombreRemitente = 'Ecosistema de Mapas';
    }

    public function enviarCorreoVerificacion(string $correoDestino, string $apodoUsuario, string $enlaceVerificacion): void
    {
        $asunto = $this->mensaje('usuario.correo_verificacion_asunto') . ' - ' . $this->nombreRemitente;
        $mensaje = $this->mensaje('usuario.correo_verificacion_cuerpo', [
            'apodo' => $apodoUsuario,
            'enlace' => $enlaceVerificacion
        ]);

        $this->despacharCorreo($correoDestino, $asunto, $mensaje);
    }

    public function enviarCorreoRecuperacion(string $correoDestino, string $apodoUsuario, string $enlaceRecuperacion): void
    {
        $asunto = $this->mensaje('usuario.correo_recuperacion_asunto') . ' - ' . $this->nombreRemitente;
        $mensaje = $this->mensaje('usuario.correo_recuperacion_cuerpo', [
            'apodo' => $apodoUsuario,
            'enlace' => $enlaceRecuperacion
        ]);

        $this->despacharCorreo($correoDestino, $asunto, $mensaje);
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
