<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Excepciones;

use Shared\Dominio\Excepciones\ExcepcionNegocio;

class ReglaNegocioExcepcion extends ExcepcionNegocio
{

    public static function apodoDuplicado(string $apodo): self
    {
        return new self('usuario.apodo_duplicado', 422);
    }

    public static function correoDuplicado(string $correo): self
    {
        return new self('usuario.correo_duplicado', 422);
    }

    public static function contrasenaCorta(int $minimo = 8): self
    {
        return new self('usuario.contrasena_corta', 422);
    }

    public static function tokenInvalido(): self
    {
        return new self('usuario.token_invalido', 422);
    }

    public static function tokenExpirado(): self
    {
        return new self('usuario.token_expirado', 422);
    }

    public static function usuarioYaVerificado(): self
    {
        return new self('usuario.ya_verificado', 422);
    }

}
