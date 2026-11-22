/**
 * ServicioUsuario: Encapsula la lógica de comunicación para el registro, 
 * gestión de perfil, recuperación de cuenta y eliminación de usuarios.
 */
export class ServicioUsuario {
    constructor(clienteHttp) {
        this.clienteHttp = clienteHttp;
    }

    async registrar(apodo, correo, contrasena) {
        const fd = new FormData();
        fd.append('apodo_usuario', apodo);
        fd.append('correo_usuario', correo);
        fd.append('contrasena_hash_usuario', contrasena);
        return await this.clienteHttp.post('/registrar-usuario', fd);
    }

    async verificarCorreo(token) {
        return await this.clienteHttp.get(`/verificar-correo?token=${encodeURIComponent(token)}`);
    }

    async actualizarApodo(nuevoApodo) {
        const fd = new FormData();
        fd.append('nuevo_apodo', nuevoApodo);
        return await this.clienteHttp.post('/actualizar-apodo', fd);
    }

    async actualizarCorreo(nuevoCorreo) {
        const fd = new FormData();
        fd.append('nuevo_correo', nuevoCorreo);
        return await this.clienteHttp.post('/actualizar-correo', fd);
    }

    async cambiarContrasena(contrasenaActual, contrasenaNueva) {
        const fd = new FormData();
        fd.append('contrasena_actual', contrasenaActual);
        fd.append('contrasena_nueva', contrasenaNueva);
        return await this.clienteHttp.post('/cambiar-contrasena', fd);
    }

    async solicitarRecuperacion(correo) {
        const fd = new FormData();
        fd.append('correo_usuario', correo);
        return await this.clienteHttp.post('/solicitar-recuperacion', fd);
    }

    async restablecerContrasena(token, nuevaContrasena) {
        const fd = new FormData();
        fd.append('token', token);
        fd.append('nueva_contrasena', nuevaContrasena);
        return await this.clienteHttp.post('/restablecer-contrasena', fd);
    }

    async eliminarCuenta(contrasenaConfirmacion) {
        const fd = new FormData();
        fd.append('contrasena_confirmacion', contrasenaConfirmacion);
        return await this.clienteHttp.post('/eliminar-cuenta', fd);
    }
}
