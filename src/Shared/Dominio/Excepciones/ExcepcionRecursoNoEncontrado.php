<?php

declare(strict_types=1);

namespace Shared\Dominio\Excepciones;

class ExcepcionRecursoNoEncontrado extends ExcepcionNegocio
{
    public function __construct(string $mensaje)
    {
        parent::__construct($mensaje, 404);
    }
}
