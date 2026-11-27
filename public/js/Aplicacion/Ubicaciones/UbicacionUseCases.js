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

    crear(titulo, descripcion, latitud, longitud, clasificaciones) {
        const coordenadas = new Coordenadas(latitud, longitud);
        return this.ubicacionGateway.crear(
            titulo.trim(),
            descripcion?.trim() || 'sin descripción',
            coordenadas.latitud,
            coordenadas.longitud,
            clasificaciones
        );
    }

    actualizar(idUbicacion, titulo, descripcion, clasificaciones) {
        return this.ubicacionGateway.actualizar(
            idUbicacion,
            titulo.trim(),
            descripcion?.trim() || 'sin descripción',
            clasificaciones
        );
    }

    eliminar(idUbicacion) {
        return this.ubicacionGateway.eliminar(idUbicacion);
    }
}
