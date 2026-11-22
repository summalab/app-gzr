<?php

declare(strict_types=1);

namespace Shared\Dominio\Servicios;

use Throwable;

interface RegistradorLogInterface
{
    public function registrarErrorCritico(Throwable $excepcion, string $contexto = 'Sistema'): void;

    public function registrarAdvertencia(string $mensaje, string $contexto = 'Sistema'): void;
}
