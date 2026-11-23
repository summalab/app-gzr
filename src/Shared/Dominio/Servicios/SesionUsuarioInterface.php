<?php

declare(strict_types=1);

namespace Shared\Dominio\Servicios;

interface SesionUsuarioInterface
{
    //public function iniciarSesion(int $idUsuario): void;
    public function iniciarSesion(int $idUsuario, string $apodoUsuario, string $rol = 'USUARIO'): void;
    public function obtenerIdUsuarioAutenticado(): ?int;
    public function cerrarSesion(): void;
    public function obtenerRolUsuarioAutenticado(): ?string;

}
