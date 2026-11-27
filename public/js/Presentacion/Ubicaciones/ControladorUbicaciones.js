export class ControladorUbicaciones {
    constructor(ubicacionUseCases, gestorMapa, vista, confirmar = (mensaje) => globalThis.confirm(mensaje)) {
        this.ubicacionUseCases = ubicacionUseCases;
        this.gestorMapa = gestorMapa;
        this.vista = vista;
        this.confirmar = confirmar;
        this.obtenerSesion = () => null;
        this.obtenerCoordenadasEnTiempoReal = () => null;
        this.coordenadasGpsCapturadas = null;
        this.ubicacionEditandoId = null;
    }

    establecerProveedorSesion(obtenerSesion) {
        this.obtenerSesion = obtenerSesion;
    }

    establecerProveedorCoordenadasEnTiempoReal(obtenerCoordenadas) {
        this.obtenerCoordenadasEnTiempoReal = obtenerCoordenadas;
    }

    inicializar() {
        this.gestorMapa.establecerManejadorEliminacion((id) => this.procesarEliminarUbicacion(id));
        this.gestorMapa.establecerManejadorEdicion((ubicacion) => this.prepararEdicionUbicacion(ubicacion));
        this.vista.enlazarClick('boton-nueva-ubicacion', () => this.procesarUbicacionGps());
        this.vista.establecerPreparadorNuevaUbicacion(() => this.prepararFormularioNuevo());

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

        this.prepararFormularioNuevo();
        this.establecerCamposFormularioVisibles(true);

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
                    this.prepararFormularioNuevo();
                    this.coordenadasGpsCapturadas = null;
                    this.establecerCamposFormularioVisibles(false);
                    this.vista.asignarValor('ubicacion-latitud', '');
                    this.vista.asignarTexto('label-ubicacion-latitud', 'Latitud:');
                    this.vista.asignarValor('ubicacion-longitud', '');
                    this.vista.asignarTexto('label-ubicacion-longitud', 'Longitud:');

                    const coordenadas = this.obtenerCoordenadasEnTiempoReal()
                        || await this.gestorMapa.obtenerUbicacionGps();
                    /**
                     * aqui se asignan las coordenadas del clic en el mapa a los campos de
                     * latitud y longitud del formulario de nueva ubicación,
                     */
                    this.vista.asignarValor('ubicacion-latitud', coordenadas.latitud);
                    this.vista.asignarTexto('label-ubicacion-latitud', `Latitud: ${coordenadas.latitud}`);

                    this.vista.asignarValor('ubicacion-longitud', coordenadas.longitud);
                    this.vista.asignarTexto('label-ubicacion-longitud', `Longitud: ${coordenadas.longitud}`);

                    this.coordenadasGpsCapturadas = coordenadas;
                    this.establecerCamposFormularioVisibles(true);
                }
            );
            if (!gpsEjecutado) return;
            this.vista.abrirModal('modal-nueva-ubicacion');
            this.vista.mostrarAlertaModal('modal-nueva-ubicacion', '¡Ubicación GPS capturada con éxito!', true);
        } catch (error) {
            /**
             * Si existe un error al obtener la ubicación GPS, se muestra un mensaje de error en el modal.
             */
            this.establecerCamposFormularioVisibles(false);
            this.vista.asignarTexto('label-ubicacion-latitud', `Latitud: NO DISPONIBLE`);
            this.vista.asignarTexto('label-ubicacion-longitud', `Longitud: NO DISPONIBLE`);
            this.vista.abrirModal('modal-nueva-ubicacion');
            this.vista.mostrarAlertaModal('modal-nueva-ubicacion', error.message, false);
        }
    }

    establecerCamposFormularioVisibles(visible) {
        for (const id of [
            'grupo-ubicacion-titulo',
            'grupo-ubicacion-descripcion',
            'boton-guardar-ubicacion-gps'
        ]) {
            this.vista.establecerVisibilidad(id, visible);
        }
    }

    prepararFormularioNuevo() {
        this.ubicacionEditandoId = null;
        this.coordenadasGpsCapturadas = null;
        this.vista.restablecerFormulario('formulario-nueva-ubicacion');
        this.vista.asignarTexto('titulo-nueva-ubicacion', 'Nueva ubicación');
        this.vista.asignarTextoBoton('boton-guardar-ubicacion-gps', 'Guardar en el mapa');
        this.vista.asignarTexto('label-ubicacion-latitud', 'Latitud:');
        this.vista.asignarTexto('label-ubicacion-longitud', 'Longitud:');
    }

    prepararEdicionUbicacion(ubicacion) {
        this.prepararFormularioNuevo();
        this.ubicacionEditandoId = Number(ubicacion.id_ubicacion);

        this.vista.asignarValor('ubicacion-titulo', ubicacion.titulo || '');
        this.vista.asignarValor('ubicacion-descripcion', ubicacion.descripcion || 'sin descripción');
        this.vista.asignarValor('tipo_infraestructura', ubicacion.tipo_infraestructura || '');
        this.vista.asignarValor('estado_infraestructura', ubicacion.estado_infraestructura || '');
        this.vista.asignarValor('riesgos_directos', ubicacion.riesgos_directos || '');
        this.vista.asignarValor('riesgos_indirectos', ubicacion.riesgos_indirectos || '');
        this.vista.asignarValor('actividades_delictivas', ubicacion.actividades_delictivas || '');
        this.vista.asignarValor('condiciones_sociales', ubicacion.condiciones_sociales || '');

        this.vista.asignarValor('ubicacion-latitud', ubicacion.latitud);
        this.vista.asignarTexto('label-ubicacion-latitud', `Latitud: ${ubicacion.latitud}`);
        this.vista.asignarValor('ubicacion-longitud', ubicacion.longitud);
        this.vista.asignarTexto('label-ubicacion-longitud', `Longitud: ${ubicacion.longitud}`);

        this.vista.asignarTexto('titulo-nueva-ubicacion', 'Editar ubicación');
        this.vista.asignarTextoBoton('boton-guardar-ubicacion-gps', 'Guardar cambios');
        this.establecerCamposFormularioVisibles(true);
        this.vista.abrirModal('modal-nueva-ubicacion');
    }

    async procesarCrearUbicacion(formulario) {
        const latitud = Number.parseFloat(this.vista.valorCampo(formulario, 'latitud'));
        const longitud = Number.parseFloat(this.vista.valorCampo(formulario, 'longitud'));
        const usuario = this.obtenerSesion();
        if (
            this.ubicacionEditandoId === null
            && usuario
            && String(usuario.rol_usuario).toLowerCase() !== 'administrador'
        ) {
            const coordenadasGps = this.coordenadasGpsCapturadas;
            if (!coordenadasGps || latitud !== coordenadasGps.latitud || longitud !== coordenadasGps.longitud) {
                throw new Error('Usa el botón GPS para capturar las coordenadas antes de guardar la ubicación.');
            }
        }

        const clasificaciones = {
            tipo_infraestructura: this.vista.valorCampo(formulario, 'tipo_infraestructura'),
            estado_infraestructura: this.vista.valorCampo(formulario, 'estado_infraestructura'),
            riesgos_directos: this.vista.valorCampo(formulario, 'riesgos_directos'),
            riesgos_indirectos: this.vista.valorCampo(formulario, 'riesgos_indirectos'),
            actividades_delictivas: this.vista.valorCampo(formulario, 'actividades_delictivas'),
            condiciones_sociales: this.vista.valorCampo(formulario, 'condiciones_sociales')
        };
        const titulo = this.vista.valorCampo(formulario, 'titulo');
        const descripcion = this.vista.valorCampo(formulario, 'descripcion');
        const respuesta = this.ubicacionEditandoId === null
            ? await this.ubicacionUseCases.crear(titulo, descripcion, latitud, longitud, clasificaciones)
            : await this.ubicacionUseCases.actualizar(
                this.ubicacionEditandoId,
                titulo,
                descripcion,
                clasificaciones
            );

        this.prepararFormularioNuevo();
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
