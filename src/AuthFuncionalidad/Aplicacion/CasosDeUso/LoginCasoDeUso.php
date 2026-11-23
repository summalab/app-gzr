<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Throwable;
use AuthFuncionalidad\Aplicacion\DTOs\LoginEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class LoginCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private SesionUsuarioInterface $mSesionUsuario,
        private CatalogoMensajesInterface $mensajes
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
            throw new InvalidArgumentException($this->mensajes->obtener('usuario.correo_invalido'));
        }

        if ($dto->contrasena === '') {
            throw new InvalidArgumentException($this->mensajes->obtener('usuario.contrasena_obligatoria_login'));
        }

        try {
            // 1. Buscar al usuario activo por correo electrónico
            $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorEmail($correoLimpio);

            // Seguridad: Usamos un mensaje genérico para no revelar si el correo existe o no
            if (!$mUsuarioEntidad || !password_verify($dto->contrasena, $mUsuarioEntidad->obtenerContrasenadHash())) {
                throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.credenciales_malas'), 401);
            }

            // 2. Regla de negocio: Impedir el acceso si no ha confirmado su correo
            if (!$mUsuarioEntidad->obtenerVerificacionUsuario()) {
                throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.cuenta_no_verificada'), 403);
            }

            // 3. Iniciar la sesión segura en el servidor (regenera ID de sesión)
            $idUsuario = $mUsuarioEntidad->obtenerId();
            if ($idUsuario === null) {
                throw new \LogicException('El usuario autenticado no tiene un identificador persistido.');
            }

            $this->mSesionUsuario->iniciarSesion(
                $idUsuario,
                $mUsuarioEntidad->obtenerApodo(),
                $mUsuarioEntidad->obtenerRol()
            );

            // 4. Retornar datos públicos seguros para que el Frontend actualice la interfaz
            return [
                'id_usuario'     => $mUsuarioEntidad->obtenerId(),
                'apodo_usuario'  => $mUsuarioEntidad->obtenerApodo(),
                'correo_usuario' => $mUsuarioEntidad->obtenerCorreo(),
                'rol_usuario'    => $mUsuarioEntidad->obtenerRol(),
            ];

        } catch (ReglaNegocioExcepcion | InvalidArgumentException $reglaExcepcion) {
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            error_log('Error crítico en LoginCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_login'));
        }
    }
}
