<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use Throwable;
use UbicacionesFuncionalidad\Aplicacion\DTOs\EliminarUbicacionEntradaDTO;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use Shared\Dominio\Servicios\AutorizadorInterface;

class EliminarUbicacionCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio,
        private AutorizadorInterface $mAutorizador
    ) {}

    public function execute(EliminarUbicacionEntradaDTO $dto): void
    {



        // Si el usuario tiene 'ubicacion.eliminar_cualquiera' (moderador/admin), puede borrar cualquier pin;
        // si no, solo puede borrar si es dueño y tiene 'ubicacion.eliminar_propia'.
        if (!$this->autorizador->puede('ubicacion.eliminar_cualquiera')) {
            $this->autorizador->exigirPermiso('ubicacion.eliminar_propia');
            if ($ubicacion->obtenerIdUsuario() !== $dto->id_usuario) {
                throw new ReglaNegocioExcepcion($this->mensajes->obtener('ubicacion.no_encontrada'), 403);
            }
        }

        $ubicacion = $this->mUbicacionRepositorio->encontrarPorId($dto->id_ubicacion);

        if (!$ubicacion) {
            throw new ReglaNegocioExcepcion('La ubicación solicitada no existe o ya fue eliminada.', 404);
        }

        // Regla de seguridad: Solo el dueño puede eliminar su propio marcador
        if ($ubicacion->obtenerIdUsuario() !== $dto->id_usuario_autenticado) {
            throw new ReglaNegocioExcepcion('No tienes permiso para eliminar una ubicación de otro usuario.', 403);
        }

        try {
            $this->mUbicacionRepositorio->eliminarLogicamente($dto->id_ubicacion);
        } catch (Throwable $falla) {
            error_log('Error crítico en EliminarUbicacionCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo eliminar la ubicación por un problema interno.');
        }
    }
}
