/**
 * Encapsula la comunicación HTTP con el módulo UbicacionesFuncionalidad.
 */
export class ServicioUbicacion {
    constructor(clienteHttp) {
        this.clienteHttp = clienteHttp;
    }

    async listarPublicas() {
        return await this.clienteHttp.get('/ubicaciones-publicas');
    }

    async listarPropias() {
        return await this.clienteHttp.get('/mis-ubicaciones');
    }

    async crear(titulo, descripcion, latitud, longitud) {
        const datos = new FormData();
        datos.append('titulo', titulo);
        datos.append('descripcion', descripcion);
        datos.append('latitud', latitud);
        datos.append('longitud', longitud);

        return await this.clienteHttp.post('/crear-ubicacion', datos);
    }

    async eliminar(idUbicacion) {
        const datos = new FormData();
        datos.append('id_ubicacion', idUbicacion);

        return await this.clienteHttp.post('/eliminar-ubicacion', datos);
    }
}
