<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

class EnvLoader
{
    /**
     * Carga las variables de un archivo .env en $_ENV y getenv().
     */
    public static function cargar(string $rutaArchivoEnv): void
    {
        if (!is_file($rutaArchivoEnv) || !is_readable($rutaArchivoEnv)) {
            throw new RuntimeException('Error crítico de configuración: No se encontró el archivo .env en la raíz del proyecto.');
        }

        $lineas = file($rutaArchivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return;
        }

        foreach ($lineas as $linea) {
            $lineaLimpia = trim($linea);

            // Ignorar comentarios y líneas sin signo '='
            if ($lineaLimpia === '' || str_starts_with($lineaLimpia, '#') || !str_contains($lineaLimpia, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $lineaLimpia, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            // Eliminar comillas simples o dobles envolventes si existen
            if (
                (str_starts_with($valor, '"') && str_ends_with($valor, '"')) ||
                (str_starts_with($valor, "'") && str_ends_with($valor, "'"))
            ) {
                $valor = substr($valor, 1, -1);
            }

            if ($clave !== '' && !array_key_exists($clave, $_ENV)) {
                $_ENV[$clave] = $valor;
                putenv("{$clave}={$valor}");
            }
        }
    }

    /**
     * Obtiene el valor de una variable de entorno o devuelve un valor por defecto.
     */
    public static function obtener(string $clave, string $porDefecto = ''): string
    {
        if (isset($_ENV[$clave])) {
            return (string)$_ENV[$clave];
        }

        $valor = getenv($clave);
        return $valor !== false ? (string)$valor : $porDefecto;
    }
}
