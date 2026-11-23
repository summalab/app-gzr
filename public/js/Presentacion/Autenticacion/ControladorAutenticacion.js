export class ControladorAutenticacion {
    constructor(autenticacion, cuentaUsuario, vista) {
        this.autenticacion = autenticacion;
        this.cuentaUsuario = cuentaUsuario;
        this.vista = vista;
        this.onSesionCambiada = async () => {};
    }

    establecerManejadorSesion(manejador) {
        this.onSesionCambiada = manejador;
    }

    inicializar() {
        this.vista.enlazarClick('btn-abrir-login', () => this.vista.abrirModal('modal-login'));
        this.vista.enlazarClick('btn-abrir-registro', () => this.vista.abrirModal('modal-registro'));
        this.vista.enlazarClick('btn-ir-recuperar', () => {
            this.vista.cerrarModal('modal-login');
            this.vista.abrirModal('modal-recuperar');
        });

        this.vista.enlazarFormulario('form-login', (formulario) => this.procesarLogin(formulario));
        this.vista.enlazarFormulario('form-registro', (formulario) => this.procesarRegistro(formulario));
        this.vista.enlazarFormulario('form-recuperar', (formulario) => this.procesarRecuperacion(formulario));
        this.vista.enlazarFormulario('form-restablecer', (formulario) => this.procesarRestablecimiento(formulario));
    }

    async procesarEnlaceInicial() {
        const parametros = new URLSearchParams(globalThis.location.search);
        const tokenVerificacion = parametros.get('token');
        const tokenRecuperacion = parametros.get('recuperar');

        if (tokenVerificacion) {
            try {
                const respuesta = await this.cuentaUsuario.verificarCorreo(tokenVerificacion);
                this.vista.mostrarAlertaGlobal(respuesta.message, true);
                this.vista.abrirModal('modal-login');
            } catch (error) {
                this.vista.mostrarAlertaGlobal(error.message, false);
            } finally {
                this.limpiarUrl();
            }
        } else if (tokenRecuperacion) {
            this.vista.asignarValor('token-recuperacion', tokenRecuperacion);
            this.vista.abrirModal('modal-restablecer');
            this.limpiarUrl();
        }
    }

    async procesarLogin(formulario) {
        const respuesta = await this.autenticacion.iniciarSesion(
            this.vista.valorCampo(formulario, 'correo_usuario'),
            this.vista.valorCampo(formulario, 'contrasena')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-login');
        await this.onSesionCambiada(respuesta.usuario);
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarRegistro(formulario) {
        const respuesta = await this.cuentaUsuario.registrar(
            this.vista.valorCampo(formulario, 'apodo_usuario'),
            this.vista.valorCampo(formulario, 'correo_usuario'),
            this.vista.valorCampo(formulario, 'contrasena_hash_usuario'),
            this.vista.valorCampo(formulario, 'confirmar_contrasena')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-registro');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarRecuperacion(formulario) {
        const respuesta = await this.cuentaUsuario.solicitarRecuperacion(
            this.vista.valorCampo(formulario, 'correo_usuario')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-recuperar');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarRestablecimiento(formulario) {
        const respuesta = await this.cuentaUsuario.restablecerContrasena(
            this.vista.valorCampo(formulario, 'token').trim(),
            this.vista.valorCampo(formulario, 'nueva_contrasena'),
            this.vista.valorCampo(formulario, 'confirmar_nueva_contrasena')
        );
        formulario.reset();
        this.vista.cerrarModal('modal-restablecer');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
        this.vista.abrirModal('modal-login');
    }

    limpiarUrl() {
        const url = `${globalThis.location.origin}${globalThis.location.pathname}`;
        globalThis.history.replaceState({}, globalThis.document.title, url);
    }
}
