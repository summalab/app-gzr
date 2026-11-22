<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Throwable;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;

class EliminarCuentaUsuarioCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private SesionUsuarioInterface $mSesionUsuario
    ) {}

    public function execute(int $idUsuario, string $contrasenaConfirmacion): void
    {
        if (trim($contrasenaConfirmacion) === '') {
            throw new InvalidArgumentException('Debe ingresar su contraseña actual para confirmar la baja de la cuenta.');
        }

        // 1. Verificar que el usuario activo exista
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($idUsuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion('La cuenta de usuario no existe o ya fue dada de baja.', 404);
        }

        // 2. Validar identidad exigiendo su contraseña actual
        if (!password_verify($contrasenaConfirmacion, $mUsuarioEntidad->obtenerContrasenadHash())) {
            throw new ReglaNegocioExcepcion('La contraseña ingresada es incorrecta. No se dio de baja la cuenta.', 422);
        }

        try {
            // 3. Ejecutar el Soft Delete en base de datos
            $this->mUsuarioRepositorio->eliminarCuenta($idUsuario);

            // 4. Destruir la sesión activa en el servidor para expulsarlo de inmediato
            $this->mSesionUsuario->cerrarSesion();

        } catch (Throwable $falla) {
            error_log('Error crítico en EliminarCuentaUsuarioCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo procesar la baja de la cuenta por un problema interno.');
        }
    }
}
