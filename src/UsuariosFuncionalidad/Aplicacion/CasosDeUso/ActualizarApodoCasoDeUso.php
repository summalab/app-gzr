<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Aplicacion\DTOs\ActualizarApodoEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;

class ActualizarApodoCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio
    ) {}

    public function execute(ActualizarApodoEntradaDTO $dto): void
    {
        $nuevoApodoLimpio = trim($dto->nuevo_apodo);

        if ($nuevoApodoLimpio === '') {
            throw new InvalidArgumentException('El nuevo apodo no puede estar vacío.');
        }

        // 1. Recuperar la entidad actual del usuario desde el repositorio
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($dto->id_usuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion('El usuario solicitado no existe en el sistema.', 404);
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
            throw new \Exception('No se pudo actualizar el apodo por un problema interno.');
        }
    }
}
