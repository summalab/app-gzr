export class UbicacionGateway {
    constructor(httpClient) {
        this.httpClient = httpClient;
    }

    listarPublicas() {
        return this.httpClient.get('/ubicaciones-publicas');
    }

    listarPropias() {
        return this.httpClient.get('/mis-ubicaciones');
    }

    crear(titulo, descripcion, latitud, longitud) {
        const datos = new FormData();
        datos.append('titulo', titulo);
        datos.append('descripcion', descripcion);
        datos.append('latitud', latitud);
        datos.append('longitud', longitud);
        return this.httpClient.post('/crear-ubicacion', datos);
    }

    eliminar(idUbicacion) {
        const datos = new FormData();
        datos.append('id_ubicacion', idUbicacion);
        return this.httpClient.post('/eliminar-ubicacion', datos);
    }
}
