<?php

declare(strict_types=1);

namespace Shared\Http;

use InvalidArgumentException;
use Shared\Dominio\Excepciones\ExcepcionNegocio;
use Shared\Dominio\Servicios\RegistradorLogInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Throwable;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Excepciones\InvarianteUsuarioExcepcion;
use UbicacionesFuncionalidad\Dominio\Excepciones\InvarianteUbicacionExcepcion;

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
        if ($excepcion instanceof ExcepcionNegocio
            || $excepcion instanceof InvalidArgumentException
            || $excepcion instanceof InvarianteUsuarioExcepcion
            || $excepcion instanceof InvarianteUbicacionExcepcion) {
            $codigoHttp = $excepcion instanceof ExcepcionNegocio
                && $excepcion->getCode() >= 400
                && $excepcion->getCode() < 500
                    ? $excepcion->getCode()
                    : 422;

            if ($excepcion instanceof InvarianteUsuarioExcepcion) {
                $mensaje = $this->mensajes->obtener($excepcion->obtenerClaveMensaje());
            } elseif ($excepcion instanceof InvarianteUbicacionExcepcion) {
                $mensaje = $this->mensajes->obtener($excepcion->obtenerClaveMensaje());
            } elseif ($excepcion instanceof ExcepcionNegocio) {
                $mensajeTexto = $excepcion->getMessage();
                if (str_starts_with($mensajeTexto, 'usuario.') || str_starts_with($mensajeTexto, 'ubicacion.') || str_starts_with($mensajeTexto, 'sistema.')) {
                    $parametros = str_starts_with($mensajeTexto, 'usuario.contrasena_corta')
                        ? ['minimo' => 8]
                        : [];
                    $mensaje = $this->mensajes->obtener($mensajeTexto, $parametros);
                } else {
                    $mensaje = $mensajeTexto;
                }
            } else {
                $mensaje = $this->mensajes->obtener('sistema.validacion_general');
            }

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
