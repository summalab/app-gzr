<?php

declare(strict_types=1);

// Configurar zona horaria oficial de Colombia para logs, tokens y fechas de PHP
date_default_timezone_set('America/Bogota');

// 1. Configuración privada de errores técnicos fuera de /public
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$rutaLogs = __DIR__ . '/../storage/logs';
if (!is_dir($rutaLogs)) {
    mkdir($rutaLogs, 0775, true);
}
ini_set('error_log', $rutaLogs . '/errores_sistema.log');

use Core\Contenedor;
use Core\Router;
use Core\EnvLoader;
use Core\ConnexionDB;
use Core\RateLimiter;
use Shared\SharedModulo;
use AuthFuncionalidad\AuthModulo;
use UbicacionesFuncionalidad\UbicacionesModulo;
use UsuariosFuncionalidad\UsuariosModulo;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Infraestructura\Servicios\NativaPhpSesionUsuario;

// 2. Cabeceras CORS seguras (Lista blanca de orígenes permitidos)
/*
$origenesPermitidos = [
    'http://app-gzr.test',
    'http://localhost',
    'https://tudominio.com',     // Reemplaza por tu dominio real en producción
    'https://tudominio.com',
];

$origenPeticion = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origenPeticion, $origenesPermitidos, true)) {
    header("Access-Control-Allow-Origin: {$origenPeticion}");
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
*/

try {
    // 3. Autoloader nativo PSR-4 (Compatible con Linux Case-Sensitive)
    spl_autoload_register(function (string $clase) {
        $directorioBase = __DIR__ . '/../';

        if (strpos($clase, 'Core\\') === 0) {
            $rutaRelativa = 'core/' . substr(str_replace('\\', '/', $clase), 5);
            $archivo = $directorioBase . $rutaRelativa . '.php';
        } else {
            $archivo = $directorioBase . 'src/' . str_replace('\\', '/', $clase) . '.php';
        }

        if (file_exists($archivo)) {
            require_once $archivo;
        }
    });


    // 3.1 Cargar variables de entorno desde la raíz del proyecto
    EnvLoader::cargar(__DIR__ . '/../.env');

    // 3.2 Cabeceras CORS seguras desde .env
    $origenesConfigurados = EnvLoader::obtener('CORS_ALLOWED_ORIGINS', '');
    $origenesPermitidos = array_filter(array_map('trim', explode(',', $origenesConfigurados)));

    $urlApp = rtrim(EnvLoader::obtener('APP_URL', ''), '/');
    if ($urlApp !== '' && !in_array($urlApp, $origenesPermitidos, true)) {
        $origenesPermitidos[] = $urlApp;
    }

    $origenPeticion = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origenPeticion !== '' && in_array($origenPeticion, $origenesPermitidos, true)) {
        header("Access-Control-Allow-Origin: {$origenPeticion}");
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // 4. Control de tasa de peticiones por IP (Anti-Bots / Fuerza Bruta)
    $rutaLimpia = '/' . trim((string)(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: ''), '/');
    $rutasCriticas = ['/login', '/registrar-usuario', '/solicitar-recuperacion', '/restablecer-contrasena'];

    $rateLimiter = new RateLimiter(__DIR__ . '/../storage/rate_limit');
    $ipCliente = RateLimiter::obtenerIpCliente();

    $evaluacion = in_array($rutaLimpia, $rutasCriticas, true)
        ? $rateLimiter->verificar($ipCliente, $rutaLimpia, 6, 300)     // Máx. 6 intentos cada 5 min en rutas sensibles
        : $rateLimiter->verificar($ipCliente, 'general', 90, 60);      // Máx. 90 peticiones por minuto en navegación

    if (!$evaluacion['permitido']) {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: ' . $evaluacion['reintentarEn']);
        echo json_encode([
            'success' => false,
            'error'   => 'Has realizado demasiadas solicitudes. Por favor, espera unos minutos antes de volver a intentarlo.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Inicialización del Contenedor y dependencias transversales (Shared)
    $contenedor = new Contenedor();

    $contenedor->bind(PDO::class, function () {
        return ConnexionDB::getInstance();
    });

    $contenedor->bind(
        SesionUsuarioInterface::class,
        NativaPhpSesionUsuario::class
    );

    // 6. Inicialización del Enrutador y carga de Módulos independientes
    $enrutador = new Router();

    /*
    $esEntornoLocal = in_array(
        $_SERVER['SERVER_NAME'] ?? 'localhost',
        ['localhost', '127.0.0.1', 'app-gzr.test'],
        true
    );
    */

    $esEntornoLocal = (EnvLoader::obtener('APP_ENV', 'local') === 'local');

    SharedModulo::registrarDependencias($contenedor);

    UsuariosModulo::registrarDependencias($contenedor, $esEntornoLocal);
    UsuariosModulo::registrarRutas($enrutador);

    AuthModulo::registrarDependencias($contenedor);
    AuthModulo::registrarRutas($enrutador);

    UbicacionesModulo::registrarDependencias($contenedor);
    UbicacionesModulo::registrarRutas($enrutador);

    

    // 7. Si el navegador solicita la raíz '/', servir la Landing Page HTML
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($rutaLimpia === '/' || $rutaLimpia === '/index.php')) {
        $archivoHtml = __DIR__ . '/index.html';
        if (file_exists($archivoHtml)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($archivoHtml);
            exit;
        }
    }

    // 8. Despacho de la petición API hacia los módulos
    $enrutador->dispatch(
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REQUEST_URI'],
        $contenedor
    );

} catch (\Throwable $e) {
    error_log('Error crítico en arranque index.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Error crítico en el arranque del servidor.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
