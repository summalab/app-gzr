<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Entidades;

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
            throw new \InvalidArgumentException("El id_usuario no puede estar vacío.");
        }

        if (empty($this->token_verificacion)) {
            throw new \InvalidArgumentException("El token_verificacion no puede estar vacío.");
        }

        if (empty($this->fecha_expiracion)) {
            throw new \InvalidArgumentException("La fecha_expiracion no puede estar vacia.");
            }
            
        if (!in_array($this->tipo_token, ['VERIFICACION', 'RECUPERACION'], true)) {
            throw new \InvalidArgumentException("El tipo_token '{$this->tipo_token}' no es válido.");
        }
    }
}
