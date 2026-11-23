<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Entidades;

class UsuarioEntidad
{
    public function __construct(
        private ?int $id_usuario,
        private string $apodo_usuario,
        private string $correo_usuario,
        private string $contrasena_hash_usuario,
        private bool $esta_verificado_usuario,
        private string $rol_usuario = 'usuario'
    ) {
        $this->autoValidarEntidad();
    }

    public function obtenerId(): ?int
    {
        return $this->id_usuario;
    }

    public function obtenerApodo(): string
    {
        return $this->apodo_usuario;
    }

    public function obtenerCorreo(): string
    {
        return $this->correo_usuario;
    }

    public function obtenerRol(): string
    {
        return $this->rol_usuario;
    }

    public function obtenerContrasenadHash(): string
    {
        return $this->contrasena_hash_usuario;
    }

    public function obtenerVerificacionUsuario(): bool
    {
        return $this->esta_verificado_usuario;
    }

    public function cambiarContrasena(string $nuevo_hash_contrasena): void
    {
        $this->contrasena_hash_usuario = $nuevo_hash_contrasena;
        $this->autoValidarEntidad();
    }

    public function actualizarCorreo(string $nuevo_correo): void
    {
        $this->correo_usuario = trim($nuevo_correo);
        $this->esta_verificado_usuario = false;
        $this->autoValidarEntidad();
    }

    public function actualizarApodo(string $nuevo_apodo): void
    {
        $this->apodo_usuario = trim($nuevo_apodo);
        $this->autoValidarEntidad();
    }

    private function autoValidarEntidad(): void
    {
        if (empty($this->apodo_usuario)) {
            throw new \InvalidArgumentException("El apodo no puede estar vacío.");
        }

        if (empty($this->correo_usuario) || !filter_var($this->correo_usuario, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("El formato del correo electrónico no es válido.");
        }

        if (empty($this->contrasena_hash_usuario)) {
            throw new \InvalidArgumentException("El hash de la contraseña no puede estar vacío.");
        }
    }
}
