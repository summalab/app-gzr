<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use Throwable;
use UbicacionesFuncionalidad\Aplicacion\DTOs\EliminarUbicacionEntradaDTO;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class EliminarUbicacionCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function execute(EliminarUbicacionEntradaDTO $dto): void
    {
        $ubicacion = $this->mUbicacionRepositorio->encontrarPorId($dto->id_ubicacion);

        if (!$ubicacion) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('ubicacion.no_encontrada'), 404);
        }

        if (!$this->mAutorizador->puede('ubicacion.eliminar_cualquiera')) {
            $this->mAutorizador->exigirPermiso('ubicacion.eliminar_propia');
            if ($ubicacion->obtenerIdUsuario() !== $dto->id_usuario_autenticado) {
                throw new ReglaNegocioExcepcion($this->mensajes->obtener('ubicacion.permiso_eliminar_otro'), 403);
            }
        }

        try {
            $this->mUbicacionRepositorio->eliminarLogicamente($dto->id_ubicacion);
        } catch (Throwable $falla) {
            error_log('Error crítico en EliminarUbicacionCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_eliminar_ubicacion'));
        }
    }
}
