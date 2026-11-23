<?php

declare(strict_types=1);

namespace Shared\Http;

use InvalidArgumentException;
use Shared\Dominio\Excepciones\ExcepcionNegocio;
use Shared\Dominio\Servicios\RegistradorLogInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Throwable;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

class ManejadorGlobalExcepciones
{
    public function __construct(
        private RegistradorLogInterface $registradorLog,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function manejar(Throwable $excepcion, string $contexto = 'API'): void
    {
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // 1. Excepciones de Reglas de Negocio o Validación de Entrada (HTTP 4xx)
        if ($excepcion instanceof ExcepcionNegocio || $excepcion instanceof InvalidArgumentException) {
            $codigoHttp = $excepcion instanceof ExcepcionNegocio
                && $excepcion->getCode() >= 400
                && $excepcion->getCode() < 500
                    ? $excepcion->getCode()
                    : 422;

            $mensaje = $excepcion instanceof ExcepcionNegocio
                ? $excepcion->getMessage()
                : $this->mensajes->obtener('sistema.validacion_general');

            http_response_code($codigoHttp);
            echo json_encode([
                'success' => false,
                'error'   => $mensaje,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 2. Fallos Técnicos Críticos (HTTP 500 con diagnóstico quirúrgico en log privado)
        $this->registradorLog->registrarErrorCritico($excepcion, $contexto);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => $this->mensajes->obtener('sistema.error_interno'),
        ], JSON_UNESCAPED_UNICODE);
    }
}
