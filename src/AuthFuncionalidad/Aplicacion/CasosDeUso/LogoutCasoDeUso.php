<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Aplicacion\CasosDeUso;

use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Throwable;

class LogoutCasoDeUso
{
    public function __construct(
        private SesionUsuarioInterface $mSesionUsuario,
        private CatalogoMensajesInterface $mensajes
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
            throw new \Exception($this->mensajes->obtener('sistema.operacion_cerrar_sesion'));
        }
    }
}
