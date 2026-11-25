<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Infraestructura\Persistencia;

use PDO;

final class ConfiguracionUbicacionTiempoRealDAO
{
    private const CLAVE = 'ubicacion_en_tiempo_real';

    public function __construct(private PDO $db) {}

    public function obtener(): ?array
    {
        $consulta = $this->db->prepare('
            SELECT habilitada, rol_permitido
            FROM configuraciones_administrativas
            WHERE clave_configuracion = :clave
            LIMIT 1
        ');
        $consulta->execute([':clave' => self::CLAVE]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    public function guardar(bool $habilitada, string $rolPermitido, int $idAdministrador): void
    {
        $consulta = $this->db->prepare('
            INSERT INTO configuraciones_administrativas (
                clave_configuracion,
                habilitada,
                rol_permitido,
                actualizado_por
            ) VALUES (
                :clave,
                :habilitada,
                :rol_permitido,
                :actualizado_por
            )
            ON DUPLICATE KEY UPDATE
                habilitada = VALUES(habilitada),
                rol_permitido = VALUES(rol_permitido),
                actualizado_por = VALUES(actualizado_por),
                actualizado_en = CURRENT_TIMESTAMP
        ');

        $consulta->execute([
            ':clave' => self::CLAVE,
            ':habilitada' => (int)$habilitada,
            ':rol_permitido' => $rolPermitido,
            ':actualizado_por' => $idAdministrador,
        ]);
    }
}
