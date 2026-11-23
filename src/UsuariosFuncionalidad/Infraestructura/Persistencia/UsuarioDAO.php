<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Persistencia;

use PDO;

class UsuarioDAO
{
    /**
     * Inyectamos la conexión PDO nativa.
     */
    public function __construct(
        private PDO $db
    ) {}

    /**
     * Inserta un nuevo registro en la tabla de usuarios.
     * 
     * @param array $data Contiene ['email' => '...', 'password_hash' => '...']
     * @return string El ID generado por la base de datos (UUID o Autoincremental).
     */
    public function insertar(array $data): int
    {
        // Usamos sentencias preparadas para blindar el sistema contra Inyección SQL
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (apodo_usuario, correo_usuario, contrasena_hash_usuario, esta_verificado_usuario, rol)
            VALUES (:apodo_usuario, :correo_usuario, :contrasena_hash_usuario, :esta_verificado_usuario, :rol)
        ");

        $stmt->execute([
            ':apodo_usuario'           => $data['apodo_usuario'],
            ':correo_usuario'          => $data['correo_usuario'],
            ':contrasena_hash_usuario' => $data['contrasena_hash_usuario'],
            ':esta_verificado_usuario' => (int)$data['esta_verificado_usuario'],
            ':rol'                     => (string)($data['rol'] ?? 'usuario')
        ]);

        // Retornamos el último ID insertado
        //return (string)$this->db->lastInsertId();
        return (int)$this->db->lastInsertId();
    }

    /**
     * Busca un usuario por su correo electrónico.
     */
    public function encontrarPorApodo(string $apodo_usuario): ?array
    {
        $stmt = $this->db->prepare("
            SELECT pk_id_usuario, apodo_usuario, correo_usuario, contrasena_hash_usuario, esta_verificado_usuario, rol 
            FROM usuarios 
            WHERE apodo_usuario = :apodo_usuario AND fecha_eliminacion IS NULL
            LIMIT 1
        ");

        $stmt->execute([':apodo_usuario' => $apodo_usuario]);
        $row = $stmt->fetch();

        return $row ? $row : null;
    }
    /**
     * Busca un usuario por su correo electrónico.
     */
    public function encontrarPorEmail(string $correo_usuario): ?array
    {
        $stmt = $this->db->prepare("
            SELECT pk_id_usuario, apodo_usuario, correo_usuario, contrasena_hash_usuario ,esta_verificado_usuario, rol
            FROM usuarios 
            WHERE correo_usuario = :correo_usuario AND fecha_eliminacion IS NULL
            LIMIT 1
        ");

        $stmt->execute([':correo_usuario' => $correo_usuario]);
        $row = $stmt->fetch();

        return $row ? $row : null;
    }

    /**
     * Busca un usuario por su ID único.
     */
    public function encontrarPorId(int $pk_id_usuario): ?array
    {
        $stmt = $this->db->prepare("
            SELECT pk_id_usuario, apodo_usuario, correo_usuario, contrasena_hash_usuario, esta_verificado_usuario, rol
            FROM usuarios 
            WHERE pk_id_usuario = :pk_id_usuario AND fecha_eliminacion IS NULL
            LIMIT 1
        ");

        $stmt->execute([':pk_id_usuario' => $pk_id_usuario]);
        $row = $stmt->fetch();

        return $row ? $row : null;
    }

    /**
     * Cambia el estatus del usuario en la tabla principal a verificado.
     */
    public function actualizarVerificacionStatus(int $pk_id_usuario, bool $esta_verificado_usuario): void
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET esta_verificado_usuario = :esta_verificado_usuario 
            WHERE pk_id_usuario = :pk_id_usuario
        ");

        $stmt->execute([
            ':esta_verificado_usuario' => $esta_verificado_usuario,
            ':pk_id_usuario'           => $pk_id_usuario
        ]);
    }


    /**
     * Cambia el apodo del usuario.
     */
    public function actualizar(array $data): void
    {
         $stmt = $this->db->prepare("
        UPDATE usuarios 
        SET apodo_usuario = :apodo_usuario,
            correo_usuario = :correo_usuario,
            contrasena_hash_usuario = :contrasena_hash_usuario,
            esta_verificado_usuario = :esta_verificado_usuario,
            rol = :rol
        WHERE pk_id_usuario = :pk_id_usuario
        ");

        $stmt->execute([
            ':apodo_usuario'           => $data['apodo_usuario'],
            ':correo_usuario'          => $data['correo_usuario'],
            ':contrasena_hash_usuario' => $data['contrasena_hash_usuario'],
            ':esta_verificado_usuario' => (int)$data['esta_verificado_usuario'],
            ':rol'                     => (string)($data['rol'] ?? 'usuario'),
            ':pk_id_usuario'           => $data['pk_id_usuario']
        ]);
    }

    /**
     * Marca la cuenta como eliminada registrando la fecha y hora actual (Soft Delete).
     */
    public function eliminarLogicamente(int $pk_id_usuario): void
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET fecha_eliminacion = NOW() 
            WHERE pk_id_usuario = :pk_id_usuario 
              AND fecha_eliminacion IS NULL
        ");

        $stmt->execute([':pk_id_usuario' => $pk_id_usuario]);
    }



    public function beginTransaction(): void {
        // Si tu DAO tiene el PDO, puedes hacer $this->userDao->getPdo()->beginTransaction();
        $this->db->beginTransaction(); 
    }

    public function commit(): void {
        $this->db->commit();
    }

    public function rollBack(): void {
        $this->db->rollBack();
    }


}