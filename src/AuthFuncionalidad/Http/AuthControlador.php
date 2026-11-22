<?php

declare(strict_types=1);

namespace AuthFuncionalidad\Http;

use InvalidArgumentException;
use Throwable;
use AuthFuncionalidad\Aplicacion\DTOs\LoginEntradaDTO;
use AuthFuncionalidad\Aplicacion\CasosDeUso\LoginCasoDeUso;
use AuthFuncionalidad\Aplicacion\CasosDeUso\LogoutCasoDeUso;
use AuthFuncionalidad\Aplicacion\CasosDeUso\VerificarSesionCasoDeUso;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

class AuthControlador
{
    public function __construct(
        private LoginCasoDeUso $mLoginCasoDeUso,
        private LogoutCasoDeUso $mLogoutCasoDeUso,
        private VerificarSesionCasoDeUso $mVerificarSesionCasoDeUso,
        private ManejadorGlobalExcepciones $mManejadorExcepciones
    ) {}

    public function login(): void
    {
        // Valida que el método sea POST, extrae credenciales, 
        // ejecuta el caso de uso y responde en formato JSON.
        // (Ver la implementación completa en el código del controlador).
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, [
                'success' => false,
                'error'   => 'Método no permitido. Se requiere POST.'
            ]);
            return;
        }

        try {
            $correo     = isset($_POST['correo_usuario']) ? (string)$_POST['correo_usuario'] : '';
            $contrasena = isset($_POST['contrasena']) ? (string)$_POST['contrasena'] : '';

            if (empty($correo) || empty($contrasena)) {
                $this->responderJson(400, [
                    'success' => false,
                    'error'   => 'El correo electrónico y la contraseña son obligatorios.'
                ]);
                return;
            }

            $inputDto = new LoginEntradaDTO($correo, $contrasena);
            $datosUsuario = $this->mLoginCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Sesión iniciada correctamente.',
                'usuario' => $datosUsuario
            ]);

        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function logout(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, [
                'success' => false,
                'error'   => 'Método no permitido. Se requiere POST.'
            ]);
            return;
        }

        try {
            $this->mLogoutCasoDeUso->execute();

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Sesión cerrada correctamente.'
            ]);

        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }


    public function verificarSesion(): void
    {
        header('Content-Type: application/json');

        // 1. Validar que sea una petición de lectura GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, [
                'success' => false,
                'error'   => 'Método no permitido. Se requiere GET.'
            ]);
            return;
        }

        try {
            // 2. Consultar al caso de uso (él revisa $_SESSION y valida en MySQL)
            $estadoSesion = $this->mVerificarSesionCasoDeUso->execute();

            // 3. Responder siempre 200 OK con el estado actual
            $this->responderJson(200, [
                'success'     => true,
                'autenticado' => $estadoSesion['autenticado'],
                'usuario'     => $estadoSesion['usuario']
            ]);

        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }


    private function responderJson(int $codigoHttp, array $datos): void
    {
        // Configura el código HTTP y emite la respuesta codificada en JSON.
        http_response_code($codigoHttp); 
        echo json_encode($datos);
    }

    /*
    private function manejarExcepcion(Throwable $e): void
    {
        // Maneja excepciones de negocio/validación o registra errores críticos en el servidor.
        // 1. Errores seguros de negocio o validación de formato (Controlados)
        if ($e instanceof ReglaNegocioExcepcion || $e instanceof InvalidArgumentException) {
            
            // Respetamos códigos específicos (ej: 401 Credenciales inválidas, 403 Cuenta sin verificar, 404 No encontrado)
            // Si la excepción no trae un código HTTP 4xx válido, usamos 422 (Unprocessable Entity) por defecto
            $codigoHttp = ($e->getCode() >= 400 && $e->getCode() < 500) 
                ? (int)$e->getCode() 
                : 422;

            $this->responderJson($codigoHttp, [
                'success' => false,
                'error'   => $e->getMessage()
            ]);
            return;
        }

        // 2. Errores críticos de infraestructura o sistema (No controlados: PDOException, TypeError, etc.)
        // Guardamos el detalle técnico real en el log privado del servidor (storage/logs/errores_sistema.log)
        error_log('Error crítico en AuthControlador: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

        // Devolvemos un mensaje genérico al navegador sin exponer tablas, consultas SQL ni rutas internas
        $this->responderJson(500, [
            'success' => false,
            'error'   => 'Ocurrió un error interno en el servidor. Inténtelo más tarde.'
        ]);
    }
    */
}
