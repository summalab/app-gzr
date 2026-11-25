<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Aplicacion\CasosDeUso;

use UbicacionEnTiempoRealFuncionalidad\Dominio\Entidades\ConfiguracionUbicacionTiempoReal;
use UbicacionEnTiempoRealFuncionalidad\Dominio\Repositorios\ConfiguracionUbicacionTiempoRealRepositorioInterface;

final class ActualizarConfiguracionUbicacionTiempoReal
{
    public function __construct(
        private ConfiguracionUbicacionTiempoRealRepositorioInterface $repositorio
    ) {}

    public function execute(
        bool $habilitada,
        string $rolPermitido,
        int $idAdministrador
    ): array {
        $configuracion = new ConfiguracionUbicacionTiempoReal($habilitada, $rolPermitido);
        $this->repositorio->guardar($configuracion, $idAdministrador);

        return [
            'habilitada' => $configuracion->estaHabilitada(),
            'rolPermitido' => $configuracion->obtenerRolPermitido(),
        ];
    }
}
