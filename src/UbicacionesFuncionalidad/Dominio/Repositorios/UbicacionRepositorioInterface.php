<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Dominio\Repositorios;

use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;

interface UbicacionRepositorioInterface
{
    /**
     * Persiste una nueva ubicación en el sistema y la devuelve con su ID asignado.
     */
    public function guardar(UbicacionEntidad $ubicacion): UbicacionEntidad;

    /**
     * Busca una ubicación activa por su identificador único.
     */
    public function encontrarPorId(int $id_ubicacion): ?UbicacionEntidad;

    /**
     * Obtiene todas las ubicaciones activas de la comunidad (incluyendo el apodo del autor).
     *
     * @return UbicacionEntidad[]
     */
    public function listarTodasActivas(): array;

    /**
     * Obtiene únicamente las ubicaciones activas creadas por un usuario específico.
     *
     * @return UbicacionEntidad[]
     */
    public function listarActivasPorUsuario(int $id_usuario): array;

    /**
     * Marca una ubicación como eliminada registrando su fecha_eliminacion (Soft Delete).
     */
    public function eliminarLogicamente(int $id_ubicacion): void;
}
