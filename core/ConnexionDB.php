<?php

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use Exception;

class ConnexionDB
{
    /**
     * Variable interna para cachear la conexión nativa (Instancia única).
     */
    private static ?PDO $instance = null;

    /**
     * Fábrica estática encargada de configurar e instanciar la conexión PDO desde .env.
     *
     * @return PDO Instancia activa y configurada de la conexión a la base de datos.
     * @throws Exception Si faltan variables críticas o falla la conexión a MySQL.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host     = EnvLoader::obtener('DB_HOST', 'localhost');
        $port     = EnvLoader::obtener('DB_PORT', '3306');
        $dbName   = EnvLoader::obtener('DB_NAME');
        $username = EnvLoader::obtener('DB_USER');
        $password = EnvLoader::obtener('DB_PASS', '');
        $charset  = EnvLoader::obtener('DB_CHARSET', 'utf8mb4');

        if ($dbName === '' || $username === '') {
            throw new Exception('Error Crítico de Infraestructura: Faltan las credenciales DB_NAME o DB_USER en el archivo .env.');
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$instance = new PDO($dsn, $username, $password, $options);

            return self::$instance;
        } catch (PDOException $e) {
            error_log('Error de conexión MySQL en ConnexionDB: ' . $e->getMessage());
            throw new Exception('Error Crítico de Infraestructura: No se pudo establecer la conexión con la base de datos.');
        }
    }

    private function __construct() {}

    private function __clone() {}
}
