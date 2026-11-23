<?php

declare(strict_types=1);

namespace UbicacionesFuncionalidad\Aplicacion\CasosDeUso;

use InvalidArgumentException;
use Core\EnvLoader;
use Throwable;
use UbicacionesFuncionalidad\Aplicacion\DTOs\CrearUbicacionEntradaDTO;
use UbicacionesFuncionalidad\Dominio\Entidades\UbicacionEntidad;
use UbicacionesFuncionalidad\Dominio\Repositorios\UbicacionRepositorioInterface;
use Shared\Dominio\Servicios\AutorizadorInterface;
use Shared\Dominio\Servicios\CatalogoMensajesInterface;

class CrearUbicacionCasoDeUso
{
    public function __construct(
        private UbicacionRepositorioInterface $mUbicacionRepositorio,
        private AutorizadorInterface $mAutorizador,
        private CatalogoMensajesInterface $mensajes
    ) {}

    public function execute(CrearUbicacionEntradaDTO $dto): array
    {
        $this->mAutorizador->exigirPermiso('ubicacion.crear');

        // Validar que el usuario esté autenticado y autorizado
        $tituloLimpio = trim($dto->titulo);
        $descripcionLimpia = $dto->descripcion !== null ? trim($dto->descripcion) : null;

        if ($descripcionLimpia === null || mb_strlen($descripcionLimpia) < 15) {
            $descripcionLimpia = null;
        }

        // 1. Regla de calidad de contenido (evita títulos basura como "111" o "aaa")
        if (mb_strlen($tituloLimpio) < 5 || !preg_match('/[a-zA-ZáéíóúÁÉÍÓÚñÑ]{3,}/', $tituloLimpio)) {
            throw new InvalidArgumentException('El título debe tener al menos 5 caracteres y palabras descriptivas reales.');
        }

        if ($descripcionLimpia === null || mb_strlen($descripcionLimpia) < 15) {
            throw new InvalidArgumentException('La descripción es obligatoria y debe explicar brevemente el lugar (mínimo 15 caracteres).');
        }

        // 2. Regla geográfica: Validar que esté dentro del radio autorizado alrededor de Pereira
        $this->validarDentroDeGeocerca($dto->latitud, $dto->longitud);

        // 3. Regla anti-duplicados: Evitar pines repetidos a menos de 25 metros (0.025 km)
        $this->validarProximidadMinima($dto->latitud, $dto->longitud, 0.025);


        try {
            $ubicacionEntidad = new UbicacionEntidad(
                id_ubicacion: null,
                id_usuario: $dto->id_usuario,
                titulo: $tituloLimpio,
                descripcion: $descripcionLimpia,
                latitud: $dto->latitud,
                longitud: $dto->longitud
            );

            $ubicacionGuardada = $this->mUbicacionRepositorio->guardar($ubicacionEntidad);

            return [
                'id_ubicacion' => $ubicacionGuardada->obtenerId(),
                'titulo'       => $ubicacionGuardada->obtenerTitulo(),
                'descripcion'  => $ubicacionGuardada->obtenerDescripcion(),
                'latitud'      => $ubicacionGuardada->obtenerLatitud(),
                'longitud'     => $ubicacionGuardada->obtenerLongitud(),
            ];

        } catch (InvalidArgumentException $reglaExcepcion) {
            throw $reglaExcepcion;
        } catch (Throwable $falla) {
            error_log('Error crítico en CrearUbicacionCasoDeUso: ' . $falla->getMessage());
            throw new \Exception('No se pudo guardar la ubicación por un problema interno.');
        }
    }

    private function validarDentroDeGeocerca(float $latitud, float $longitud): void
    {
        $modo = strtolower(EnvLoader::obtener('GEO_MODO', 'circular'));

        if ($modo === 'nacional') {
            // Límites geográficos continentales/insulares de Colombia
            $dentroColombia = ($latitud >= -4.23 && $latitud <= 13.50)
                           && ($longitud >= -81.80 && $longitud <= -66.85);

            if (!$dentroColombia) {
                throw new InvalidArgumentException(
                    $this->mensajes->obtener('ubicacion.fuera_de_pais')
                );
            }
            return;
        }

        // Modo circular (regional)
        $centroLat = (float)EnvLoader::obtener('GEO_CENTRO_LAT', '4.8133');
        $centroLng = (float)EnvLoader::obtener('GEO_CENTRO_LNG', '-75.6961');
        $radioMaxKm = (float)EnvLoader::obtener('GEO_RADIO_KM', '40');

        $distanciaKm = $this->calcularDistanciaKm($centroLat, $centroLng, $latitud, $longitud);

        if ($distanciaKm > $radioMaxKm) {
            throw new InvalidArgumentException(
                $this->mensajes->obtener('ubicacion.fuera_de_zona', ['radio' => $radioMaxKm])
            );
        }
    }


    /*
    private function validarProximidadMinima(float $latitud, float $longitud, float $minimoKm): void
    {
        $ubicacionesExistentes = $this->mUbicacionRepositorio->listarTodasActivas();

        foreach ($ubicacionesExistentes as $existente) {
            $latExistente = (float)($existente['latitud'] ?? 0);
            $lngExistente = (float)($existente['longitud'] ?? 0);

            if ($this->calcularDistanciaKm($latExistente, $lngExistente, $latitud, $longitud) < $minimoKm) {
                throw new InvalidArgumentException(
                    'Ya existe una ubicación registrada exactamente en este punto (a menos de 25 metros).'
                );
            }
        }
    }
    */

        private function validarProximidadMinima(float $latitud, float $longitud, float $minimoKm): void
    {
        $ubicacionesExistentes = $this->mUbicacionRepositorio->listarTodasActivas();

        foreach ($ubicacionesExistentes as $existente) {
            $latExistente = $existente->obtenerLatitud();
            $lngExistente = $existente->obtenerLongitud();

            if ($this->calcularDistanciaKm($latExistente, $lngExistente, $latitud, $longitud) < $minimoKm) {
                throw new InvalidArgumentException(
                    'Ya existe una ubicación registrada exactamente en este punto (a menos de 25 metros).'
                );
            }
        }
    }

    /**
     * Calcula la distancia en kilómetros entre dos coordenadas usando la fórmula de Haversine.
     */
    private function calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        return 6371 * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
