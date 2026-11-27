<?php

declare(strict_types=1);

namespace Shared\Dominio\Seguridad;

/**
 * Define los roles válidos del sistema y sus permisos asociados.
 */
enum RolUsuario: string
{
    case USUARIO       = 'usuario';
    case MODERADOR     = 'moderador';
    case ADMINISTRADOR = 'administrador';

    /**
     * Devuelve la lista de permisos granulares concedidos a cada rol.
     */
    public function permisos(): array
    {
        return match ($this) {
            self::USUARIO => [
                'ubicacion.crear',
                'ubicacion.eliminar_propia',
                'ubicacion.editar_propia',
                'ubicacion.reportar',
                'perfil.gestionar_propio',
            ],
            self::MODERADOR => [
                'ubicacion.crear',
                'ubicacion.eliminar_propia',
                'ubicacion.eliminar_cualquiera',
                'ubicacion.editar_propia',
                'ubicacion.editar_cualquiera',
                'ubicacion.aprobar',
                'ubicacion.reportar',
                'perfil.gestionar_propio',
            ],
            self::ADMINISTRADOR => [
                '*', // Acceso total a todas las capacidades del sistema
            ],
        };
    }

    public function tienePermiso(string $permiso): bool
    {
        $permisos = $this->permisos();
        return in_array('*', $permisos, true) || in_array($permiso, $permisos, true);
    }
}



