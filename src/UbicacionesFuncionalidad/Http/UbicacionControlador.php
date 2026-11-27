<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Http;

use InvalidArgumentException;
use Throwable;
use Core\EnvLoader;
use Shared\Dominio\Servicios\SesionUsuarioInterface;
use Shared\Http\ManejadorGlobalExcepciones;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\CrearUbicacionCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\EliminarUbicacionCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\ListarUbicacionesPublicasCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\CasosDeUso\ListarUbicacionesUsuarioCasoDeUso;
use UbicacionesFuncionalidad\Aplicacion\DTOs\CrearUbicacionEntradaDTO;
use UbicacionesFuncionalidad\Aplicacion\DTOs\EliminarUbicacionEntradaDTO;
use UsuariosFuncionalidad\Dominio\Excepciones\ReglaNegocioExcepcion;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class UbicacionControlador
{
    public function __construct(
        private CrearUbicacionCasoDeUso $mCrearUbicacionCasoDeUso,
        private ListarUbicacionesPublicasCasoDeUso $mListarPublicasCasoDeUso,
        private ListarUbicacionesUsuarioCasoDeUso $mListarUsuarioCasoDeUso,
        private EliminarUbicacionCasoDeUso $mEliminarUbicacionCasoDeUso,
        private SesionUsuarioInterface $mSesionUsuario,
        private ManejadorGlobalExcepciones $mManejadorExcepciones,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function listarPublicas(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $ubicaciones = $this->mListarPublicasCasoDeUso->execute();

            $this->responderJson(200, [
                'success'     => true,
                'ubicaciones' => $ubicaciones,
                'geocerca'    => [
                    'lat'     => (float)EnvLoader::obtener('GEO_CENTRO_LAT', '4.8133'),
                    'lng'     => (float)EnvLoader::obtener('GEO_CENTRO_LNG', '-75.6961'),
                    'radioKm' => (float)EnvLoader::obtener('GEO_RADIO_KM', '40'),
                    'modo'    => strtolower(EnvLoader::obtener('GEO_MODO', 'circular')),
                ],
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function listarPropias(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->responderJson(405, ['success' => false, 'error' => $this->mensajes->obtener('sistema.metodo_no_permitido')]);
            return;
        }

        try {
            $idUsuarioSesion = $this->mSesionUsuario->obtenerIdUsuarioAutenticado();
            if ($idUsuarioSesion === null) {
                $this->responderJson(401, ['success' => false, 'error' => $this->mensajes->obtener('sistema.no_autorizado')]);
                return;
            }

            $ubicaciones = $this->mListarUsuarioCasoDeUso->execute($idUsuarioSesion);

            $this->responderJson(200, [
                'success'     => true,
                'ubicaciones' => $ubicaciones
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function crear(): void
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

            $titulo      = isset($_POST['titulo']) ? (string)$_POST['titulo'] : '';
            $descripcion = isset($_POST['descripcion']) ? (string)$_POST['descripcion'] : null;
            $latitudRaw  = $_POST['latitud'] ?? null;
            $longitudRaw = $_POST['longitud'] ?? null;
            $tipoInfraestructura = trim((string)($_POST['tipo_infraestructura'] ?? ''));
            $estadoInfraestructura = trim((string)($_POST['estado_infraestructura'] ?? ''));
            $riesgosDirectos = trim((string)($_POST['riesgos_directos'] ?? ''));
            $riesgosIndirectos = trim((string)($_POST['riesgos_indirectos'] ?? ''));
            $actividadesDelictivas = trim((string)($_POST['actividades_delictivas'] ?? ''));
            $condicionesSociales = trim((string)($_POST['condiciones_sociales'] ?? ''));

            if (
                trim($titulo) === ''
                || !is_numeric($latitudRaw)
                || !is_numeric($longitudRaw)
                || $tipoInfraestructura === ''
                || $estadoInfraestructura === ''
                || $riesgosDirectos === ''
                || $riesgosIndirectos === ''
                || $actividadesDelictivas === ''
                || $condicionesSociales === ''
            ) {
                $this->responderJson(400, [
                    'success' => false,
                    'error'   => $this->mensajes->obtener('ubicacion.campos_obligatorios')
                ]);
                return;
            }

            $dto = new CrearUbicacionEntradaDTO(
                id_usuario: $idUsuarioSesion,
                titulo: $titulo,
                descripcion: $descripcion,
                latitud: (float)$latitudRaw,
                longitud: (float)$longitudRaw,
                tipo_infraestructura: $tipoInfraestructura,
                estado_infraestructura: $estadoInfraestructura,
                riesgos_directos: $riesgosDirectos,
                riesgos_indirectos: $riesgosIndirectos,
                actividades_delictivas: $actividadesDelictivas,
                condiciones_sociales: $condicionesSociales
            );

            $ubicacionCreada = $this->mCrearUbicacionCasoDeUso->execute($dto);

            $this->responderJson(201, [
                'success'   => true,
                'message'   => $this->mensajes->obtener('ubicacion.creada_exito'),
                'ubicacion' => $ubicacionCreada
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    public function eliminar(): void
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

            $idUbicacionRaw = $_POST['id_ubicacion'] ?? null;
            if (!is_numeric($idUbicacionRaw) || (int)$idUbicacionRaw <= 0) {
                $this->responderJson(400, ['success' => false, 'error' => $this->mensajes->obtener('ubicacion.identificador_invalido')]);
                return;
            }

            $dto = new EliminarUbicacionEntradaDTO((int)$idUbicacionRaw, $idUsuarioSesion);
            $this->mEliminarUbicacionCasoDeUso->execute($dto);

            $this->responderJson(200, [
                'success' => true,
                'message' => $this->mensajes->obtener('ubicacion.eliminada_exito')
            ]);
        } catch (Throwable $e) {
            $this->mManejadorExcepciones->manejar($e, static::class);
        }
    }

    private function responderJson(int $codigoHttp, array $datos): void
    {
        http_response_code($codigoHttp);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }
}
