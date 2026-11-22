<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Persistencia;

use PDO;

class TokenConfirmacionUsuarioDAO
{

    public function __construct(
        private PDO $db
    ) {}

    public function encontrarPorId(int $id_token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT pk_id_token, fk_id_usuario, token_verificacion, fecha_expiracion 
            FROM tokens_confirmacion_usuario 
            WHERE pk_id_token = :id_token 
            LIMIT 1
        ");

        $stmt->execute([':id_token' => $id_token]);
        $row = $stmt->fetch();

        return $row ? $row : null;
    }


    /**
     * Registra un token de verificación asociado a un usuario específico.
     */
    public function crearTokenVerificacion(array $tokenData): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO tokens_confirmacion_usuario (fk_id_usuario, token_verificacion, tipo_token, fecha_expiracion) 
            VALUES (:fk_id_usuario, :token_verificacion, :tipo_token, :fecha_expiracion)
        ");

        $stmt->execute([
            ':fk_id_usuario'      => $tokenData['id_usuario'],
            ':token_verificacion' => $tokenData['token_verificacion'],
            ':tipo_token'         => $tokenData['tipo_token'],
            ':fecha_expiracion'   => $tokenData['fecha_expiracion']
        ]);

        // Retornamos el último ID insertado
        return (int)$this->db->lastInsertId();
    }

    /**
     * Busca los datos de un token y obtiene el ID del usuario asociado mediante un JOIN.
     */
    public function encontrarPorToken(string $token_verificacion): ?array
    {
        $stmt = $this->db->prepare("
            SELECT fk_id_usuario, tipo_token, fecha_expiracion 
            FROM tokens_confirmacion_usuario 
            WHERE token_verificacion = :token 
            LIMIT 1
        ");

        $stmt->execute([':token' => $token_verificacion]);
        $row = $stmt->fetch();

        return $row ? $row : null;
    }

    /**
     * Elimina de forma segura un token una vez ha sido consumido con éxito.
     */
    public function borrarToken(string $token_verificacion): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM tokens_confirmacion_usuario 
            WHERE token_verificacion = :token_verificacion
        ");

        $stmt->execute([':token_verificacion' => $token_verificacion]);
    }

}