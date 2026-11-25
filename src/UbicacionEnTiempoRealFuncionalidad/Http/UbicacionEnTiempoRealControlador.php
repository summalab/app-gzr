<?php

declare(strict_types=1);

namespace UbicacionEnTiempoRealFuncionalidad\Http;

use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Http\ManejadorGlobalExcepciones;
use UbicacionEnTiempoRealFuncionalidad\Aplicacion\CasosDeUso\ActualizarConfiguracionUbicacionTiempoReal;
use UbicacionEnTiempoRealFuncionalidad\Aplicacion\CasosDeUso\ObtenerConfiguracionUbicacionTiempoReal;
use Throwable;

final class UbicacionEnTiempoRealControlador
{
    public function __construct(
        private ObtenerConfiguracionUbicacionTiempoReal $obtenerConfiguracion,
        private ActualizarConfiguracionUbicacionTiempoReal $actualizarConfiguracion,
        private AutorizadorInterface $autorizador,
        private SesionUsuarioInterface $sesionUsuario,
        private ManejadorGlobalExcepciones $manejadorExcepciones
    ) {}

    public function obtenerConfiguracion(): void
    {
        // La política se consulta al recibir este endpoint; el backend no programa sondeos periódicos.
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido.']);
            return;
        }

        try {
            $this->responderJson(200, [
                'success' => true,
                'configuracion' => $this->obtenerConfiguracion->execute(),
            ]);
        } catch (Throwable $error) {
            $this->manejadorExcepciones->manejar($error, static::class);
        }
    }

    public function guardarConfiguracion(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(405, ['success' => false, 'error' => 'Método no permitido.']);
            return;
        }

        try {
            $this->autorizador->exigirPermiso('configuracion.ubicacion_tiempo_real');
            $idAdministrador = $this->sesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idAdministrador === null) {
                $this->responderJson(401, ['success' => false, 'error' => 'Debes iniciar sesión como administrador.']);
                return;
            }

            $datos = json_decode((string)file_get_contents('php://input'), true);
            if (!is_array($datos)
                || !is_bool($datos['habilitada'] ?? null)
                || !is_string($datos['rolPermitido'] ?? null)) {
                $this->responderJson(400, [
                    'success' => false,
                    'error' => 'La configuración enviada no tiene un formato válido.',
                ]);
                return;
            }

            $configuracion = $this->actualizarConfiguracion->execute(
                $datos['habilitada'],
                $datos['rolPermitido'],
                $idAdministrador
            );

            $this->responderJson(200, [
                'success' => true,
                'message' => 'La configuración de ubicación en tiempo real se guardó correctamente.',
                'configuracion' => $configuracion,
            ]);
        } catch (Throwable $error) {
            $this->manejadorExcepciones->manejar($error, static::class);
        }
    }

    private function responderJson(int $codigoHttp, array $datos): void
    {
        http_response_code($codigoHttp);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
}
