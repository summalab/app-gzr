/**
 * ServicioAuth: Gestiona el inicio de sesión, cierre de sesión y verificación de estado 
 * mediante llamadas POST/GET con FormData hacia AuthControlador.php.
 */
export class ServicioAuth {
    constructor(clienteHttp) {
        this.clienteHttp = clienteHttp;
    }

    async iniciarSesion(correo, contrasena) {
        const fd = new FormData();
        fd.append('correo_usuario', correo);
        fd.append('contrasena', contrasena);
        return await this.clienteHttp.post('/login', fd);
    }

    async cerrarSesion() {
        return await this.clienteHttp.post('/logout', new FormData());
    }

    async verificarSesion() {
        return await this.clienteHttp.get('/verificar-sesion');
    }
}
