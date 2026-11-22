<?php

declare(strict_types=1);

namespace Shared\Infraestructura\Servicios;

use Core\EnvLoader;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class ArchivoCatalogoMensajes implements CatalogoMensajesInterface
{
    private array $mensajes = [];

    public function __construct()
    {
        $idioma = strtolower(EnvLoader::obtener('APP_LOCALE', 'es'));
        $rutaArchivo = dirname(__DIR__) . "/Idiomas/{$idioma}.php";

        if (!file_exists($rutaArchivo)) {
            $rutaArchivo = dirname(__DIR__) . '/Idiomas/es.php';
        }

        $cargado = require $rutaArchivo;
        $this->mensajes = is_array($cargado) ? $cargado : [];
    }

    public function obtener(string $clave, array $parametros = []): string
    {
        $segmentos = explode('.', $clave);
        $valor = $this->mensajes;

        foreach ($segmentos as $segmento) {
            if (!is_array($valor) || !array_key_exists($segmento, $valor)) {
                return $clave; // Fallback seguro: devuelve la clave si no existe traducción
            }
            $valor = $valor[$segmento];
        }

        if (!is_string($valor)) {
            return $clave;
        }

        foreach ($parametros as $nombre => $reemplazo) {
            $valor = str_replace('{' . $nombre . '}', (string)$reemplazo, $valor);
        }

        return $valor;
    }
}
