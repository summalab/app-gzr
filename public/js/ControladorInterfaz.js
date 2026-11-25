export class ControladorInterfaz {
    constructor({
        autenticacion,
        autenticacionController,
        perfilController,
        ubicacionesController,
        ubicacionTiempoRealController,
        gestorMapa,
        vista
    }) {
        this.autenticacion = autenticacion;
        this.autenticacionController = autenticacionController;
        this.perfilController = perfilController;
        this.ubicacionesController = ubicacionesController;
        this.ubicacionTiempoRealController = ubicacionTiempoRealController;
        this.gestorMapa = gestorMapa;
        this.vista = vista;
        this.usuarioActual = null;

        this.autenticacionController.establecerManejadorSesion((usuario) => this.actualizarEstadoSesion(usuario));
        this.perfilController.establecerManejadoresSesion(
            () => this.usuarioActual,
            (usuario) => this.actualizarEstadoSesion(usuario)
        );
        this.ubicacionesController.establecerProveedorSesion(() => this.usuarioActual);
    }

    inicializar() {
        this.vista.enlazarCierreModales();
        this.autenticacionController.inicializar();
        this.perfilController.inicializar();
        this.ubicacionesController.inicializar();
        this.gestorMapa.inicializar(
            [4.8133, -75.6961],
            6,
            (coordenadas) => this.ubicacionesController.manejarClicEnMapa(coordenadas)
        );
    }

    async restaurarSesion() {
        try {
            const estado = await this.autenticacion.verificarSesion();
            await this.actualizarEstadoSesion(
                estado.autenticado && estado.usuario ? estado.usuario : null
            );
        } catch (error) {
            console.error('Error al verificar la sesión inicial:', error);
            await this.actualizarEstadoSesion(null);
        }
    }

    actualizarEstadoSesion(usuario) {
        this.usuarioActual = usuario;
        this.vista.renderizarSesion(usuario);
        this.ubicacionTiempoRealController.actualizarSesion(usuario);
        this.ubicacionesController.cargarParaUsuario(usuario).catch((error) => {
            this.vista.mostrarAlertaGlobal(error.message, false);
        });
    }
}
