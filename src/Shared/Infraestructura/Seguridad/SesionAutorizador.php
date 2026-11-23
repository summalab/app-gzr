<?php

declare(strict_types=1);

namespace Shared\Infraestructura\Seguridad;

use Shared\Dominio\Seguridad\RolUsuario;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

//1. Implementación que consulta el usuario autenticado en SesionUsuarioInterface y verifica sus permisos.
class SesionAutorizador implements AutorizadorInterface
{
    public function __construct(
        private SesionUsuarioInterface $sesionUsuario,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function puede(string $permiso): bool
    {
        $rolTexto = $this->sesionUsuario->obtenerRolUsuarioAutenticado();
        if ($rolTexto === null) {
            return false;
        }

        $rol = RolUsuario::tryFrom($rolTexto) ?? RolUsuario::USUARIO;
        return $rol->tienePermiso($permiso);
    }

    public function exigirPermiso(string $permiso): void
    {
        if ($this->sesionUsuario->obtenerIdUsuarioAutenticado() === null) {
            throw new ReglaNegocioExcepcion(
                $this->mensajes->obtener('sistema.no_autorizado'),
                401
            );
        }

        if (!$this->puede($permiso)) {
            throw new ReglaNegocioExcepcion(
                $this->mensajes->obtener('sistema.permiso_denegado'),
                403
            );
        }
    }
}

