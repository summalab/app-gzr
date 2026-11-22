<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use DateTime;
use InvalidArgumentException;
use Throwable;
use UsuariosFuncionalidad\Aplicacion\DTOs\RestablecerContrasenaEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\TokenConfirmacionUsuarioRepositorioInterface;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;

class RestablecerContrasenaCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private TokenConfirmacionUsuarioRepositorioInterface $mTokenRepositorio
    ) {}

    public function execute(RestablecerContrasenaEntradaDTO $dto): void
    {
        $tokenLimpio = trim($dto->token);

        if ($tokenLimpio === '') {
            throw new InvalidArgumentException('El token de recuperación es obligatorio.');
        }

        if (strlen($dto->nueva_contrasena) < 8) {
            throw ReglaNegocioExcepcion::contrasenaCorta(8);
        }

        // 1. Buscar el token en la base de datos
        $datosToken = $this->mTokenRepositorio->encontrarPorToken($tokenLimpio);

        if (!$datosToken) {
            throw ReglaNegocioExcepcion::tokenInvalido();
        }

        // 2. Regla de seguridad: Verificar que sea específicamente un token de RECUPERACION
        if (($datosToken['tipo_token'] ?? '') !== 'RECUPERACION') {
            throw new ReglaNegocioExcepcion('El token proporcionado no es válido para restablecer contraseñas.', 422);
        }

        // 3. Validar que el token no haya expirado (1 hora)
        $ahora = new DateTime();
        $expiracion = new DateTime($datosToken['fecha_expiracion']);

        if ($ahora > $expiracion) {
            // Limpiamos el token expirado para no dejar basura en la tabla
            $this->mTokenRepositorio->borrarToken($tokenLimpio);
            throw ReglaNegocioExcepcion::tokenExpirado();
        }

        // 4. Buscar al usuario dueño del token
        $idUsuario = (int)$datosToken['fk_id_usuario'];
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($idUsuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion('El usuario asociado a este enlace ya no existe.', 404);
        }

        // 5. Ejecutar actualización de clave y borrado del token en una transacción atómica
        $this->mUsuarioRepositorio->beginTransaction();

        try {
            $nuevoHash = password_hash($dto->nueva_contrasena, PASSWORD_DEFAULT);
            $mUsuarioEntidad->cambiarContrasena($nuevoHash);

            // Actualizamos el usuario en MySQL
            $this->mUsuarioRepositorio->guardar($mUsuarioEntidad);

            // Eliminamos el token usado para que sea de un solo uso
            $this->mTokenRepositorio->borrarToken($tokenLimpio);

            $this->mUsuarioRepositorio->commit();

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            $this->mUsuarioRepositorio->rollBack();
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            $this->mUsuarioRepositorio->rollBack();
            error_log('Error crítico en RestablecerContrasenaCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo restablecer la contraseña por un problema interno.');
        }
    }
}



