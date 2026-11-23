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
use Shared\Http\ManejadorGlobalExcepciones;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;


class AuthControlador
{
    public function __construct(
        private LoginCasoDeUso $mLoginCasoDeUso,
        private LogoutCasoDeUso $mLogoutCasoDeUso,
        private VerificarSesionCasoDeUso $mVerificarSesionCasoDeUso,
        private ManejadorGlobalExcepciones $mManejadorExcepciones,
        private CatalogoMensajesInterface $mensajes
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
                'error'   => $this->mensajes->obtener('sistema.metodo_no_permitido')
            ]);
            return;
        }

        try {
            $correo     = isset($_POST['correo_usuario']) ? (string)$_POST['correo_usuario'] : '';
            $contrasena = isset($_POST['contrasena']) ? (string)$_POST['contrasena'] : '';

            if (empty($correo) || empty($contrasena)) {
                $this->responderJson(400, [
                    'success' => false,
                    'error'   => $this->mensajes->obtener('usuario.campos_login_obligatorios')
                ]);
                return;
            }

            $inputDto = new LoginEntradaDTO($correo, $contrasena);
            $datosUsuario = $this->mLoginCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.sesion_exito'),
                'usuario' => $datosUsuario
            ]);

        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, 'AuthControlador.login()');
        }
    }

    public function logout(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, [
                'success' => false,
                'error'   => $this->mensajes->obtener('sistema.metodo_no_permitido')
            ]);
            return;
        }

        try {
            $this->mLogoutCasoDeUso->execute();

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.logout_exito')
            ]);

        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, 'AuthControlador.logout()');
        }
    }


    public function verificarSesion(): void
    {
        header('Content-Type: application/json');

        // 1. Validar que sea una petición de lectura GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, [
                'success' => false,
                'error'   => $this->mensajes->obtener('sistema.metodo_no_permitido')
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
            $this->mManejadorExcepciones->manejar($e, 'AuthControlador.verificarSesion()');
        }
    }


    private function responderJson(int $codigoHttp, array $datos): void
    {
        // Configura el código HTTP y emite la respuesta codificada en JSON.
        http_response_code($codigoHttp); 
        echo json_encode($datos);
    }

}
