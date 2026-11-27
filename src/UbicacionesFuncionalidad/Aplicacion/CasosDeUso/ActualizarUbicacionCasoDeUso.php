<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Throwable;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use UbicacionesFuncionalidad\Aplicacion\DTOs\ActualizarUbicacionEntradaDTO;
use UbicacionesFuncionalidad\Dominio\ClasificacionesDeRiesgo\OpcionesNuevaUbicacion;
use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

class ActualizarUbicacionCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function execute(ActualizarUbicacionEntradaDTO $dto): array
    {
        $ubicacion = $this->mUbicacionRepositorio->encontrarPorId($dto->id_ubicacion);
        if ($ubicacion === null) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('ubicacion.no_encontrada'), 404);
        }

        if (!$this->mAutorizador->puede('ubicacion.editar_cualquiera')) {
            $this->mAutorizador->exigirPermiso('ubicacion.editar_propia');
            if ($ubicacion->obtenerIdUsuario() !== $dto->id_usuario_autenticado) {
                throw new ReglaNegocioExcepcion($this->mensajes->obtener('ubicacion.permiso_editar_otro'), 403);
            }
        }

        $tituloLimpio = trim($dto->titulo);
        if (mb_strlen($tituloLimpio) < 5 || !preg_match('/[a-zA-ZáéíóúÁÉÍÓÚñÑ]{3,}/', $tituloLimpio)) {
            throw new InvalidArgumentException($this->mensajes->obtener('ubicacion.titulo_invalido', ['minimo' => 5]));
        }

        foreach (OpcionesNuevaUbicacion::cases() as $opcion) {
            $valor = match ($opcion) {
                OpcionesNuevaUbicacion::TIPO_INFRAESTRUCTURA => $dto->tipo_infraestructura,
                OpcionesNuevaUbicacion::ESTADO_INFRAESTRUCTURA => $dto->estado_infraestructura,
                OpcionesNuevaUbicacion::RIESGOS_DIRECTOS => $dto->riesgos_directos,
                OpcionesNuevaUbicacion::RIESGOS_INDIRECTOS => $dto->riesgos_indirectos,
                OpcionesNuevaUbicacion::ACTIVIDADES_DELICTIVAS => $dto->actividades_delictivas,
                OpcionesNuevaUbicacion::CONDICIONES_SOCIALES => $dto->condiciones_sociales,
            };
            if (!in_array(trim($valor), $opcion->clasificacionUbicacion(), true)) {
                throw new InvalidArgumentException($this->mensajes->obtener('ubicacion.clasificacion_invalida'));
            }
        }

        $ubicacionActualizada = new UbicacionEntidad(
            id_ubicacion: $ubicacion->obtenerId(),
            id_usuario: $ubicacion->obtenerIdUsuario(),
            titulo: $tituloLimpio,
            descripcion: UbicacionEntidad::normalizarDescripcion($dto->descripcion),
            latitud: $ubicacion->obtenerLatitud(),
            longitud: $ubicacion->obtenerLongitud(),
            tipo_infraestructura: $dto->tipo_infraestructura,
            estado_infraestructura: $dto->estado_infraestructura,
            riesgos_directos: $dto->riesgos_directos,
            riesgos_indirectos: $dto->riesgos_indirectos,
            actividades_delictivas: $dto->actividades_delictivas,
            condiciones_sociales: $dto->condiciones_sociales,
            apodo_autor: $ubicacion->obtenerApodoAutor()
        );

        try {
            $ubicacionGuardada = $this->mUbicacionRepositorio->actualizarDatos($ubicacionActualizada);
        } catch (Throwable $falla) {
            error_log('Error crítico en ActualizarUbicacionCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_actualizar_ubicacion'));
        }

        if ($ubicacionGuardada === null) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('ubicacion.no_encontrada'), 404);
        }

        return [
            'id_ubicacion' => $ubicacionGuardada->obtenerId(),
            'titulo' => $ubicacionGuardada->obtenerTitulo(),
            'descripcion' => $ubicacionGuardada->obtenerDescripcion(),
            'tipo_infraestructura' => $ubicacionGuardada->obtenerTipoInfraestructura(),
            'estado_infraestructura' => $ubicacionGuardada->obtenerEstadoInfraestructura(),
            'riesgos_directos' => $ubicacionGuardada->obtenerRiesgosDirectos(),
            'riesgos_indirectos' => $ubicacionGuardada->obtenerRiesgosIndirectos(),
            'actividades_delictivas' => $ubicacionGuardada->obtenerActividadesDelictivas(),
            'condiciones_sociales' => $ubicacionGuardada->obtenerCondicionesSociales(),
        ];
    }
}
