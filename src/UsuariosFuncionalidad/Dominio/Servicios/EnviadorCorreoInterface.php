<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Servicios;

interface EnviadorCorreoInterface
{
    /**
     * Envía el enlace de activación al correo del usuario recién registrado.
     */
    public function enviarCorreoVerificacion(string $correoDestino, string $apodoUsuario, string $enlaceVerificacion): void;
    
    public function enviarCorreoRecuperacion(string $correoDestino, string $apodoUsuario, string $enlaceRecuperacion): void;
}
