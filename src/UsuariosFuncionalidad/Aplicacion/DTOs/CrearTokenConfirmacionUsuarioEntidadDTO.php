<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\DTOs;

/**
 * DTO inmutable para transportar los datos crudos del formulario de registro.
 * Sigue el principio de Responsabilidad Única (S en SOLID).
 */
readonly class CrearTokenConfirmacionUsuarioEntidadDTO
{
    public function __construct(
        public string $id_usuario,
        public string $token_verificacion,
        public string $fecha_expiracion,
    ) {}
}