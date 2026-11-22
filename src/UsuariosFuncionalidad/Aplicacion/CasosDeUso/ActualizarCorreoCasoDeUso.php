<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Aplicacion\DTOs\ActualizarCorreoEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;

class ActualizarCorreoCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private GenerarTokenConfirmacionUsuarioCasoDeUso $mGenerarTokenConfirmacionUsuarioCasoDeUso,
        private EnviadorCorreoInterface $mEnviadorCorreoInterfaceImp
    ) {}

    public function execute(ActualizarCorreoEntradaDTO $dto): void
    {
        $nuevoCorreoLimpio = trim($dto->nuevo_correo);

        if ($nuevoCorreoLimpio === '' || !filter_var($nuevoCorreoLimpio, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El formato del nuevo correo electrónico no es válido.');
        }

        // 1. Recuperar al usuario actual desde la base de datos
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($dto->id_usuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion('El usuario solicitado no existe en el sistema.', 404);
        }

        // 2. Si el correo nuevo es idéntico al actual, no hacemos nada
        if (strtolower($mUsuarioEntidad->obtenerCorreo()) === strtolower($nuevoCorreoLimpio)) {
            return;
        }

        // 3. Validar que el nuevo correo no esté ocupado por otro usuario
        if ($this->mUsuarioRepositorio->encontrarPorEmail($nuevoCorreoLimpio)) {
            throw ReglaNegocioExcepcion::correoDuplicado($nuevoCorreoLimpio);
        }

        // 4. Iniciar transacción ACID para actualizar usuario y generar nuevo token
        $this->mUsuarioRepositorio->beginTransaction();

        try {
            // Mutamos la entidad (esto cambia el correo y pone esta_verificado_usuario = false)
            $mUsuarioEntidad->actualizarCorreo($nuevoCorreoLimpio);

            // Guardamos el cambio en MySQL (ejecuta UPDATE porque ya tiene ID)
            $this->mUsuarioRepositorio->guardar($mUsuarioEntidad);

            // Generamos el nuevo token de verificación
            $tokenVerificacion = $this->mGenerarTokenConfirmacionUsuarioCasoDeUso->execute(
                $mUsuarioEntidad->obtenerId(),
                $mUsuarioEntidad->obtenerVerificacionUsuario()
            );

            // Confirmamos ambos cambios en disco
            $this->mUsuarioRepositorio->commit();
            
            // 5. Armar URL dinámica desde .env y enviar correo fuera de la transacción
            $urlBase = rtrim(\Core\EnvLoader::obtener('APP_URL', 'http://app-gzr.test'), '/');
            $enlaceVerificacion = "{$urlBase}/?token={$tokenVerificacion}";

            $this->mEnviadorCorreoInterfaceImp->enviarCorreoVerificacion(
                $mUsuarioEntidad->obtenerCorreo(),
                $mUsuarioEntidad->obtenerApodo(),
                $enlaceVerificacion
            );

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            $this->mUsuarioRepositorio->rollBack();
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            $this->mUsuarioRepositorio->rollBack();
            error_log('Error crítico en ActualizarCorreoCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo actualizar el correo electrónico por un problema interno.');
        }
    }
}
