<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\DTOs;

readonly class CrearUbicacionEntradaDTO
{
    public function __construct(
        public int $id_usuario,
        public string $titulo,
        public ?string $descripcion,
        public float $latitud,
        public float $longitud
    ) {}
}
