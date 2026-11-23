export class ControladorPerfilUsuario {
    constructor(cuentaUsuario, autenticacion, vista) {
        this.cuentaUsuario = cuentaUsuario;
        this.autenticacion = autenticacion;
        this.vista = vista;
        this.obtenerSesion = () => null;
        this.onSesionCambiada = async () => {};
    }

    establecerManejadoresSesion(obtenerSesion, onSesionCambiada) {
        this.obtenerSesion = obtenerSesion;
        this.onSesionCambiada = onSesionCambiada;
    }

    inicializar() {
        this.vista.enlazarClick('boton-menu-principal', () => {
            const panel = this.vista.documento.getElementById('panel-cuenta');
            this.vista.alternarPanelPerfil(!panel?.classList.contains('abierto'));
        });
        this.vista.enlazarClick('boton-cerrar-panel-cuenta', () => this.vista.alternarPanelPerfil(false));
        this.vista.enlazarClick('opcion-editar-apodo', () => this.abrirOpcion('modal-editar-apodo'));
        this.vista.enlazarClick('opcion-editar-correo', () => this.abrirOpcion('modal-editar-correo'));
        this.vista.enlazarClick('opcion-cambiar-clave', () => this.abrirOpcion('modal-cambiar-contrasena'));
        this.vista.enlazarClick('opcion-eliminar-cuenta', () => this.abrirOpcion('modal-eliminar-cuenta'));
        this.vista.enlazarClick('boton-cerrar-sesion', () => this.procesarLogout());

        this.vista.enlazarFormulario('formulario-editar-apodo', (formulario) => this.procesarActualizarApodo(formulario));
        this.vista.enlazarFormulario('formulario-editar-correo', (formulario) => this.procesarActualizarCorreo(formulario));
        this.vista.enlazarFormulario('formulario-cambiar-contrasena', (formulario) => this.procesarCambiarContrasena(formulario));
        this.vista.enlazarFormulario('formulario-eliminar-cuenta', (formulario) => this.procesarEliminarCuenta(formulario));
    }

    abrirOpcion(idModal) {
        this.vista.alternarPanelPerfil(false);
        this.vista.abrirModal(idModal);
    }

    async procesarActualizarApodo(formulario) {
        const apodo = this.vista.valorCampo(formulario, 'nuevo_apodo').trim();
        const respuesta = await this.cuentaUsuario.actualizarApodo(apodo);
        const usuario = this.obtenerSesion();
        if (usuario) await this.onSesionCambiada({ ...usuario, apodo_usuario: apodo });
        formulario.reset();
        this.vista.cerrarModal('modal-editar-apodo');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarActualizarCorreo(formulario) {
        const respuesta = await this.cuentaUsuario.actualizarCorreo(
            this.vista.valorCampo(formulario, 'nuevo_correo')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-editar-correo');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarCambiarContrasena(formulario) {
        const respuesta = await this.cuentaUsuario.cambiarContrasena(
            this.vista.valorCampo(formulario, 'contrasena_actual'),
            this.vista.valorCampo(formulario, 'contrasena_nueva'),
            this.vista.valorCampo(formulario, 'confirmar_contrasena_nueva')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-cambiar-contrasena');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarEliminarCuenta(formulario) {
        const respuesta = await this.cuentaUsuario.eliminarCuenta(
            this.vista.valorCampo(formulario, 'contrasena_confirmacion')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-eliminar-cuenta');
        await this.onSesionCambiada(null);
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarLogout() {
        try {
            const respuesta = await this.autenticacion.cerrarSesion();
            await this.onSesionCambiada(null);
            this.vista.mostrarAlertaGlobal(respuesta.message, true);
        } catch (error) {
            this.vista.mostrarAlertaGlobal(error.message, false);
        }
    }
}
