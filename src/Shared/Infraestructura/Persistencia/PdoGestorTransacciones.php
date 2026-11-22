<?php

declare(strict_types=1);

namespace Shared\Infraestructura\Persistencia;

use PDO;
use Shared\Dominio\Servicios\GestorTransaccionesInterface;
use Throwable;

/**
 * (Incluye protección contra transacciones anidadas usando $this->pdo->inTransaction(), 
 * evitando el error fatal de PDO "There is already an active transaction" si un 
 * Caso de Uso llama a otro Caso de Uso).
 */
class PdoGestorTransacciones implements GestorTransaccionesInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function ejecutar(callable $operacion): mixed
    {
        // Si ya hay una transacción abierta por un caso de uso padre, reutilizarla
        $esTransaccionRaiz = !$this->pdo->inTransaction();

        if ($esTransaccionRaiz) {
            $this->pdo->beginTransaction();
        }

        try {
            $resultado = $operacion();

            if ($esTransaccionRaiz && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $resultado;
            
        } catch (Throwable $excepcion) {
            if ($esTransaccionRaiz && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $excepcion;
        }
    }
}
