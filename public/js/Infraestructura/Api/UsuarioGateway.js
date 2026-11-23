export class UsuarioGateway {
    constructor(httpClient) {
        this.httpClient = httpClient;
    }

    registrar(apodo, correo, contrasena) {
        const datos = new FormData();
        datos.append('apodo_usuario', apodo);
        datos.append('correo_usuario', correo);
        datos.append('contrasena_hash_usuario', contrasena);
        return this.httpClient.post('/registrar-usuario', datos);
    }

    verificarCorreo(token) {
        return this.httpClient.get(`/verificar-correo?token=${encodeURIComponent(token)}`);
    }

    actualizarApodo(apodo) {
        return this.postForm('/actualizar-apodo', 'nuevo_apodo', apodo);
    }

    actualizarCorreo(correo) {
        return this.postForm('/actualizar-correo', 'nuevo_correo', correo);
    }

    cambiarContrasena(actual, nueva) {
        const datos = new FormData();
        datos.append('contrasena_actual', actual);
        datos.append('contrasena_nueva', nueva);
        return this.httpClient.post('/cambiar-contrasena', datos);
    }

    solicitarRecuperacion(correo) {
        return this.postForm('/solicitar-recuperacion', 'correo_usuario', correo);
    }

    restablecerContrasena(token, nueva) {
        const datos = new FormData();
        datos.append('token', token);
        datos.append('nueva_contrasena', nueva);
        return this.httpClient.post('/restablecer-contrasena', datos);
    }

    eliminarCuenta(contrasena) {
        return this.postForm('/eliminar-cuenta', 'contrasena_confirmacion', contrasena);
    }

    postForm(endpoint, campo, valor) {
        const datos = new FormData();
        datos.append(campo, valor);
        return this.httpClient.post(endpoint, datos);
    }
}
