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
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

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
        private ManejadorGlobalExcepciones $mManejadorExcepciones,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function registrar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $apodo      = isset($_POST['apodo_usuario']) ? (string)$_POST['apodo_usuario'] : '';
            $correo     = isset($_POST['correo_usuario']) ? (string)$_POST['correo_usuario'] : '';
            $contrasena = isset($_POST['contrasena_hash_usuario']) ? (string)$_POST['contrasena_hash_usuario'] : '';

            if (empty($apodo) || empty($correo) || empty($contrasena)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.campos_registro_obligatorios')]);
                return;
            }

            $inputDto = new RegistrarUsuarioEntradaDTO($apodo, $correo, $contrasena);
            $this->mRegistrarUsuarioCasoDeUso->execute($inputDto);

            $this->responderJson(201, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.registro_exito')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function verificar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $token = isset($_GET['token']) ? (string)$_GET['token'] : '';

            if (empty($token)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.token_verificacion_obligatorio')]);
                return;
            }

            $this->mVerificacionTokenCasoDeUso->execute($token);

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.verificacion_exito')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function actualizarApodo(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => $this->mensajes->obtener('sistema.no_autorizado')]);
                return;
            }

            $nuevoApodo = isset($_POST['nuevo_apodo']) ? (string)$_POST['nuevo_apodo'] : '';
            if (empty($nuevoApodo)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.nuevo_apodo_obligatorio')]);
                return;
            }

            $inputDto = new ActualizarApodoEntradaDTO($idUsuarioSesion, $nuevoApodo);
            $this->mActualizarApodoCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.apodo_actualizado')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function actualizarCorreo(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => $this->mensajes->obtener('sistema.no_autorizado')]);
                return;
            }

            $nuevoCorreo = isset($_POST['nuevo_correo']) ? (string)$_POST['nuevo_correo'] : '';
            if (empty($nuevoCorreo)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.nuevo_correo_obligatorio')]);
                return;
            }

            $inputDto = new ActualizarCorreoEntradaDTO($idUsuarioSesion, $nuevoCorreo);
            $this->mActualizarCorreoCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.correo_actualizado')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function cambiarContrasena(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => $this->mensajes->obtener('sistema.no_autorizado')]);
                return;
            }

            $contrasenaActual = isset($_POST['contrasena_actual']) ? (string)$_POST['contrasena_actual'] : '';
            $contrasenaNueva  = isset($_POST['contrasena_nueva']) ? (string)$_POST['contrasena_nueva'] : '';

            if (empty($contrasenaActual) || empty($contrasenaNueva)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.contrasena_obligatoria')]);
                return;
            }

            $inputDto = new CambiarContrasenaEntradaDTO($idUsuarioSesion, $contrasenaActual, $contrasenaNueva);
            $this->mCambiarContrasenaCasoDeUso->execute($inputDto);

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.contrasena_actualizada')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function solicitarRecuperacion(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $correo = isset($_POST['correo_usuario']) ? (string)$_POST['correo_usuario'] : '';
            if (empty($correo)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.correo_obligatorio')]);
                return;
            }

            $this->mSolicitarRecuperacionCasoDeUso->execute($correo);

            // Mensaje genérico por seguridad anti-enumeración
            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.recuperacion_exito')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function restablecerContrasena(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $token           = isset($_POST['token']) ? (string)$_POST['token'] : '';
            $nuevaContrasena = isset($_POST['nueva_contrasena']) ? (string)$_POST['nueva_contrasena'] : '';

            if (empty($token) || empty($nuevaContrasena)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.restablecimiento_campos_obligatorios')]);
                return;
            }
            $inputDto = new RestablecerContrasenaEntradaDTO($token, $nuevaContrasena);
            $this->mRestablecerContrasenaCasoDeUso->execute($inputDto);
            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.contrasena_restablecida')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }
    
    public function eliminarCuenta(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }
        try {
            
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();

            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => $this->mensajes->obtener('sistema.no_autorizado')]);
                return;
            }

            $contrasenaConfirmacion = isset($_POST['contrasena_confirmacion']) ? (string)$_POST['contrasena_confirmacion'] : '';
            
            if (empty($contrasenaConfirmacion)) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('usuario.confirmacion_cuenta_obligatoria')]);
                return;
            }
            
            $this->mEliminarCuentaCasoDeUso->execute($idUsuarioSesion, $contrasenaConfirmacion);
            
            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('sistema.cuenta_eliminada')
            ]);
            
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    private function responderJson(int $codigoHttp, array $datos): void
    {
        http_response_code($codigoHttp);
        echo json_encode($datos);
    }

}