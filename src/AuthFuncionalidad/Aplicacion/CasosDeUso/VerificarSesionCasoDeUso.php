<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Aplicacion\CasosDeUso;

use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Throwable;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class VerificarSesionCasoDeUso
{
    public function __construct(
        private SesionUsuarioInterface $mSesionUsuario,
        private UsuarioRepositorioInterface $mUsuarioRepositorio,
        private CatalogoMensajesInterface $mensajes
    ) {}

    /**
     * Comprueba si existe una sesión activa y valida que el usuario siga activo en MySQL.
     *
     * @return array{autenticado: bool, usuario: ?array{id_usuario: int, apodo_usuario: string, correo_usuario: string, rol_usuario: string}}
     */
    public function execute(): array
    {
        try {
            $idUsuario = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();

            if ($idUsuario === null) {
                return [
                    'autenticado' => false,
                    'usuario'     => null
                ];
            }

            // Validación de seguridad: verificar que la cuenta no haya sido dada de baja (Soft Delete)
            $mUsuarioEntidad = $this->mUsuarioRepositorio->encontrarPorId($idUsuario);

            if (!$mUsuarioEntidad) {
                // Si el usuario fue eliminado mientras tenía la sesión abierta, cerramos la cookie
                $this->mSesionUsuario->cerrarSesion();

                return [
                    'autenticado' => false,
                    'usuario'     => null
                ];
            }

            return [
                'autenticado' => true,
                'usuario'     => [
                    'id_usuario'     => $mUsuarioEntidad->obtenerId(),
                    'apodo_usuario'  => $mUsuarioEntidad->obtenerApodo(),
                    'correo_usuario' => $mUsuarioEntidad->obtenerCorreo(),
                    'rol_usuario'    => $mUsuarioEntidad->obtenerRol(),
                ]
            ];

        } catch (Throwable $falla) {
            error_log('Error crítico en VerificarSesionCasoDeUso: ' . $falla->getMessage());
            throw new \Exception($this->mensajes->obtener('sistema.operacion_verificar_sesion'));
        }
    }
}
