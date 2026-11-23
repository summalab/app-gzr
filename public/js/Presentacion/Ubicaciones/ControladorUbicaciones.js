export class ControladorUbicaciones {
    constructor(ubicacionUseCases, gestorMapa, vista, confirmar = (mensaje) => globalThis.confirm(mensaje)) {
        this.ubicacionUseCases = ubicacionUseCases;
        this.gestorMapa = gestorMapa;
        this.vista = vista;
        this.confirmar = confirmar;
        this.obtenerSesion = () => null;
    }

    establecerProveedorSesion(obtenerSesion) {
        this.obtenerSesion = obtenerSesion;
    }

    inicializar() {
        this.gestorMapa.establecerManejadorEliminacion((id) => this.procesarEliminarUbicacion(id));
        this.vista.enlazarClick('btn-nueva-ubicacion', () => this.vista.abrirModal('modal-nueva-ubicacion'));
        this.vista.enlazarFormulario('form-nueva-ubicacion', (formulario) => this.procesarCrearUbicacion(formulario));

        this.vista.enlazarClick('btn-usar-gps', () => this.procesarUbicacionGps());
    }

    async cargarParaUsuario(usuario) {
        const datosPublicos = await this.ubicacionUseCases.listarPublicas();
        if (datosPublicos?.geocerca) {
            const { lat, lng, radioKm, modo } = datosPublicos.geocerca;
            this.gestorMapa.actualizarGeocerca(lat, lng, radioKm, modo || 'circular');
        }

        if (usuario) {
            const respuesta = await this.ubicacionUseCases.listarPropias();
            this.gestorMapa.cargarVistaPrivada(respuesta.ubicaciones || [], usuario.apodo_usuario);
        } else {
            this.gestorMapa.cargarVistaPublica(datosPublicos.ubicaciones || []);
        }
    }

    manejarClicEnMapa(coordenadas) {
        if (!this.obtenerSesion()) return;

        this.vista.asignarValor('ubicacion-latitud', coordenadas.lat);
        this.vista.asignarValor('ubicacion-longitud', coordenadas.lng);
        this.vista.abrirModal('modal-nueva-ubicacion');
    }

    async procesarUbicacionGps() {
        try {
            const gpsEjecutado = await this.vista.ejecutarConBotonEnCarga(
                'btn-usar-gps',
                '📡 Obteniendo señal GPS...',
                async () => {
                    const coordenadas = await this.gestorMapa.obtenerUbicacionGps();
                    this.vista.asignarValor('ubicacion-latitud', coordenadas.latitud);
                    this.vista.asignarValor('ubicacion-longitud', coordenadas.longitud);
                }
            );
            if (!gpsEjecutado) return;
            this.vista.mostrarAlertaModal('modal-nueva-ubicacion', '¡Ubicación GPS capturada con éxito!', true);
        } catch (error) {
            this.vista.mostrarAlertaModal('modal-nueva-ubicacion', error.message, false);
        }
    }

    async procesarCrearUbicacion(formulario) {
        const respuesta = await this.ubicacionUseCases.crear(
            this.vista.valorCampo(formulario, 'titulo'),
            this.vista.valorCampo(formulario, 'descripcion'),
            Number.parseFloat(this.vista.valorCampo(formulario, 'latitud')),
            Number.parseFloat(this.vista.valorCampo(formulario, 'longitud'))
        );
        formulario.reset();
        this.vista.cerrarModal('modal-nueva-ubicacion');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
        await this.cargarParaUsuario(this.obtenerSesion());
    }

    async procesarEliminarUbicacion(idUbicacion) {
        const usuario = this.obtenerSesion();
        if (!usuario) return;

        try {
            if (!this.confirmar('¿Estás seguro de que deseas eliminar esta ubicación de tu mapa?')) {
                await this.cargarParaUsuario(usuario);
                return;
            }

            const respuesta = await this.ubicacionUseCases.eliminar(idUbicacion);
            this.vista.mostrarAlertaGlobal(respuesta.message, true);
            await this.cargarParaUsuario(usuario);
        } catch (error) {
            this.vista.mostrarAlertaGlobal(error.message, false);
        }
    }
}
