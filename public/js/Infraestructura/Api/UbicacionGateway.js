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

    crear(titulo, descripcion, latitud, longitud, clasificaciones) {
        const datos = new FormData();
        datos.append('titulo', titulo);
        datos.append('descripcion', descripcion);
        datos.append('latitud', latitud);
        datos.append('longitud', longitud);
        datos.append('tipo_infraestructura', clasificaciones.tipo_infraestructura);
        datos.append('estado_infraestructura', clasificaciones.estado_infraestructura);
        datos.append('riesgos_directos', clasificaciones.riesgos_directos);
        datos.append('riesgos_indirectos', clasificaciones.riesgos_indirectos);
        datos.append('actividades_delictivas', clasificaciones.actividades_delictivas);
        datos.append('condiciones_sociales', clasificaciones.condiciones_sociales);
        return this.httpClient.post('/crear-ubicacion', datos);
    }

    eliminar(idUbicacion) {
        const datos = new FormData();
        datos.append('id_ubicacion', idUbicacion);
        return this.httpClient.post('/eliminar-ubicacion', datos);
    }
}
