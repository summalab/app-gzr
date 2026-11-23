<?php

declare(strict_types=1);

namespace Shared\Infraestructura\Servicios;

use Shared\Dominio\Servicios\SesionUsuarioInterface;

class NativaPhpSesionUsuario implements SesionUsuarioInterface
{
    public function __construct()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
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


    public function iniciarSesion(int $idUsuario, string $apodoUsuario, string $rolUsuario = 'usuario'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['id_usuario_autenticado'] = $idUsuario;
        $_SESSION['rol_usuario_autenticado'] = strtolower($rolUsuario);
        $_SESSION['apodo_usuario_autenticado'] = $apodoUsuario;
    }

    public function obtenerRolUsuarioAutenticado(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['rol_usuario_autenticado']) ? (string)$_SESSION['rol_usuario_autenticado'] : null;
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
