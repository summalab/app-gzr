<?php

declare(strict_types=1);

namespace Shared\Dominio\Excepciones;

class ExcepcionNegocio extends ExcepcionAplicacion
{
    public function __construct(string $mensaje, int $codigoHttp = 422)
    {
        parent::__construct($mensaje, $codigoHttp);
    }
}
