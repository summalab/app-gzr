import { tienePrecisionSuficiente } from '../Dominio/PrecisionUbicacion.js';

const ANTIGUEDAD_MAXIMA_UBICACION_MS = 15000;

export class TransmitirUbicacionEnTiempoReal {
    constructor(geolocalizador, mapa, reloj = () => Date.now()) {
        this.geolocalizador = geolocalizador;
        this.mapa = mapa;
        this.reloj = reloj;
        this.idSeguimiento = null;
        this.activo = false;
        this.ultimaUbicacion = null;
    }

    iniciar(alRecibirUbicacion, alOcurrirError) {
        if (this.activo) return false;

        this.activo = true;
        try {
            const idSeguimiento = this.geolocalizador.observar(
                (lectura) => {
                    if (!this.activo) return;
                    if (!tienePrecisionSuficiente(lectura.precisionMetros)) {
                        this.ultimaUbicacion = null;
                        this.mapa.limpiarUbicacionEnTiempoReal();
                        alRecibirUbicacion({
                            precisa: false,
                            precisionMetros: lectura.precisionMetros
                        });
                        return;
                    }

                    this.ultimaUbicacion = {
                        latitud: lectura.latitud,
                        longitud: lectura.longitud,
                        precisionMetros: lectura.precisionMetros,
                        recibidaEn: this.reloj()
                    };
                    this.mapa.mostrarUbicacionEnTiempoReal(
                        lectura.latitud,
                        lectura.longitud,
                        lectura.precisionMetros
                    );
                    alRecibirUbicacion({ precisa: true });
                },
                (error) => {
                    if (!this.activo) return;
                    this.detener();
                    alOcurrirError(error);
                }
            );
            if (this.activo) {
                this.idSeguimiento = idSeguimiento;
            } else {
                this.geolocalizador.dejarDeObservar(idSeguimiento);
            }
            return this.activo;
        } catch (error) {
            this.detener();
            throw error;
        }
    }

    detener() {
        const idSeguimiento = this.idSeguimiento;
        this.idSeguimiento = null;
        this.activo = false;
        this.ultimaUbicacion = null;
        if (idSeguimiento !== null) this.geolocalizador.dejarDeObservar(idSeguimiento);
        this.mapa.limpiarUbicacionEnTiempoReal();
    }

    estaActivo() {
        return this.activo;
    }

    obtenerUltimaUbicacion() {
        if (!this.activo || !this.ultimaUbicacion) return null;
        if (this.reloj() - this.ultimaUbicacion.recibidaEn > ANTIGUEDAD_MAXIMA_UBICACION_MS) {
            return null;
        }

        return {
            latitud: this.ultimaUbicacion.latitud,
            longitud: this.ultimaUbicacion.longitud
        };
    }
}
