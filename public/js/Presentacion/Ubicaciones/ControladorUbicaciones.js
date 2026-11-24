export class ControladorUbicaciones {
    constructor(ubicacionUseCases, gestorMapa, vista, confirmar = (mensaje) => globalThis.confirm(mensaje)) {
        this.ubicacionUseCases = ubicacionUseCases;
        this.gestorMapa = gestorMapa;
        this.vista = vista;
        this.confirmar = confirmar;
        this.obtenerSesion = () => null;
        this.coordenadasGpsCapturadas = null;
    }

    establecerProveedorSesion(obtenerSesion) {
        this.obtenerSesion = obtenerSesion;
    }

    inicializar() {
        this.gestorMapa.establecerManejadorEliminacion((id) => this.procesarEliminarUbicacion(id));
        this.vista.enlazarClick('boton-nueva-ubicacion', () => this.procesarUbicacionGps());

        this.vista.enlazarFormulario('formulario-nueva-ubicacion', (formulario) => this.procesarCrearUbicacion(formulario));
    }

    async cargarParaUsuario(usuario) {
        this.coordenadasGpsCapturadas = null;
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

    /**
     * UBICACIONES MANUALES DESDE EL MAPA
     * Maneja el evento de clic en el mapa para agregar una nueva ubicación.
     * se asume que el usuario ya ha iniciado sesión y tiene permisos de administrador
     * para agregar ubicaciones manualmente desde el mapa. Si no es así, se le indica
     * que use el botón GPS y luego guarde la ubicación capturada.
     * @param {Object} coordenadas - Las coordenadas del clic en el mapa.
     * @param {number} coordenadas.lat - La latitud de la ubicación.
     * @param {number} coordenadas.lng - La longitud de la ubicación.
     */
    manejarClicEnMapa(coordenadas) {
        const usuario = this.obtenerSesion();
        if (!usuario) return;
        if (String(usuario.rol_usuario).toLowerCase() !== 'administrador') {
            this.vista.mostrarAlertaGlobal(
                'Para agregar una ubicación, usa el botón GPS y luego guarda la ubicación capturada.',
                false
            );
            return;
        }

        /**
         * aqui se asignan las coordenadas del clic en el mapa a los campos de
         * latitud y longitud del formulario de nueva ubicación,
         */
        this.vista.asignarValor('ubicacion-latitud', coordenadas.lat);
        this.vista.asignarTexto('label-ubicacion-latitud', `Latitud: ${coordenadas.lat}`);

        this.vista.asignarValor('ubicacion-longitud', coordenadas.lng);
        this.vista.asignarTexto('label-ubicacion-longitud', `Longitud: ${coordenadas.lng}`);

        this.vista.abrirModal('modal-nueva-ubicacion');
    }

    /**
     * UBICACIONES POR GPS
     * Solicita al usuario su ubicación GPS (permiso requerido para usar GPS del dispositivo) y,
     * si se obtiene con éxito, llena los campos de latitud y longitud en el formulario de
     * nueva ubicación. Si ocurre un error al obtener la ubicación, se muestra un mensaje de
     * error en el modal.
     * @returns
     */
    async procesarUbicacionGps() {
        try {
            const gpsEjecutado = await this.vista.ejecutarConBotonEnCarga(
                'boton-nueva-ubicacion',
                '📡 Obteniendo señal GPS...',
                async () => {
                    this.coordenadasGpsCapturadas = null;
                    this.vista.asignarValor('ubicacion-latitud', '');
                    this.vista.asignarTexto('label-ubicacion-latitud', 'Latitud:');
                    this.vista.asignarValor('ubicacion-longitud', '');
                    this.vista.asignarTexto('label-ubicacion-longitud', 'Longitud:');

                    const coordenadas = await this.gestorMapa.obtenerUbicacionGps();
                    /**
                     * aqui se asignan las coordenadas del clic en el mapa a los campos de
                     * latitud y longitud del formulario de nueva ubicación,
                     */
                    this.vista.asignarValor('ubicacion-latitud', coordenadas.latitud);
                    this.vista.asignarTexto('label-ubicacion-latitud', `Latitud: ${coordenadas.latitud}`);

                    this.vista.asignarValor('ubicacion-longitud', coordenadas.longitud);
                    this.vista.asignarTexto('label-ubicacion-longitud', `Longitud: ${coordenadas.longitud}`);

                    this.coordenadasGpsCapturadas = coordenadas;
                }
            );
            if (!gpsEjecutado) return;
            this.vista.abrirModal('modal-nueva-ubicacion');
            this.vista.mostrarAlertaModal('modal-nueva-ubicacion', '¡Ubicación GPS capturada con éxito!', true);
        } catch (error) {
            this.vista.abrirModal('modal-nueva-ubicacion');
            this.vista.mostrarAlertaModal('modal-nueva-ubicacion', error.message, false);
        }
    }

    async procesarCrearUbicacion(formulario) {
        const latitud = Number.parseFloat(this.vista.valorCampo(formulario, 'latitud'));
        const longitud = Number.parseFloat(this.vista.valorCampo(formulario, 'longitud'));
        const usuario = this.obtenerSesion();
        if (usuario && String(usuario.rol_usuario).toLowerCase() !== 'administrador') {
            const coordenadasGps = this.coordenadasGpsCapturadas;
            if (!coordenadasGps || latitud !== coordenadasGps.latitud || longitud !== coordenadasGps.longitud) {
                throw new Error('Usa el botón GPS para capturar las coordenadas antes de guardar la ubicación.');
            }
        }

        const respuesta = await this.ubicacionUseCases.crear(
            this.vista.valorCampo(formulario, 'titulo'),
            this.vista.valorCampo(formulario, 'descripcion'),
            latitud,
            longitud
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
