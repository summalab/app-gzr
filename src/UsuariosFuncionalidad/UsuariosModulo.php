<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad;

use Core\Contenedor;
use Core\Router;
use UsuariosFuncionalidad\Dominio\Repositorios\UsuarioRepositorioInterface;
use UsuariosFuncionalidad\Infraestructura\Repositorios\MySQLUsuarioRepositorio;
use UsuariosFuncionalidad\Dominio\Repositorios\TokenConfirmacionUsuarioRepositorioInterface;
use UsuariosFuncionalidad\Infraestructura\Repositorios\MySQLTokenConfirmacionUsuarioRepositorio;
use UsuariosFuncionalidad\Dominio\Servicios\EnviadorCorreoInterface;
use UsuariosFuncionalidad\Infraestructura\Servicios\LogLocalEnviadorCorreo;
use UsuariosFuncionalidad\Infraestructura\Servicios\SmtpEnviadorCorreo;
use UsuariosFuncionalidad\Http\UsuarioControlador;

class UsuariosModulo
{
    public static function registrarDependencias(Contenedor $contenedor, bool $usarCorreoLocal = true): void
    {
        $contenedor->bind(
            UsuarioRepositorioInterface::class,
            MySQLUsuarioRepositorio::class
        );

        $contenedor->bind(
            TokenConfirmacionUsuarioRepositorioInterface::class,
            MySQLTokenConfirmacionUsuarioRepositorio::class
        );

        if ($usarCorreoLocal) {

            $contenedor->bind(
                EnviadorCorreoInterface::class, 
                LogLocalEnviadorCorreo::class
            );
        } else {
            
            $contenedor->bind(
                EnviadorCorreoInterface::class, 
                SmtpEnviadorCorreo::class
            );
        }
    }

    public static function registrarRutas(Router $enrutador): void
    {
        // Rutas públicas
        $enrutador->addRoute('POST', '/registrar-usuario', UsuarioControlador::class, 'registrar');
        $enrutador->addRoute('GET', '/verificar-correo', UsuarioControlador::class, 'verificar');
        $enrutador->addRoute('POST', '/solicitar-recuperacion', UsuarioControlador::class, 'solicitarRecuperacion');
        $enrutador->addRoute('POST', '/restablecer-contrasena', UsuarioControlador::class, 'restablecerContrasena');

        // Rutas privadas (protegidas por sesión en el controlador)
        $enrutador->addRoute('POST', '/actualizar-apodo', UsuarioControlador::class, 'actualizarApodo');
        $enrutador->addRoute('POST', '/actualizar-correo', UsuarioControlador::class, 'actualizarCorreo');
        $enrutador->addRoute('POST', '/cambiar-contrasena', UsuarioControlador::class, 'cambiarContrasena');
        $enrutador->addRoute('POST', '/eliminar-cuenta', UsuarioControlador::class, 'eliminarCuenta');
    }
}
