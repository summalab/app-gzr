import { Coordenadas } from '../../Dominio/Ubicaciones/Coordenadas.js';

export class UbicacionUseCases {
    constructor(ubicacionGateway) {
        this.ubicacionGateway = ubicacionGateway;
    }

    listarPublicas() {
        return this.ubicacionGateway.listarPublicas();
    }

    listarPropias() {
        return this.ubicacionGateway.listarPropias();
    }

    crear(titulo, descripcion, latitud, longitud) {
        const coordenadas = new Coordenadas(latitud, longitud);
        return this.ubicacionGateway.crear(
            titulo.trim(),
            descripcion.trim(),
            coordenadas.latitud,
            coordenadas.longitud
        );
    }

    eliminar(idUbicacion) {
        return this.ubicacionGateway.eliminar(idUbicacion);
    }
}
