<?php

declare(strict_types=1);

namespace UsuariosFuncionalidad\Http;

use InvalidArgumentException;
use Throwable;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Http\ManejadorGlobalExcepciones;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use UsuariosFuncionalidad\Aplicacion\DTOs\RegistrarUsuarioEntradaDTO;
use UsuariosFuncionalidad\Aplicacion\DTOs\ActualizarApodoEntradaDTO;
use UsuariosFuncionalidad\Aplicacion\DTOs\ActualizarCorreoEntradaDTO;
use UsuariosFuncionalidad\Aplicacion\DTOs\CambiarContrasenaEntradaDTO;
use UsuariosFuncionalidad\Aplicacion\DTOs\RestablecerContrasenaEntradaDTO;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\RegistrarUsuarioCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\VerificacionTokenConfirmacionUsuarioCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\ActualizarApodoCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\ActualizarCorreoCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\CambiarContrasenaCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\SolicitarRecuperacionContrasenaCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\RestablecerContrasenaCasoDeUso;
use UsuariosFuncionalidad\Aplicacion\CasosDeUso\EliminarCuentaUsuarioCasoDeUso;

class UsuarioControlador
{
    public function __construct(
        private RegistrarUsuarioCasoDeUso $mRegistrarUsuarioCasoDeUso,
        private VerificacionTokenConfirmacionUsuarioCasoDeUso $mVerificacionTokenCasoDeUso,
        private ActualizarApodoCasoDeUso $mActualizarApodoCasoDeUso,
        private ActualizarCorreoCasoDeUso $mActualizarCorreoCasoDeUso,
        private CambiarContrasenaCasoDeUso $mCambiarContrasenaCasoDeUso,
        private SolicitarRecuperacionContrasenaCasoDeUso $mSolicitarRecuperacionCasoDeUso,
        private RestablecerContrasenaCasoDeUso $mRestablecerContrasenaCasoDeUso,
        private EliminarCuentaUsuarioCasoDeUso $mEliminarCuentaCasoDeUso,
        private SesionUsuarioInterface $mSesionUsuario,
        private ManejadorGlobalExcepciones $mManejadorExcepciones
    ) {}

    public function registrar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        try {
            $apodo      = isset($_POST['apodo_usuario']) ? (string)$_POST['apodo_usuario'] : '';
            $correo     = isset($_POST['correo_usuario']) ? (string)$_POST['correo_usuario'] : '';
            $contrasena = isset($_POST['contrasena_hash_usuario']) ? (string)$_POST['contrasena_hash_usuario'] : '';

            if (empty($apodo) || empty($correo) || empty($contrasena)) {
                $this->responderJson(400, ['success' => false, 'error' => 'El apodo, el correo electrónico y la contraseña son obligatorios.']);
                return;
            }

            $inputDto = new RegistrarUsuarioEntradaDTO($apodo, $correo, $contrasena);
            $this->mRegistrarUsuarioCasoDeUso->execute($inputDto);

            $this->responderJson(201, [
                'success' => true,
                'message' => 'Usuario registrado exitosamente. Revisa tu correo para activar la cuenta.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function verificar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere GET.']);
            return;
        }

        try {
            $token = isset($_GET['token']) ? (string)$_GET['token'] : '';

            if (empty($token)) {
                $this->responderJson(400, ['success' => false, 'error' => 'Falta el token de verificación.']);
                return;
            }

            $this->mVerificacionTokenCasoDeUso->execute($token);

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Cuenta de correo verificada con éxito. Ya puede iniciar sesión.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function actualizarApodo(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => 'No autorizado. Debe iniciar sesión.']);
                return;
            }

            $nuevoApodo = isset($_POST['nuevo_apodo']) ? (string)$_POST['nuevo_apodo'] : '';
            if (empty($nuevoApodo)) {
                $this->responderJson(400, ['success' => false, 'error' => 'El nuevo apodo es obligatorio.']);
                return;
            }

            $inputDto = new ActualizarApodoEntradaDTO($idUsuarioSesion, $nuevoApodo);
            $this->mActualizarApodoCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Apodo actualizado correctamente.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function actualizarCorreo(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => 'No autorizado. Debe iniciar sesión.']);
                return;
            }

            $nuevoCorreo = isset($_POST['nuevo_correo']) ? (string)$_POST['nuevo_correo'] : '';
            if (empty($nuevoCorreo)) {
                $this->responderJson(400, ['success' => false, 'error' => 'El nuevo correo electrónico es obligatorio.']);
                return;
            }

            $inputDto = new ActualizarCorreoEntradaDTO($idUsuarioSesion, $nuevoCorreo);
            $this->mActualizarCorreoCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Correo actualizado. Revisa tu bandeja para verificar la nueva dirección.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function cambiarContrasena(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => 'No autorizado. Debe iniciar sesión.']);
                return;
            }

            $contrasenaActual = isset($_POST['contrasena_actual']) ? (string)$_POST['contrasena_actual'] : '';
            $contrasenaNueva  = isset($_POST['contrasena_nueva']) ? (string)$_POST['contrasena_nueva'] : '';

            if (empty($contrasenaActual) || empty($contrasenaNueva)) {
                $this->responderJson(400, ['success' => false, 'error' => 'Debe ingresar la contraseña actual y la nueva.']);
                return;
            }

            $inputDto = new CambiarContrasenaEntradaDTO($idUsuarioSesion, $contrasenaActual, $contrasenaNueva);
            $this->mCambiarContrasenaCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function solicitarRecuperacion(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        try {
            $correo = isset($_POST['correo_usuario']) ? (string)$_POST['correo_usuario'] : '';
            if (empty($correo)) {
                $this->responderJson(400, ['success' => false, 'error' => 'Debe ingresar su correo electrónico.']);
                return;
            }

            $this->mSolicitarRecuperacionCasoDeUso->execute($correo);

            // Mensaje genérico por seguridad anti-enumeración
            $this->responderJson(200, [
                'success' => true,
                'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function restablecerContrasena(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        try {
            $token           = isset($_POST['token']) ? (string)$_POST['token'] : '';
            $nuevaContrasena = isset($_POST['nueva_contrasena']) ? (string)$_POST['nueva_contrasena'] : '';

            if (empty($token) || empty($nuevaContrasena)) {
                $this->responderJson(400, ['success' => false, 'error' => 'El token y la nueva contraseña son obligatorios.']);
                return;
            }
            $inputDto = new RestablecerContrasenaEntradaDTO($token, $nuevaContrasena);
            $this->mRestablecerContrasenaCasoDeUso->execute($inputDto);
            $this->responderJson(200, [
                'success' => true,
                'message' => 'Contraseña restablecida con éxito. Ya puede iniciar sesión con su nueva clave.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }
    
    public function eliminarCuenta(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere POST.']);
            return;
        }
        try {
            
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();

            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => 'No autorizado. Debe iniciar sesión.']);
                return;
            }

            $contrasenaConfirmacion = isset($_POST['contrasena_confirmacion']) ? (string)$_POST['contrasena_confirmacion'] : '';
            
            if (empty($contrasenaConfirmacion)) {
                $this->responderJson(400, ['success' => false, 'error' => 'Debe confirmar su contraseña para eliminar la cuenta.']);
                return;
            }
            
            $this->mEliminarCuentaCasoDeUso->execute($idUsuarioSesion, $contrasenaConfirmacion);
            
            $this->responderJson(200, [
                'success' => true,
                'message' => 'Su cuenta ha sido dada de baja correctamente.'
            ]);
            
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    private function responderJson(int $codigoHttp, array $datos): void
    {
        http_response_code($codigoHttp);
        echo json_encode($datos);
    }

    /*          
    private function manejarExcepcion(Throwable $e): void
    {
        if ($e instanceof ReglaNegocioExcepcion || $e instanceof InvalidArgumentException) {
            $codigoHttp = ($e->getCode() >= 400 && $e->getCode() < 500) ? (int)$e->getCode() : 422;
            $this->responderJson($codigoHttp, [
                'success' => false,
                'error'   => $e->getMessage()
            ]);
            return;
        }
        
        error_log('Error crítico en UsuarioControlador: ' . $e->getMessage());
            $this->responderJson(500, [
            'success' => false,
            'error'   => 'Ocurrió un error interno en el servidor. Inténtelo más tarde.'
        ]);
    }
    */
}