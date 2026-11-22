<?php

declare(strict_types=1);

namespace Shared\Infraestructura\Servicios;

use Shared\Dominio\Servicios\SesionUsuarioInterface;

class NativaPhpSesionUsuario implements SesionUsuarioInterface
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

            session_start([
                'cookie_httponly' => true,     // Impide que JS lea la cookie (Protección XSS)
                'cookie_secure'   => $esHttps, // true en HTTPS (Producción), false en HTTP (Laragon)
                'cookie_samesite' => 'Strict', // Protege contra falsificación de peticiones (CSRF)
                'use_strict_mode' => true      // Rechaza IDs de sesión no generados por el servidor
            ]);
        }
    }

    public function iniciarSesion(int $idUsuario): void
    {
        // Regenera el ID de sesión al hacer login para evitar ataques de fijación de sesión
        session_regenerate_id(true);
        $_SESSION['id_usuario_autenticado'] = $idUsuario;
    }

    public function obtenerIdUsuarioAutenticado(): ?int
    {
        return isset($_SESSION['id_usuario_autenticado']) 
            ? (int)$_SESSION['id_usuario_autenticado'] 
            : null;
    }

    public function cerrarSesion(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
