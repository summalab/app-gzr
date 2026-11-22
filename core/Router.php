<?php

declare(strict_types=1);

namespace Core;

use Exception;

class Router
{
    /**
     * Catálogo interno donde se registran las rutas del sistema.
     * @var array<int, array{method: string, path: string, controller: string, action: string}>
     */
    private array $routes = [];

    /**
     * Registra una nueva ruta mapeada en el sistema.
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE).
     * @param string $path URL limpia del endpoint (ej: '/register').
     * @param string $controller Nombre completo de la clase del controlador (Namespace completo).
     * @param string $action Método que se ejecutará dentro del controlador.
     * @return void
     */
    public function addRoute(string $method, string $path, string $controller, string $action): void
    {
        $this->routes[] = [
            'method'     => strtoupper(trim($method)),
            'path'       => '/' . trim($path, '/'), // Normaliza barras (ej: 'register/' -> '/register')
            'controller' => $controller,
            'action'     => $action
        ];
    }

    /**
     * Analiza la petición HTTP entrante, busca una coincidencia y despacha el flujo.
     * 
     * @param string $requestMethod Método enviado por el servidor ($_SERVER['REQUEST_METHOD']).
     * @param string $requestUri URI enviada por el servidor ($_SERVER['REQUEST_URI']).
     * @param Contenedor $contenedor Instancia de nuestro contenedor DI nativo.
     * @return void
     */
    public function dispatch(string $requestMethod, string $requestUri, Contenedor $contenedor): void
    {
        // 1. Limpiar y normalizar la URL (Elimina parámetros query string: /register?error=1 -> /register)
        $parsedUrl = parse_url($requestUri, PHP_URL_PATH);
        $cleanPath = '/' . trim($parsedUrl ?? '', '/');
        $method    = strtoupper($requestMethod);

        // 2. Buscar si la ruta solicitada coincide con el catálogo registrado
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $cleanPath) {
                
                try {
                    // MÁGIA DEL AUTOWIRING: Le pedimos al contenedor que resuelva e instancie
                    // el controlador con toda su inmensa cadena de dependencias en un solo clic.
                    $controllerInstance = $contenedor->get($route['controller']);
                    $action = $route['action'];

                    // Validación defensiva: verificar si el método/acción existe en el controlador instanciado
                    if (!method_exists($controllerInstance, $action)) {
                        throw new Exception("Error de Enrutamiento: El método [{$action}] no existe en [{$route['controller']}].");
                    }

                    // Ejecutamos la acción del controlador
                    $controllerInstance->$action();
                    return;

                } catch (\Throwable $e) {
                    // Aislamiento Defensivo: Si ocurre un error catastrófico dentro de un módulo,
                    // el enrutador captura la falla de forma segura para no tumbar por completo el servidor web.
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error'   => 'Error en la ejecución del módulo solicitado.',
                        // ini-> details muestra los detalles de los errores.
                        //'details' => $e->getMessage() // Puedes ocultar 'details' en producción por seguridad
                        // <-fin
                    ]);
                        
                    // ini-> errores se escriben en log de errores.
                    error_log($e->getMessage(),0);
                    // <-fin
                    return;
                }
            }
        }

        // 3. Si recorrió todo el ciclo y ninguna combinación de método/ruta coincidió
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error'   => "Ruta no encontrada: [{$method}] {$cleanPath}"
        ]);
    }
}
