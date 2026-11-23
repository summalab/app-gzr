export class AutenticacionUseCases {
    constructor(authGateway) {
        this.authGateway = authGateway;
    }

    iniciarSesion(correo, contrasena) {
        return this.authGateway.iniciarSesion(correo.trim(), contrasena);
    }

    cerrarSesion() {
        return this.authGateway.cerrarSesion();
    }

    verificarSesion() {
        return this.authGateway.verificarSesion();
    }
}
