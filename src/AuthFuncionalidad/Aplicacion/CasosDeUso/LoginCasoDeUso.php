<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Throwable;
use AuthFuncionalidad\Aplicacion\DTOs\LoginEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;

class LoginCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private SesionUsuarioInterface $mSesionUsuario
    ) {}

    /**
     * Valida credenciales, verifica que la cuenta esté activa e inicia la sesión segura.
     *
     * @param LoginEntradaDTO $dto
     * @return array{id_usuario: int, apodo_usuario: string, correo_usuario: string}
     */
    public function execute(LoginEntradaDTO $dto): array
    {
        $correoLimpio = trim($dto->correo_usuario);

        if ($correoLimpio === '' || !filter_var($correoLimpio, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El formato del correo electrónico no es válido.');
        }

        if ($dto->contrasena === '') {
            throw new InvalidArgumentException('Debe ingresar su contraseña.');
        }

        try {
            // 1. Buscar al usuario activo por correo electrónico
            $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorEmail($correoLimpio);

            // Seguridad: Usamos un mensaje genérico para no revelar si el correo existe o no
            if (!$mUsuarioEntidad || !password_verify($dto->contrasena, $mUsuarioEntidad->obtenerContrasenadHash())) {
                throw new ReglaNegocioExcepcion('Credenciales incorrectas. Verifique su correo y contraseña.', 401);
            }

            // 2. Regla de negocio: Impedir el acceso si no ha confirmado su correo
            if (!$mUsuarioEntidad->obtenerVerificacionUsuario()) {
                throw new ReglaNegocioExcepcion('Su cuenta aún no ha sido verificada. Revise su correo electrónico.', 403);
            }

            // 3. Iniciar la sesión segura en el servidor (regenera ID de sesión)
            $this->mSesionUsuario->iniciarSesion($mUsuarioEntidad->obtenerId());

            // 4. Retornar datos públicos seguros para que el Frontend actualice la interfaz
            return [
                'id_usuario'     => $mUsuarioEntidad->obtenerId(),
                'apodo_usuario'  => $mUsuarioEntidad->obtenerApodo(),
                'correo_usuario' => $mUsuarioEntidad->obtenerCorreo(),
            ];

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            error_log('Error crítico en LoginCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo iniciar sesión por un problema interno.');
        }
    }
}
