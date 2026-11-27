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
        public float $longitud,
        public string $tipo_infraestructura,
        public string $estado_infraestructura,
        public string $riesgos_directos,
        public string $riesgos_indirectos,
        public string $actividades_delictivas,
        public string $condiciones_sociales
    ) {}
}
