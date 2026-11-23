<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Throwable;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Shared\Dominio\Servicios\GestorTransaccionesInterface;

class EliminarCuentaUsuarioCasoDeUso
{
    public function __construct(
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private SesionUsuarioInterface $mSesionUsuario,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes,
        private GestorTransaccionesInterface $transacciones
    ) {}

    public function execute(int $idUsuario, string $contrasenaConfirmacion): void
    {
        $this->mAutorizador->exigirPermiso('perfil.gestionar_propio');
        if (trim($contrasenaConfirmacion) === '') {
            throw new InvalidArgumentException($this->mensajes->obtener('usuario.confirmacion_cuenta_obligatoria'));
        }

        // 1. Verificar que el usuario activo exista
        $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($idUsuario);

        if (!$mUsuarioEntidad) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.cuenta_no_encontrada'), 404);
        }

        // 2. Validar identidad exigiendo su contraseña actual
        if (!password_verify($contrasenaConfirmacion, $mUsuarioEntidad->obtenerContrasenadHash())) {
            throw new ReglaNegocioExcepcion($this->mensajes->obtener('usuario.contrasena_confirmacion_mala'), 422);
        }

        // 3. Ejecutar el Soft Delete en base de datos
        $this->transacciones->ejecutar(function () use ($idUsuario) {
            $this->mUsuarioRepositorio->eliminarCuenta($idUsuario);
        });

        // 4. Destruir la sesión activa en el servidor para expulsarlo de inmediato
        $this->mSesionUsuario->cerrarSesion();
    }
}
