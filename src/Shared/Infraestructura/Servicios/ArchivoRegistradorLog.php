<?php

declare(strict_types=1);

namespace Shared\Infraestructura\Servicios;

use Shared\Dominio\Servicios\RegistradorLogInterface;
use Throwable;

class ArchivoRegistradorLog implements RegistradorLogInterface
{
    public function registrarErrorCritico(Throwable $excepcion, string $contexto = 'Sistema'): void
    {
        $rutaRelativa = str_replace(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR, '', $excepcion->getFile());
        $metodoHttp   = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri          = $_SERVER['REQUEST_URI'] ?? '-';

        $entrada = sprintf(
            "[%s] [%s] %s | Archivo: %s (Línea %d) | Ruta: %s %s\nTrace:\n%s\n%s",
            date('Y-m-d H:i:s'),
            $contexto,
            $excepcion->getMessage(),
            $rutaRelativa,
            $excepcion->getLine(),
            $metodoHttp,
            $uri,
            $excepcion->getTraceAsString(),
            str_repeat('-', 80)
        );

        error_log($entrada);
    }

    public function registrarAdvertencia(string $mensaje, string $contexto = 'Sistema'): void
    {
        error_log(sprintf("[%s] [WARN - %s] %s", date('Y-m-d H:i:s'), $contexto, $mensaje));
    }
}
