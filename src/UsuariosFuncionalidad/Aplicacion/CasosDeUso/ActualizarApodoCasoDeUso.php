<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Aplicacion\DTOs\ActualizarApodoEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class ActualizarApodoCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function execute(ActualizarApodoEntradaDTO $dto): void
    {
        $this->mAutorizador->exigirPermiso('perfil.gestionar_propio');
        $nuevoApodoLimpio = trim($dto->nuevo_apodo);

        if ($nuevoApodoLimpio === '') {
            throw new InvalidArgumentException($this->mensajes->obtener('usuario.apodo_vacio'));
        }

        // 1. Recuperar la entidad actual del usuario desde el repositorio
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($dto->id_usuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.usuario_no_encontrado'), 404);
        }

        // 2. Si el apodo ingresado es idéntico al que ya tiene, evitamos trabajo innecesario
        if ($mUsuarioEntidad->obtenerApodo() === $nuevoApodoLimpio) {
            return;
        }

        // 3. Regla de negocio: Validar que el nuevo apodo no pertenezca a otro usuario
        if ($this->mUsuarioRepositorio->encontrarPorApodo($nuevoApodoLimpio)) {
            throw ReglaNegocioExcepcion::apodoDuplicado($nuevoApodoLimpio);
        }

        try {
            // 4. Mutar el estado de la entidad (autovalida internamente sus invariantes)
            $mUsuarioEntidad->actualizarApodo($nuevoApodoLimpio);

            // 5. Persistir el cambio reutilizando el método único guardar() (ejecuta UPDATE)
            $this->mUsuarioRepositorio->guardar($mUsuarioEntidad);

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            error_log('Error crítico en ActualizarApodoCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_cambio_apodo'));
        }
    }
}
