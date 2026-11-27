<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Dominio\Entidades;

use InvalidArgumentException;
use UbicacionesFuncionalidad\Dominio\Excepciones\InvarianteUbicacionExcepcion;

class UbicacionEntidad
{
    public const DESCRIPCION_POR_DEFECTO = 'sin descripción';
    private const DESCRIPCIONES_OBSOLETAS = ['', 'descripción', 'descripsion'];

    public function __construct(
        private ?int $id_ubicacion,
        private int $id_usuario,
        private string $titulo,
        private ?string $descripcion,
        private float $latitud,
        private float $longitud,
        private string $tipo_infraestructura,
        private string $estado_infraestructura,
        private string $riesgos_directos,
        private string $riesgos_indirectos,
        private string $actividades_delictivas,
        private string $condiciones_sociales,
        private ?string $apodo_autor = null
    ) {
        $this->descripcion = self::normalizarDescripcion($this->descripcion);

        $this->autoValidarEntidad();
    }

    public static function normalizarDescripcion(?string $descripcion): string
    {
        $descripcionLimpia = $descripcion !== null ? trim($descripcion) : '';

        return in_array(mb_strtolower($descripcionLimpia), self::DESCRIPCIONES_OBSOLETAS, true)
            ? self::DESCRIPCION_POR_DEFECTO
            : $descripcionLimpia;
    }

    public function obtenerId(): ?int
    {
        return $this->id_ubicacion;
    }

    public function obtenerIdUsuario(): int
    {
        return $this->id_usuario;
    }

    public function obtenerTitulo(): string
    {
        return $this->titulo;
    }

    public function obtenerDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function obtenerLatitud(): float
    {
        return $this->latitud;
    }

    public function obtenerLongitud(): float
    {
        return $this->longitud;
    }

    public function obtenerTipoInfraestructura(): string { return $this->tipo_infraestructura; }
    public function obtenerEstadoInfraestructura(): string { return $this->estado_infraestructura; }
    public function obtenerRiesgosDirectos(): string { return $this->riesgos_directos; }
    public function obtenerRiesgosIndirectos(): string { return $this->riesgos_indirectos; }
    public function obtenerActividadesDelictivas(): string { return $this->actividades_delictivas; }
    public function obtenerCondicionesSociales(): string { return $this->condiciones_sociales; }

    public function obtenerApodoAutor(): ?string
    {
        return $this->apodo_autor;
    }

    private function autoValidarEntidad(): void
    {
        if ($this->id_usuario <= 0) {
            throw new InvarianteUbicacionExcepcion('ubicacion.usuario_propietario_invalido');
        }

        if (trim($this->titulo) === '') {
            throw new InvarianteUbicacionExcepcion('ubicacion.titulo_vacio');
        }

        if (mb_strlen(trim($this->titulo)) > 120) {
            throw new InvarianteUbicacionExcepcion('ubicacion.titulo_maximo');
        }

        if ($this->latitud < -90.0 || $this->latitud > 90.0) {
            throw new InvarianteUbicacionExcepcion('ubicacion.latitud_invalida');
        }

        if ($this->longitud < -180.0 || $this->longitud > 180.0) {
            throw new InvarianteUbicacionExcepcion('ubicacion.longitud_invalida');
        }

        foreach ([
            $this->tipo_infraestructura,
            $this->estado_infraestructura,
            $this->riesgos_directos,
            $this->riesgos_indirectos,
            $this->actividades_delictivas,
            $this->condiciones_sociales,
        ] as $clasificacion) {
            if (trim($clasificacion) === '') {
                throw new InvarianteUbicacionExcepcion('ubicacion.campos_obligatorios');
            }
        }
    }
}
