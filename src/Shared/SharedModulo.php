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

