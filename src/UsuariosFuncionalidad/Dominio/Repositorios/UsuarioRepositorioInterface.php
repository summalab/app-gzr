<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Dominio\Repositorios;

use UsuariosFuncionalidad\Dominio\Entidades\UsuarioEntidad;

interface UsuarioRepositorioInterface
{
    /**
     * Guarda un usuario en el sistema de persistencia.
     * Puede ser una inserción (registro) o una actualización.
     */
    public function guardar(UsuarioEntidad $mUsuarioEntidad ): UsuarioEntidad;

    /**
     * Busca un usuario por su ID único.
     */
    public function encontrarPorId(int $id_usuario): ?UsuarioEntidad;

    /**
     * Verifica si un correo electrónico ya está registrado en el sistema.
     * Útil para reglas de validación previas a la creación.
     */
    public function encontrarPorApodo(string $apodo_usuario): bool;

    
    public function encontrarPorEmail(string $correo_usuario): ?UsuarioEntidad;

    /**
     * Activa el estado de verificación de un usuario.
     */
    public function marcarComoVerificado(int $id_usuario): void;

    /**
     * Realiza una baja lógica de la cuenta del usuario sin destruir sus relaciones.
     */
    public function eliminarCuenta(int $id_usuario): void;
    

    // IMPLEMENTACION DE TRANSACCIONES EN LA BASE DE DATOS
    // este mecanismo permite tener control sobre multiples insertos a la base de datos
    // si uno de esos insertos es fallido toda la transaccion reversa la operacion para
    // no dejar reigistros corruptos o sin relacines necesarias con otras tablas,
    public function beginTransaction(): void; //iniciarTransaccion()
    
    public function commit(): void; //comprometerse()
    
    public function rollBack(): void; //revertir()
}
