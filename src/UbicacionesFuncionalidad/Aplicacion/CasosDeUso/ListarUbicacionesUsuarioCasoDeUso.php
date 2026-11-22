<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use Throwable;
use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;

class ListarUbicacionesUsuarioCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio
    ) {}

    public function execute(int $idUsuario): array
    {
        try {
            $entidades = $this->mUbicacionRepositorio->listarActivasPorUsuario($idUsuario);

            return array_map(
                fn(UbicacionEntidad $u): array => [
                    'id_ubicacion' => $u->obtenerId(),
                    'titulo'       => $u->obtenerTitulo(),
                    'descripcion'  => $u->obtenerDescripcion(),
                    'latitud'      => $u->obtenerLatitud(),
                    'longitud'     => $u->obtenerLongitud(),
                ],
                $entidades
            );
        } catch (Throwable $falla) {
            error_log('Error crítico en ListarUbicacionesUsuarioCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudieron cargar tus ubicaciones por un problema interno.');
        }
    }
}
