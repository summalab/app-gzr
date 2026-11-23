<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Dominio\Excepciones;

use DomainException;

final class InvarianteUbicacionExcepcion extends DomainException
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
