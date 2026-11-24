const PRECISION_MAXIMA_METROS = 39;

export class GeolocalizadorNavegador {
    constructor(geolocation = globalThis.navigator?.geolocation) {
        this.geolocation = geolocation;
    }

    obtenerUbicacion() {
        return new Promise((resolve, reject) => {
            if (!this.geolocation) {
                reject(new Error('Tu navegador o dispositivo no soporta geolocalización GPS.'));
                return;
            }

            this.geolocation.getCurrentPosition(
                (posicion) => {
                    const precision = posicion.coords.accuracy;
                    if (!Number.isFinite(precision) || precision > PRECISION_MAXIMA_METROS) {
                        const precisionTexto = Number.isFinite(precision)
                            ? `${Math.round(precision)} m`
                            : 'desconocida';
                        reject(new Error(
                            `La precisión GPS es insuficiente (${precisionTexto}). Activa la ubicación precisa e inténtalo de nuevo.`
                        ));
                        return;
                    }

                    resolve({
                        latitud: Number(posicion.coords.latitude.toFixed(6)),
                        longitud: Number(posicion.coords.longitude.toFixed(6))
                    });
                },
                (error) => reject(new Error(this.obtenerMensajeError(error))),
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
    }

    obtenerMensajeError(error) {
        if (error.code === error.PERMISSION_DENIED) {
            return 'Permiso de ubicación denegado. Activa el GPS y permite el acceso en tu navegador.';
        }
        if (error.code === error.TIMEOUT) {
            return 'Se agotó el tiempo esperando señal GPS. Intenta en un espacio más abierto.';
        }
        return 'No se pudo obtener tu ubicación GPS.';
    }
}
