<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Infraestructura\Persistencia;

use PDO;

class UbicacionDAO
{
    public function __construct(
        private PDO $db
    ) {}

    /**
     * Inserta una nueva ubicación en MySQL y retorna su ID generado.
     */
    public function insertar(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO ubicaciones (
                fk_id_usuario, titulo, descripcion, latitud, longitud,
                tipo_infraestructura, estado_infraestructura, riesgos_directos,
                riesgos_indirectos, actividades_delictivas, condiciones_sociales
            )
            VALUES (
                :fk_id_usuario, :titulo, :descripcion, :latitud, :longitud,
                :tipo_infraestructura, :estado_infraestructura, :riesgos_directos,
                :riesgos_indirectos, :actividades_delictivas, :condiciones_sociales
            )
        ');

        $stmt->execute([
            ':fk_id_usuario' => $data['fk_id_usuario'],
            ':titulo'        => $data['titulo'],
            ':descripcion'   => $data['descripcion'],
            ':latitud'       => $data['latitud'],
            ':longitud'      => $data['longitud'],
            ':tipo_infraestructura' => $data['tipo_infraestructura'],
            ':estado_infraestructura' => $data['estado_infraestructura'],
            ':riesgos_directos' => $data['riesgos_directos'],
            ':riesgos_indirectos' => $data['riesgos_indirectos'],
            ':actividades_delictivas' => $data['actividades_delictivas'],
            ':condiciones_sociales' => $data['condiciones_sociales'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Busca una ubicación activa por su ID único.
     */
    public function encontrarPorId(int $pk_id_ubicacion): ?array
    {
        $stmt = $this->db->prepare('
            SELECT pk_id_ubicacion, fk_id_usuario, titulo, descripcion, latitud, longitud,
                   tipo_infraestructura, estado_infraestructura, riesgos_directos,
                   riesgos_indirectos, actividades_delictivas, condiciones_sociales
            FROM ubicaciones
            WHERE pk_id_ubicacion = :pk_id_ubicacion
              AND fecha_eliminacion IS NULL
            LIMIT 1
        ');

        $stmt->execute([':pk_id_ubicacion' => $pk_id_ubicacion]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Lista todas las ubicaciones activas de usuarios activos junto con su apodo.
     */
    public function listarTodasActivas(): array
    {
        $stmt = $this->db->query('
            SELECT 
                u.pk_id_ubicacion,
                u.fk_id_usuario,
                u.titulo,
                u.descripcion,
                u.latitud,
                u.longitud,
                u.tipo_infraestructura,
                u.estado_infraestructura,
                u.riesgos_directos,
                u.riesgos_indirectos,
                u.actividades_delictivas,
                u.condiciones_sociales,
                usr.apodo_usuario
            FROM ubicaciones u
            INNER JOIN usuarios usr ON u.fk_id_usuario = usr.pk_id_usuario
            WHERE u.fecha_eliminacion IS NULL
              AND usr.fecha_eliminacion IS NULL
            ORDER BY u.fecha_creacion DESC
        ');

        return $stmt->fetchAll();
    }

    /**
     * Lista únicamente las ubicaciones activas creadas por un usuario específico.
     */
    public function listarActivasPorUsuario(int $fk_id_usuario): array
    {
        $stmt = $this->db->prepare('
            SELECT 
                pk_id_ubicacion,
                fk_id_usuario,
                titulo,
                descripcion,
                latitud,
                longitud,
                tipo_infraestructura,
                estado_infraestructura,
                riesgos_directos,
                riesgos_indirectos,
                actividades_delictivas,
                condiciones_sociales
            FROM ubicaciones
            WHERE fk_id_usuario = :fk_id_usuario
              AND fecha_eliminacion IS NULL
            ORDER BY fecha_creacion DESC
        ');

        $stmt->execute([':fk_id_usuario' => $fk_id_usuario]);

        return $stmt->fetchAll();
    }

    /**
     * Marca una ubicación como eliminada (Soft Delete).
     */
    public function eliminarLogicamente(int $pk_id_ubicacion): void
    {
        $stmt = $this->db->prepare('
            UPDATE ubicaciones
            SET fecha_eliminacion = NOW()
            WHERE pk_id_ubicacion = :pk_id_ubicacion
              AND fecha_eliminacion IS NULL
        ');

        $stmt->execute([':pk_id_ubicacion' => $pk_id_ubicacion]);
    }
}
