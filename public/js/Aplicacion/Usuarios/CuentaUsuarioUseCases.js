export class CuentaUsuarioUseCases {
    constructor(usuarioGateway) {
        this.usuarioGateway = usuarioGateway;
    }

    registrar(apodo, correo, contrasena, confirmarContrasena) {
        this.validarConfirmacion(contrasena, confirmarContrasena);
        return this.usuarioGateway.registrar(apodo.trim(), correo.trim(), contrasena);
    }

    verificarCorreo(token) {
        return this.usuarioGateway.verificarCorreo(token);
    }

    solicitarRecuperacion(correo) {
        return this.usuarioGateway.solicitarRecuperacion(correo.trim());
    }

    restablecerContrasena(token, contrasena, confirmarContrasena) {
        this.validarConfirmacion(contrasena, confirmarContrasena);
        return this.usuarioGateway.restablecerContrasena(token, contrasena);
    }

    actualizarApodo(apodo) {
        return this.usuarioGateway.actualizarApodo(apodo.trim());
    }

    actualizarCorreo(correo) {
        return this.usuarioGateway.actualizarCorreo(correo.trim());
    }

    cambiarContrasena(actual, nueva, confirmarNueva) {
        this.validarConfirmacion(nueva, confirmarNueva);
        return this.usuarioGateway.cambiarContrasena(actual, nueva);
    }

    eliminarCuenta(contrasena) {
        return this.usuarioGateway.eliminarCuenta(contrasena);
    }

    validarConfirmacion(contrasena, confirmarContrasena) {
        if (contrasena !== confirmarContrasena) {
            throw new Error('Las contraseñas ingresadas no coinciden.');
        }
    }
}
