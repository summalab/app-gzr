<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Excepciones;

use DomainException;

final class InvarianteUsuarioExcepcion extends DomainException
{
    public function __construct(private readonly string $claveInvariante)
    {
        parent::__construct($claveInvariante, 422);
    }

    public function obtenerClaveMensaje(): string
    {
        return $this->claveInvariante;
    }
}
