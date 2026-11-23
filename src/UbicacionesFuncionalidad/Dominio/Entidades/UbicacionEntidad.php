<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Dominio\Entidades;

use InvalidArgumentException;
use UbicacionesFuncionalidad\Dominio\Excepciones\InvarianteUbicacionExcepcion;

class UbicacionEntidad
{
    public function __construct(
        private ?int $id_ubicacion,
        private int $id_usuario,
        private string $titulo,
        private ?string $descripcion,
        private float $latitud,
        private float $longitud,
        private ?string $apodo_autor = null
    ) {
        $this->autoValidarEntidad();
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
    }
}
