<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Aplicacion\DTOs;

readonly class ActualizarCorreoEntradaDTO
{
    public function __construct(
        public int $id_usuario,
        public string $nuevo_correo
    ) {}
}
