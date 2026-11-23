<?php

declare(strict_types=1);

namespace Shared;

use Core\Contenedor;
use Core\Router;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;
use Shared\Dominio\Servicios\RegistradorLogInterface;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Infraestructura\Servicios\ArchivoCatalogoMensajes;
use Shared\Infraestructura\Servicios\ArchivoRegistradorLog;
use Shared\Infraestructura\Servicios\NativaPhpSesionUsuario;
use Shared\Dominio\Servicios\GestorTransaccionesInterface;
use Shared\Infraestructura\Persistencia\PdoGestorTransacciones;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Infraestructura\SeguridadSesionAutorizador;

class SharedModulo
{
    
    public static function registrarDependencias(Contenedor $contenedor): void
    {
        $contenedor->bind(
            SesionUsuarioInterface::class, 
            NativaPhpSesionUsuario::class
        );
        
        $contenedor->bind(
            RegistradorLogInterface::class,
            ArchivoRegistradorLog::class
        );
        
        $contenedor->bind(
            CatalogoMensajesInterface::class,
            ArchivoCatalogoMensajes::class
        );

        $contenedor->bind(
            GestorTransaccionesInterface::class,
            PdoGestorTransacciones::class
        );

        $contenedor->bind(
            AutorizadorInterface::class, 
            SesionAutorizador::class
        );

    }

    public static function registrarRutas(Router $enrutador): void
    {
        /*
        // Rutas públicas
        $enrutador->addRoute('POST', '/registrar-usuario', UsuarioControlador::class, 'registrar');
        $enrutador->addRoute('GET', '/verificar-correo', UsuarioControlador::class, 'verificar');
        */
    }

}

