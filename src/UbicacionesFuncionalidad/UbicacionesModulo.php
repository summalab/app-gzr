<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad;

use Core\Contenedor;
use Core\Router;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;
use UbicacionesFuncionalidad\Http\UbicacionControlador;
use UbicacionesFuncionalidad\Infraestructura\Repositorios\MySQLUbicacionRepositorio;

class UbicacionesModulo
{
    public static function registrarDependencias(Contenedor $contenedor): void
    {
        $contenedor->bind(
            UbicacionRepositorioInterface::class,
            MySQLUbicacionRepositorio::class
        );
    }

    public static function registrarRutas(Router $enrutador): void
    {
        // Ruta pública (Landing Page)
        $enrutador->addRoute('GET', '/ubicaciones-publicas', UbicacionControlador::class, 'listarPublicas');

        // Rutas privadas (protegidas por sesión en el controlador)
        $enrutador->addRoute('GET', '/mis-ubicaciones', UbicacionControlador::class, 'listarPropias');
        $enrutador->addRoute('POST', '/crear-ubicacion', UbicacionControlador::class, 'crear');
        $enrutador->addRoute('POST', '/eliminar-ubicacion', UbicacionControlador::class, 'eliminar');
    }
}
