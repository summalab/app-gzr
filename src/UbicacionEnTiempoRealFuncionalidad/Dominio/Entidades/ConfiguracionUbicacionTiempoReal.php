<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Dominio\Entidades;

use InvalidArgumentException;

final class ConfiguracionUbicacionTiempoReal
{
    private const ROLES_PERMITIDOS = [
        'todos',
        'visitante',
        'usuario',
        'moderador',
        'administrador',
    ];

    public function __construct(
        private bool $habilitada,
        private string $rolPermitido
    ) {
        if (!in_array($this->rolPermitido, self::ROLES_PERMITIDOS, true)) {
            throw new InvalidArgumentException('El rol permitido para la ubicación en tiempo real no es válido.');
        }
    }

    public function estaHabilitada(): bool
    {
        return $this->habilitada;
    }

    public function obtenerRolPermitido(): string
    {
        return $this->rolPermitido;
    }

    public function permite(?string $rol): bool
    {
        if (!$this->habilitada) {
            return false;
        }

        if ($this->rolPermitido === 'todos') {
            return true;
        }

        return $this->rolPermitido === ($rol === null ? 'visitante' : strtolower($rol));
    }
}
