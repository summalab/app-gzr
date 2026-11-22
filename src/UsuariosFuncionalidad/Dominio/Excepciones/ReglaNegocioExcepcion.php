<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Excepciones;

use Exception;

class ReglaNegocioExcepcion extends Exception
{

    public static function apodoDuplicado(string $apodo): self
    {
        return new self("El apodo '{$apodo}' ya se encuentra registrado por otro usuario.", 422);
    }

    public static function correoDuplicado(string $correo): self
    {
        return new self("El correo electrónico '{$correo}' ya se encuentra registrado.", 422);
    }

    public static function contrasenaCorta(int $minimo = 8): self
    {
        return new self("La contraseña debe tener al menos {$minimo} caracteres.", 422);
    }

    public static function tokenInvalido(): self
    {
        return new self("El enlace de verificación no es válido o ya fue utilizado.", 422);
    }

    public static function tokenExpirado(): self
    {
        return new self("El enlace ha expirado. Por favor, solicita uno nuevo.", 422);
    }

}
