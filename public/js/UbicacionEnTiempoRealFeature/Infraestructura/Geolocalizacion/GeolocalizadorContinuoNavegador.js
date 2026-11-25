export class GeolocalizadorContinuoNavegador {
    constructor(geolocation = globalThis.navigator?.geolocation) {
        this.geolocation = geolocation;
    }

    observar(alRecibir, alOcurrirError) {
        if (!this.geolocation) {
            throw new Error('Este navegador no ofrece acceso a la ubicación del dispositivo.');
        }

        return this.geolocation.watchPosition(
            (posicion) => {
                const latitud = Number(posicion.coords.latitude);
                const longitud = Number(posicion.coords.longitude);
                if (!Number.isFinite(latitud) || latitud < -90 || latitud > 90
                    || !Number.isFinite(longitud) || longitud < -180 || longitud > 180) {
                    alOcurrirError(new Error('El GPS devolvió coordenadas no válidas.'));
                    return;
                }

                alRecibir({
                    latitud: Number(latitud.toFixed(6)),
                    longitud: Number(longitud.toFixed(6)),
                    precisionMetros: posicion.coords.accuracy
                });
            },
            (error) => alOcurrirError(new Error(this.obtenerMensajeError(error))),
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    dejarDeObservar(idSeguimiento) {
        this.geolocation?.clearWatch(idSeguimiento);
    }

    obtenerMensajeError(error) {
        if (error.code === 1) {
            return 'No se autorizó el GPS. Permite el acceso a la ubicación en los ajustes de tu navegador e inténtalo de nuevo.';
        }
        if (error.code === 3) {
            return 'No se obtuvo una lectura GPS a tiempo. Inténtalo en un lugar con mejor señal.';
        }
        return 'No se pudo mantener la transmisión de ubicación GPS.';
    }
}
