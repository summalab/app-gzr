<?php

declare(strict_types=1);

namespace Shared\Dominio\Servicios;

interface AutorizadorInterface
{
    /**
     * Verifica si el usuario actual posee el permiso indicado.
     */
    public function puede(string $permiso): bool;

    /**
     * Exige el permiso indicado; si no lo tiene, lanza ReglaNegocioExcepcion (HTTP 403).
     */
    public function exigirPermiso(string $permiso): void;
}


//Contrato limpio para validar permisos sin acoplar los casos de uso a la sesión HTTP.