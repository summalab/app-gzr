<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Aplicacion\CasosDeUso;

use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Throwable;

class LogoutCasoDeUso
{
    public function __construct(
        private SesionUsuarioInterface $mSesionUsuario
    ) {}

    /**
     * Cierra de forma segura la sesión activa del usuario en el servidor.
     */
    public function execute(): void
    {
        try {
            $this->mSesionUsuario->cerrarSesion();
        } catch (Throwable $falla) {
            error_log('Error crítico en LogoutCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo cerrar la sesión correctamente por un problema interno.');
        }
    }
}
