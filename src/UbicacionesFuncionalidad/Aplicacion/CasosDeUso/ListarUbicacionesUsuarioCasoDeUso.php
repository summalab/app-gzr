<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use Throwable;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Shared\Dominio\Servicios\AutorizadorInterface;
use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;

class ListarUbicacionesUsuarioCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio,
        private CatalogoMensajesInterface $mensajes,
        private AutorizadorInterface $mAutorizador
    ) {}

    public function execute(int $idUsuario): array
    {
        $this->mAutorizador->exigirPermiso('perfil.gestionar_propio');
        try {
            $entidades = $this->mUbicacionRepositorio->listarActivasPorUsuario($idUsuario);

            return array_map(
                fn(UbicacionEntidad $u): array => [
                    'id_ubicacion' => $u->obtenerId(),
                    'titulo'       => $u->obtenerTitulo(),
                    'descripcion'  => $u->obtenerDescripcion(),
                    'latitud'      => $u->obtenerLatitud(),
                    'longitud'     => $u->obtenerLongitud(),
                    'tipo_infraestructura' => $u->obtenerTipoInfraestructura(),
                    'estado_infraestructura' => $u->obtenerEstadoInfraestructura(),
                    'riesgos_directos' => $u->obtenerRiesgosDirectos(),
                    'riesgos_indirectos' => $u->obtenerRiesgosIndirectos(),
                    'actividades_delictivas' => $u->obtenerActividadesDelictivas(),
                    'condiciones_sociales' => $u->obtenerCondicionesSociales(),
                ],
                $entidades
            );
        } catch (Throwable $falla) {
            error_log('Error crítico en ListarUbicacionesUsuarioCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_listar_ubicaciones'));
        }
    }
}
