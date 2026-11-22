<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\DTOs;

readonly class EliminarUbicacionEntradaDTO
{
    public function __construct(
        public int $id_ubicacion,
        public int $id_usuario_autenticado
    ) {}
}
