<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Repositorios;

use UsuariosFuncionalidad\Dominio\Entidades\TokenConfirmacionUsuarioEntidad;

interface TokenConfirmacionUsuarioRepositorioInterface
{
    /**
     * Busca un usuario por su ID único.
     */
    public function encontrarPorId(int $id_token): ?TokenConfirmacionUsuarioEntidad;
    
    /**
     * Registra un token de verificación para un usuario.
     */
    /*
    public function crearTokenVerificacion(
        string $id_usuario, 
        string $token_verificacion, 
        string $fecha_expiracion
    ): void;
    */

    public function crearTokenVerificacion(TokenConfirmacionUsuarioEntidad $mTokenConfirmacionUsuarioEntidad): TokenConfirmacionUsuarioEntidad;

    /**
     * Encuentra los datos de un token activo.
     */
    public function encontrarPorToken(string $token_verificacion): ?array;

    /**
     * Remueve un token del almacenamiento.
     */
    public function borrarToken(string $token_verificacion): void;
}
