export class ConfiguracionUbicacionTiempoRealGateway {
    constructor(httpClient) {
        this.httpClient = httpClient;
    }

    obtener() {
        return this.httpClient.get('/ubicacion-tiempo-real/configuracion');
    }

    guardar(configuracion) {
        return this.httpClient.post('/ubicacion-tiempo-real/configuracion', configuracion);
    }
}
