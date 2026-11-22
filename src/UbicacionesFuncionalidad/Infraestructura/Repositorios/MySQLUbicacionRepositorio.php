<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Infraestructura\Repositorios;

use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;
use UbicacionesFuncionalidad\Infraestructura\Persistencia\UbicacionDAO;

class MySQLUbicacionRepositorio implements UbicacionRepositorioInterface
{
    public function __construct(
        private UbicacionDAO $mUbicacionDao
    ) {}

    public function guardar(UbicacionEntidad $ubicacion): UbicacionEntidad
    {
        $data = [
            'fk_id_usuario' => $ubicacion->obtenerIdUsuario(),
            'titulo'        => $ubicacion->obtenerTitulo(),
            'descripcion'   => $ubicacion->obtenerDescripcion(),
            'latitud'       => $ubicacion->obtenerLatitud(),
            'longitud'      => $ubicacion->obtenerLongitud(),
        ];

        $idCreado = $this->mUbicacionDao->insertar($data);

        return new UbicacionEntidad(
            id_ubicacion: $idCreado,
            id_usuario: $ubicacion->obtenerIdUsuario(),
            titulo: $ubicacion->obtenerTitulo(),
            descripcion: $ubicacion->obtenerDescripcion(),
            latitud: $ubicacion->obtenerLatitud(),
            longitud: $ubicacion->obtenerLongitud(),
            apodo_autor: $ubicacion->obtenerApodoAutor()
        );
    }

    public function encontrarPorId(int $id_ubicacion): ?UbicacionEntidad
    {
        $row = $this->mUbicacionDao->encontrarPorId($id_ubicacion);

        if (!$row) {
            return null;
        }

        return $this->hidratarEntidad($row);
    }

    public function listarTodasActivas(): array
    {
        $filas = $this->mUbicacionDao->listarTodasActivas();

        return array_map(
            fn(array $fila): UbicacionEntidad => $this->hidratarEntidad($fila),
            $filas
        );
    }

    public function listarActivasPorUsuario(int $id_usuario): array
    {
        $filas = $this->mUbicacionDao->listarActivasPorUsuario($id_usuario);

        return array_map(
            fn(array $fila): UbicacionEntidad => $this->hidratarEntidad($fila),
            $filas
        );
    }

    public function eliminarLogicamente(int $id_ubicacion): void
    {
        $this->mUbicacionDao->eliminarLogicamente($id_ubicacion);
    }

    /**
     * Convierte una fila asociativa de MySQL en una instancia segura de UbicacionEntidad.
     */
    private function hidratarEntidad(array $row): UbicacionEntidad
    {
        return new UbicacionEntidad(
            id_ubicacion: (int)$row['pk_id_ubicacion'],
            id_usuario: (int)$row['fk_id_usuario'],
            titulo: (string)$row['titulo'],
            descripcion: isset($row['descripcion']) ? (string)$row['descripcion'] : null,
            latitud: (float)$row['latitud'],
            longitud: (float)$row['longitud'],
            apodo_autor: isset($row['apodo_usuario']) ? (string)$row['apodo_usuario'] : null
        );
    }
}
