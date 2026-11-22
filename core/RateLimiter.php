<?php

declare(strict_types=1);

namespace Core;

class RateLimiter
{
    private string $directorioAlmacenamiento;

    public function __construct(string $directorioAlmacenamiento)
    {
        $this->directorioAlmacenamiento = rtrim($directorioAlmacenamiento, '/\\');

        if (!is_dir($this->directorioAlmacenamiento)) {
            mkdir($this->directorioAlmacenamiento, 0775, true);
        }
    }

    /**
     * Evalúa el límite de peticiones por IP con penalización progresiva a bots persistentes.
     */
    public function verificar(
        string $ipCliente,
        string $rutaRuta,
        int $limiteMaximo = 60,
        int $ventanaSegundos = 60
    ): array {
        // Purga periódica (1 de cada 15 peticiones)
        if (random_int(1, 15) === 1) {
            $this->limpiarRegistrosExpirados();
        }

        $claveIp = hash('sha256', $ipCliente);
        $archivoRegistro = $this->directorioAlmacenamiento . '/' . $claveIp . '.json';
        $ahora = time();

        $fp = fopen($archivoRegistro, 'c+');
        if ($fp === false) {
            return ['permitido' => true, 'restantes' => 1, 'reintentarEn' => 0];
        }

        flock($fp, LOCK_EX);

        $contenido = stream_get_contents($fp);
        $registroIp = ($contenido !== false && $contenido !== '')
            ? json_decode($contenido, true)
            : [];

        if (!is_array($registroIp)) {
            $registroIp = [];
        }

        // 1. Limpiar internamente únicamente las rutas cuya ventana ya expiró
        foreach ($registroIp as $rutaGuardada => $info) {
            if (!is_array($info) || !isset($info['expira']) || $ahora >= (int)$info['expira']) {
                unset($registroIp[$rutaGuardada]);
            }
        }

        // 2. Inicializar contador si es la primera petición en esta ventana
        if (!isset($registroIp[$rutaRuta])) {
            $registroIp[$rutaRuta] = [
                'intentos' => 0,
                'expira'   => $ahora + $ventanaSegundos,
            ];
        }

        $registroIp[$rutaRuta]['intentos'] = (int)$registroIp[$rutaRuta]['intentos'] + 1;
        $intentosActuales = $registroIp[$rutaRuta]['intentos'];

        // 3. Detección de Bot: Si insiste más del doble del límite estando bloqueado,
        // extendemos el castigo a 15 minutos (900 segundos)
        if ($intentosActuales > ($limiteMaximo * 2)) {
            $registroIp[$rutaRuta]['expira'] = max((int)$registroIp[$rutaRuta]['expira'], $ahora + 900);
        }

        $permitido = $intentosActuales <= $limiteMaximo;
        $restantes = max(0, $limiteMaximo - $intentosActuales);
        $reintentarEn = max(1, (int)$registroIp[$rutaRuta]['expira'] - $ahora);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)json_encode($registroIp));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return [
            'permitido'    => $permitido,
            'restantes'    => $restantes,
            'reintentarEn' => $reintentarEn,
        ];
    }

    /**
     * Obtiene la IP real del visitante (compatible con proxies inversos y Cloudflare).
     */
    public static function obtenerIpCliente(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        return trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
    }


    /**
     * Purga segura de archivos JSON por IP: solo borra el archivo si TODAS las rutas
     * registradas dentro de esa IP ya expiraron o si el archivo está corrupto/vacío.
     */
    private function limpiarRegistrosExpirados(): void
    {
        $archivos = glob($this->directorioAlmacenamiento . '/*.json');
        if ($archivos === false) {
            return;
        }

        $ahora = time();

        foreach ($archivos as $archivo) {
            if (!is_file($archivo)) {
                continue;
            }

            // Si lleva más de 30 minutos (1800 s) sin actividad, supera cualquier castigo máximo
            if (($ahora - filemtime($archivo)) > 1800) {
                @unlink($archivo);
                continue;
            }

            $contenido = @file_get_contents($archivo);
            $registroIp = ($contenido !== false && $contenido !== '')
                ? json_decode($contenido, true)
                : null;

            // Si el archivo está vacío o corrupto, eliminarlo
            if (!is_array($registroIp) || empty($registroIp)) {
                @unlink($archivo);
                continue;
            }

            // Verificar si queda al menos UNA ruta con bloqueo/ventana aún vigente
            $tieneVentanaActiva = false;
            foreach ($registroIp as $info) {
                if (is_array($info) && isset($info['expira']) && $ahora < (int)$info['expira']) {
                    $tieneVentanaActiva = true;
                    break;
                }
            }

            // Solo si TODAS las rutas de esa IP ya expiraron, borramos el archivo del disco
            if (!$tieneVentanaActiva) {
                @unlink($archivo);
            }
        }
    }

}
