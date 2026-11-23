<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use Throwable;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;

class ListarUbicacionesPublicasCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function execute(): array
    {
        try {
            $entidades = $this->mUbicacionRepositorio->listarTodasActivas();

            return array_map(
                fn(UbicacionEntidad $u): array => [
                    'id_ubicacion'  => $u->obtenerId(),
                    'titulo'        => $u->obtenerTitulo(),
                    'descripcion'   => $u->obtenerDescripcion(),
                    'latitud'       => $u->obtenerLatitud(),
                    'longitud'      => $u->obtenerLongitud(),
                    'apodo_usuario' => $u->obtenerApodoAutor(),
                ],
                $entidades
            );
        } catch (Throwable $falla) {
            error_log('Error crítico en ListarUbicacionesPublicasCasoDeUso: ' . $falla->getMessage());
            throw new \Exception(
                $this->mensajes->obtener('sistema.operacion_listar_ubicaciones')
            );
        }
    }
}

