<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Dominio\Repositorios;

use UbicacionEnTiempoRealFuncionalidad\Dominio\Entidades\ConfiguracionUbicacionTiempoReal;

interface ConfiguracionUbicacionTiempoRealRepositorioInterface
{
    public function obtener(): ConfiguracionUbicacionTiempoReal;

    public function guardar(
        ConfiguracionUbicacionTiempoReal $configuracion,
        int $idAdministrador
    ): void;
}
