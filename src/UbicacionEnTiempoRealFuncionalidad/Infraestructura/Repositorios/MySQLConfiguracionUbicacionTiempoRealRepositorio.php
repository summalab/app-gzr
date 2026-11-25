<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Infraestructura\Repositorios;

use UbicacionEnTiempoRealFuncionalidad\Dominio\Entidades\ConfiguracionUbicacionTiempoReal;
use UbicacionEnTiempoRealFuncionalidad\Dominio\Repositorios\ConfiguracionUbicacionTiempoRealRepositorioInterface;
use UbicacionEnTiempoRealFuncionalidad\Infraestructura\Persistencia\ConfiguracionUbicacionTiempoRealDAO;

final class MySQLConfiguracionUbicacionTiempoRealRepositorio implements ConfiguracionUbicacionTiempoRealRepositorioInterface
{
    public function __construct(
        private ConfiguracionUbicacionTiempoRealDAO $dao
    ) {}

    public function obtener(): ConfiguracionUbicacionTiempoReal
    {
        $fila = $this->dao->obtener();
        if ($fila === null) {
            return new ConfiguracionUbicacionTiempoReal(true, 'todos');
        }

        return new ConfiguracionUbicacionTiempoReal(
            (bool)$fila['habilitada'],
            (string)$fila['rol_permitido']
        );
    }

    public function guardar(
        ConfiguracionUbicacionTiempoReal $configuracion,
        int $idAdministrador
    ): void {
        $this->dao->guardar(
            $configuracion->estaHabilitada(),
            $configuracion->obtenerRolPermitido(),
            $idAdministrador
        );
    }
}
