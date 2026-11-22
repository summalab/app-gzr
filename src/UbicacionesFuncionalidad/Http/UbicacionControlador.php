<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Http;

use InvalidArgumentException;
use Throwable;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Http\ManejadorGlobalExcepciones;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\CrearUbicacionCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\EliminarUbicacionCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\ListarUbicacionesPublicasCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\ListarUbicacionesUsuarioCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\DTOs\CrearUbicacionEntradaDTO;
use UbicacionesFuncionalidad\Aplicacion\DTOs\EliminarUbicacionEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;

class UbicacionControlador
{
    public function __construct(
        private CrearUbicacionCasoDeUso $mCrearUbicacionCasoDeUso,
        private ListarUbicacionesPublicasCasoDeUso $mListarPublicasCasoDeUso,
        private ListarUbicacionesUsuarioCasoDeUso $mListarUsuarioCasoDeUso,
        private EliminarUbicacionCasoDeUso $mEliminarUbicacionCasoDeUso,
        private SesionUsuarioInterface $mSesionUsuario,
        private ManejadorGlobalExcepciones $mManejadorExcepciones
    ) {}

    public function listarPublicas(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere GET.']);
            return;
        }

        try {
            $ubicaciones = $this->mListarPublicasCasoDeUso->execute();

            $this->responderJson(200, [
                'success'     => true,
                'ubicaciones' => $ubicaciones
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function listarPropias(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido. Se requiere GET.']);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => 'No autorizado. Debe iniciar sesión.']);
                return;
            }

            $ubicaciones = $this->mListarUsuarioCasoDeUso->execute($idUsuarioSesion);

            $this->responderJson(200, [
                'success'     => true,
                'ubicaciones' => $ubicaciones
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function crear(): void
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

            $titulo      = isset($_POST['titulo']) ? (string)$_POST['titulo'] : '';
            $descripcion = isset($_POST['descripcion']) ? (string)$_POST['descripcion'] : null;
            $latitudRaw  = $_POST['latitud'] ?? null;
            $longitudRaw = $_POST['longitud'] ?? null;

            if (trim($titulo) === '' || !is_numeric($latitudRaw) || !is_numeric($longitudRaw)) {
                $this->responderJson(400, [
                    'success' => false,
                    'error'   => 'El título, la latitud y la longitud son obligatorios y deben tener formato válido.'
                ]);
                return;
            }

            $dto = new CrearUbicacionEntradaDTO(
                id_usuario: $idUsuarioSesion,
                titulo: $titulo,
                descripcion: $descripcion,
                latitud: (float)$latitudRaw,
                longitud: (float)$longitudRaw
            );

            $ubicacionCreada = $this->mCrearUbicacionCasoDeUso->execute($dto);

            $this->responderJson(201, [
                'success'   => true,
                'message'   => 'Ubicación guardada correctamente.',
                'ubicacion' => $ubicacionCreada
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function eliminar(): void
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

            $idUbicacionRaw = $_POST['id_ubicacion'] ?? null;
            if (!is_numeric($idUbicacionRaw) || (int)$idUbicacionRaw <= 0) {
                $this->responderJson(400, ['success' => false, 'error' => 'El identificador de la ubicación no es válido.']);
                return;
            }

            $dto = new EliminarUbicacionEntradaDTO((int)$idUbicacionRaw, $idUsuarioSesion);
            $this->mEliminarUbicacionCasoDeUso->execute($dto);

            $this->responderJson(200, [
                'success' => true,
                'message' => 'Ubicación eliminada correctamente.'
            ]);
        } catch (Throwable $e) {
            //$this->manejarExcepcion($e);
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    private function responderJson(int $codigoHttp, array $datos): void
    {
        http_response_code($codigoHttp);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
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

        error_log('Error crítico en UbicacionControlador: ' . $e->getMessage());
        $this->responderJson(500, [
            'success' => false,
            'error'   => 'Ocurrió un error interno en el servidor. Inténtelo más tarde.'
        ]);
    }
    */
}
