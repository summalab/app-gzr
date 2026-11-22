<?php

declare(strict_types=1);

namespace Shared\Http;

use InvalidArgumentException;
use Shared\Dominio\Servicios\RegistradorLogInterface;
use Throwable;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

class ManejadorGlobalExcepciones
{
    public function __construct(
        private RegistradorLogInterface $registradorLog
    ) {}

    public function manejar(Throwable $excepcion, string $contexto = 'API'): void
    {
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // 1. Excepciones de Reglas de Negocio o Validación de Entrada (HTTP 4xx)
        if ($excepcion instanceof ReglaNegocioExcepcion || $excepcion instanceof InvalidArgumentException) {
            $codigoHttp = ($excepcion instanceof ReglaNegocioExcepcion && $excepcion->getCode() >= 400 && $excepcion->getCode() < 500)
                ? $excepcion->getCode()
                : 422;

            http_response_code($codigoHttp);
            echo json_encode([
                'success' => false,
                'error'   => $excepcion->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 2. Fallos Técnicos Críticos (HTTP 500 con diagnóstico quirúrgico en log privado)
        $this->registradorLog->registrarErrorCritico($excepcion, $contexto);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Ocurrió un error interno en el servidor. Inténtalo más tarde.',
        ], JSON_UNESCAPED_UNICODE);
    }
}
