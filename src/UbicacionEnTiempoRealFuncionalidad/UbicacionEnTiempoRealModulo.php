<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad;

use Core\Contenedor;
use Core\Router;
use UbicacionEnTiempoRealFuncionalidad\Dominio\Repositorios\ConfiguracionUbicacionTiempoRealRepositorioInterface;
use UbicacionEnTiempoRealFuncionalidad\Http\UbicacionEnTiempoRealControlador;
use UbicacionEnTiempoRealFuncionalidad\Infraestructura\Repositorios\MySQLConfiguracionUbicacionTiempoRealRepositorio;

final class UbicacionEnTiempoRealModulo
{
    public static function registrarDependencias(Contenedor $contenedor): void
    {
        $contenedor->bind(
            ConfiguracionUbicacionTiempoRealRepositorioInterface::class,
            MySQLConfiguracionUbicacionTiempoRealRepositorio::class
        );
    }

    public static function registrarRutas(Router $enrutador): void
    {
        $enrutador->addRoute(
            'GET',
            '/ubicacion-tiempo-real/configuracion',
            UbicacionEnTiempoRealControlador::class,
            'obtenerConfiguracion'
        );
        $enrutador->addRoute(
            'POST',
            '/ubicacion-tiempo-real/configuracion',
            UbicacionEnTiempoRealControlador::class,
            'guardarConfiguracion'
        );
    }
}
