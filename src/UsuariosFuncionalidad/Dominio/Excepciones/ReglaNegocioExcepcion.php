<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Excepciones;

use Shared\Dominio\Excepciones\ExcepcionNegocio;

class ReglaNegocioExcepcion extends ExcepcionNegocio
{

    public static function apodoDuplicado(string $apodo): self
    {
        $mensajes = require dirname(__DIR__, 3) . '/Shared/Infraestructura/Idiomas/es.php';
        return new self($mensajes['usuario']['apodo_duplicado'], 422);
    }

    public static function correoDuplicado(string $correo): self
    {
        $mensajes = require dirname(__DIR__, 3) . '/Shared/Infraestructura/Idiomas/es.php';
        return new self($mensajes['usuario']['correo_duplicado'], 422);
    }

    public static function contrasenaCorta(int $minimo = 8): self
    {
        $mensajes = require dirname(__DIR__, 3) . '/Shared/Infraestructura/Idiomas/es.php';
        return new self(str_replace('{minimo}', (string)$minimo, $mensajes['usuario']['contrasena_corta']), 422);
    }

    public static function tokenInvalido(): self
    {
        $mensajes = require dirname(__DIR__, 3) . '/Shared/Infraestructura/Idiomas/es.php';
        return new self($mensajes['usuario']['token_invalido'], 422);
    }

    public static function tokenExpirado(): self
    {
        $mensajes = require dirname(__DIR__, 3) . '/Shared/Infraestructura/Idiomas/es.php';
        return new self($mensajes['usuario']['token_expirado'], 422);
    }

}
