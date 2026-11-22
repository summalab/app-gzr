<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\DTOs;

readonly class CambiarContrasenaEntradaDTO
{
    public function __construct(
        public int $id_usuario,
        public string $contrasena_actual,
        public string $contrasena_nueva
    ) {}
}
