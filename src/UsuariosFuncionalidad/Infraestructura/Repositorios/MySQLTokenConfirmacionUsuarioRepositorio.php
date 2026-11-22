<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Infraestructura\Repositorios;

use UsuariosFuncionalidad\Dominio\Entidades\TokenConfirmacionUsuarioEntidad;
use UsuariosFuncionalidad\Dominio\Repositorios\TokenConfirmacionUsuarioRepositorioInterface;
use UsuariosFuncionalidad\Infraestructura\Persistencia\TokenConfirmacionUsuarioDAO;

class MySQLTokenConfirmacionUsuarioRepositorio implements TokenConfirmacionUsuarioRepositorioInterface
{

    /**
     * El repositorio depende del DAO técnico para realizar las consultas SQL.
     */
    public function __construct(
        private TokenConfirmacionUsuarioDAO $mTokenConfirmacionUsuarioDAO
    ) {}

    public function encontrarPorId(int $id_token): ?TokenConfirmacionUsuarioEntidad
    {

        $array = $this->mTokenConfirmacionUsuarioDAO->encontrarPorId($id_token);

        if (!$array) return null;

        return new TokenConfirmacionUsuarioEntidad(
            id_token: (int)$array['pk_id_token'],
            id_usuario: (int)$array['fk_id_usuario'],
            token_verificacion: $array['token_verificacion'],
            fecha_expiracion: $array['fecha_expiracion']
        );
    }

    /**
     * Encapsula los parámetros primitivos y delega la inserción del token al DAO.
     */
    //public function crearTokenVerificacion(string $id_usuario, string $token_verificacion, string $fecha_expiracion): void;
    public function crearTokenVerificacion(TokenConfirmacionUsuarioEntidad $mTokenConfirmacionUsuarioEntidad): TokenConfirmacionUsuarioEntidad
    {
        /*
        $this->mTokenConfirmacionUsuarioDAO->insertarToken([
            'id_usuario'         => $id_usuario,
            'token_verificacion' => $token_verificacion,
            'fecha_expiracion'   => $fecha_expiracion
        ]);
        */

        // Extraemos los datos de la entidad en un arreglo plano para el DAO
        $data = [
            'id_usuario'         => $mTokenConfirmacionUsuarioEntidad->obtenerIdUsuario(),
            'token_verificacion' => $mTokenConfirmacionUsuarioEntidad->obtenerTokenConfirmacion(),
            'fecha_expiracion'   => $mTokenConfirmacionUsuarioEntidad->obtenerFechaExpiracion(),
            'tipo_token'         => $mTokenConfirmacionUsuarioEntidad->obtenerTipoToken(),
        ];


        $id_token_generado = $this->mTokenConfirmacionUsuarioDAO->crearTokenVerificacion($data);


        return new TokenConfirmacionUsuarioEntidad(
            id_token: $id_token_generado,
            id_usuario: $mTokenConfirmacionUsuarioEntidad->obtenerIdUsuario(),
            token_verificacion: $mTokenConfirmacionUsuarioEntidad->obtenerTokenConfirmacion(),
            fecha_expiracion: $mTokenConfirmacionUsuarioEntidad->obtenerFechaExpiracion(),
            tipo_token: $mTokenConfirmacionUsuarioEntidad->obtenerTipoToken(),
        );
    }


    /**
     * Recupera el arreglo asociativo del token directo desde el DAO técnico.
     */
    public function encontrarPorToken(string $token_verificacion): ?array
    {
        return $this->mTokenConfirmacionUsuarioDAO->encontrarPorToken($token_verificacion);
    }


    /**
     * Ordena la remoción definitiva del token consumido al DAO.
     */
    public function borrarToken(string $token_verificacion): void
    {
        $this->mTokenConfirmacionUsuarioDAO->borrarToken($token_verificacion);
    }

}

