<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Repositorios;

use UsuariosFuncionalidad\Dominio\Entidades\UsuarioEntidad;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Infraestructura\Persistencia\UsuarioDAO;

class MySQLUsuarioRepositorio implements UsuarioRepositorioInterface
{
    public function __construct(
        private UsuarioDAO $mUsuarioDao
    ) {}

    public function guardar(UsuarioEntidad $mUsuarioEntidad): UsuarioEntidad
    {
        if (!$mUsuarioEntidad->obtenerId()) {
            $data = [
                'apodo_usuario'           => $mUsuarioEntidad->obtenerApodo(),
                'correo_usuario'          => $mUsuarioEntidad->obtenerCorreo(),
                'contrasena_hash_usuario' => $mUsuarioEntidad->obtenerContrasenadHash(),
                'esta_verificado_usuario' => $mUsuarioEntidad->obtenerVerificacionUsuario(),
                'rol'                     => $mUsuarioEntidad->obtenerRol()
            ];

            $id_usuario_creado = $this->mUsuarioDao->insertar($data);

            return new UsuarioEntidad(
                id_usuario: $id_usuario_creado,
                apodo_usuario: $mUsuarioEntidad->obtenerApodo(),
                correo_usuario: $mUsuarioEntidad->obtenerCorreo(),
                contrasena_hash_usuario: $mUsuarioEntidad->obtenerContrasenadHash(),
                esta_verificado_usuario: $mUsuarioEntidad->obtenerVerificacionUsuario(),
                rol_usuario: $mUsuarioEntidad->obtenerRol()
            );
        } else {
            $data = [
                'pk_id_usuario'           => $mUsuarioEntidad->obtenerId(),
                'apodo_usuario'           => $mUsuarioEntidad->obtenerApodo(),
                'correo_usuario'          => $mUsuarioEntidad->obtenerCorreo(),
                'contrasena_hash_usuario' => $mUsuarioEntidad->obtenerContrasenadHash(),
                'esta_verificado_usuario' => $mUsuarioEntidad->obtenerVerificacionUsuario(),
                'rol'                     => $mUsuarioEntidad->obtenerRol()
            ];

            $this->mUsuarioDao->actualizar($data);

            return new UsuarioEntidad(
                id_usuario: $mUsuarioEntidad->obtenerId(),
                apodo_usuario: $mUsuarioEntidad->obtenerApodo(),
                correo_usuario: $mUsuarioEntidad->obtenerCorreo(),
                contrasena_hash_usuario: $mUsuarioEntidad->obtenerContrasenadHash(),
                esta_verificado_usuario: $mUsuarioEntidad->obtenerVerificacionUsuario(),
                rol_usuario: $mUsuarioEntidad->obtenerRol()
            );
        }
    }

    public function encontrarPorId(int $id_usuario): ?UsuarioEntidad
    {
        $row = $this->mUsuarioDao->encontrarPorId($id_usuario);

        if (!$row) {
            return null;
        }

        return new UsuarioEntidad(
            id_usuario: (int)$row['pk_id_usuario'],
            apodo_usuario: (string)$row['apodo_usuario'],
            correo_usuario: (string)$row['correo_usuario'],
            contrasena_hash_usuario: (string)$row['contrasena_hash_usuario'],
            esta_verificado_usuario: (bool)$row['esta_verificado_usuario'],
            rol_usuario: (string)($row['rol'] ?? 'usuario')
        );
    }

    public function encontrarPorApodo(string $apodo_usuario): bool
    {
        $row = $this->mUsuarioDao->encontrarPorApodo($apodo_usuario);
        return $row !== null;
    }


    public function encontrarPorEmail(string $correo_usuario): ?UsuarioEntidad
    {
        $row = $this->mUsuarioDao->encontrarPorEmail($correo_usuario);

        if (!$row) {
            return null;
        }

        return new UsuarioEntidad(
            id_usuario: (int)$row['pk_id_usuario'],
            apodo_usuario: (string)$row['apodo_usuario'],
            correo_usuario: (string)$row['correo_usuario'],
            contrasena_hash_usuario: (string)$row['contrasena_hash_usuario'],
            esta_verificado_usuario: (bool)($row['esta_verificado_usuario'] ?? false),
            rol_usuario: (string)($row['rol'] ?? 'usuario')
        );
    }

    public function marcarComoVerificado(int $id_usuario): void
    {
        $this->mUsuarioDao->actualizarVerificacionStatus($id_usuario, true);
    }


    public function eliminarCuenta(int $id_usuario): void
    {
        $this->mUsuarioDao->eliminarLogicamente($id_usuario);
    }


    public function beginTransaction(): void
    {
        $this->mUsuarioDao->beginTransaction();
    }

    public function commit(): void
    {
        $this->mUsuarioDao->commit();
    }

    public function rollBack(): void
    {
        $this->mUsuarioDao->rollBack();
    }
}
