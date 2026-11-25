<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Aplicacion\CasosDeUso;

use UbicacionEnTiempoRealFuncionalidad\Dominio\Repositorios\ConfiguracionUbicacionTiempoRealRepositorioInterface;

final class ObtenerConfiguracionUbicacionTiempoReal
{
    public function __construct(
        private ConfiguracionUbicacionTiempoRealRepositorioInterface $repositorio
    ) {}

    public function execute(): array
    {
        $configuracion = $this->repositorio->obtener();

        return [
            'habilitada' => $configuracion->estaHabilitada(),
            'rolPermitido' => $configuracion->obtenerRolPermitido(),
        ];
    }
}
