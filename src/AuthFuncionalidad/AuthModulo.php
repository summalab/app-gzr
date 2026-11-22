<?php

declare(strict_types=1);

namespace AuthFuncionalidad;

use Core\Contenedor;
use Core\Router;
use AuthFuncionalidad\Http\AuthControlador;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Infraestructura\Repositorios\MySQLUsuarioRepositorio;

class AuthModulo
{
    /**
     * Registra las dependencias específicas del módulo de Autenticación.
     * Como AuthControlador y sus Casos de Uso dependen de UsuarioRepositorioInterface
     * y SesionUsuarioInterface (ya enlazados en el núcleo/Usuarios), el Autowiring
     * los resuelve automáticamente sin configuraciones extra.
     */
    public static function registrarDependencias(Contenedor $contenedor): void
    {
        // Espacio reservado por si en el futuro agregas servicios propios de Auth
        // (ej. límite de intentos fallidos / RateLimiterInterface).
        $contenedor->bind(
            UsuarioRepositorioInterface::class,
            MySQLUsuarioRepositorio::class
        );
    }

    /**
     * Registra los endpoints HTTP públicos y de estado de sesión del módulo.
     */
    public static function registrarRutas(Router $enrutador): void
    {
        $enrutador->addRoute('POST', '/login', AuthControlador::class, 'login');
        $enrutador->addRoute('POST', '/logout', AuthControlador::class, 'logout');
        $enrutador->addRoute('GET', '/verificar-sesion', AuthControlador::class, 'verificarSesion');
    }
}
