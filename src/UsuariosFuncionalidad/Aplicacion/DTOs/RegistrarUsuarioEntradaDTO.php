<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\DTOs;

/**
 * DTO inmutable para transportar los datos crudos del formulario de registro.
 * Sigue el principio de Responsabilidad Única (S en SOLID).
 */
readonly class RegistrarUsuarioEntradaDTO
{
    public function __construct(
        public string $apodo_usuario,
        public string $correo_usuario,
        public string $contrasena_hash_usuario,
    ) {}
}