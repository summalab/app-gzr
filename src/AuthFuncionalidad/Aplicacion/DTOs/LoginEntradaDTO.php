<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Aplicacion\DTOs;

readonly class LoginEntradaDTO
{
    public function __construct(
        public string $correo_usuario,
        public string $contrasena
    ) {}
}
