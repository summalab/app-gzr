<?php

declare(strict_types=1);

namespace Shared\Dominio\Servicios;

interface CatalogoMensajesInterface
{
    /**
     * Obtiene un mensaje traducido por su clave (ej. 'ubicacion.fuera_de_zona')
     * y reemplaza marcadores dinámicos como ['radio' => 40].
     */
    public function obtener(string $clave, array $parametros = []): string;
}
