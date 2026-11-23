export class AuthGateway {
    constructor(httpClient) {
        this.httpClient = httpClient;
    }

    iniciarSesion(correo, contrasena) {
        const datos = new FormData();
        datos.append('correo_usuario', correo);
        datos.append('contrasena', contrasena);
        return this.httpClient.post('/login', datos);
    }

    cerrarSesion() {
        return this.httpClient.post('/logout', new FormData());
    }

    verificarSesion() {
        return this.httpClient.get('/verificar-sesion');
    }
}
