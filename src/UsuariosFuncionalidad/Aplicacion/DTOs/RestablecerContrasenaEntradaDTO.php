<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\DTOs;

readonly class RestablecerContrasenaEntradaDTO
{
    public function __construct(
        public string $token,
        public string $nueva_contrasena
    ) {}
}