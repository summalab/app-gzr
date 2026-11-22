<?php

declare(strict_types=1);

namespace Shared\Dominio\Servicios;
/** 
 * (Nota: La capa de Aplicación puede pedir esta interfaz sin saber que por debajo existe MySQL o PDO).
 */
interface GestorTransaccionesInterface
{
    /**
     * Ejecuta una operación compuesta de forma atómica (ACID).
     * Hace commit automáticamente si tiene éxito o rollBack si ocurre cualquier excepción.
     *
     * @template T
     * @param callable(): T $operacion
     * @return T
     */
    public function ejecutar(callable $operacion): mixed;
}
