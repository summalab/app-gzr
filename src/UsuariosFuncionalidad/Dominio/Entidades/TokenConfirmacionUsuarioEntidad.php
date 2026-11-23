<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Entidades;

use UsuariosFuncionalidad\Dominio\Excepciones\InvarianteUsuarioExcepcion;

class TokenConfirmacionUsuarioEntidad
{

    public function __construct(
        private ?int $id_token,
        private int $id_usuario,
        private string $token_verificacion,
        private string $fecha_expiracion,
        private string $tipo_token = 'VERIFICACION',
    ) {
        $this->autoValidarEntidad();
    }

    public function obtenerId(): ?int
    {
        return $this->id_token;
    }

    public function obtenerIdUsuario(): int
    {
        return $this->id_usuario;
    }

    public function obtenerTokenConfirmacion(): string
    {
        return $this->token_verificacion;
    }

    public function obtenerTipoToken(): string
    {
        return $this->tipo_token;
    }

    public function obtenerFechaExpiracion(): string
    {
        return $this->fecha_expiracion;
    }

    /**
     * Validación interna de dominio (Principio de encapsulación).
     * Una entidad nunca debe permitir existir en un estado inválido.
     */
    private function autoValidarEntidad(): void
    {
        
        if (empty($this->id_usuario)) {
            throw new InvarianteUsuarioExcepcion('usuario.id_usuario_vacio');
        }

        if (empty($this->token_verificacion)) {
            throw new InvarianteUsuarioExcepcion('usuario.token_vacio');
        }

        if (empty($this->fecha_expiracion)) {
            throw new InvarianteUsuarioExcepcion('usuario.expiracion_vacia');
        }
            
        if (!in_array($this->tipo_token, ['VERIFICACION', 'RECUPERACION'], true)) {
            throw new InvarianteUsuarioExcepcion('usuario.tipo_token_invalido');
        }
    }
}
