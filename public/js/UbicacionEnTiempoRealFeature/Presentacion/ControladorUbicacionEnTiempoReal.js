import { PoliticaAccesoUbicacionTiempoReal } from '../Dominio/PoliticaAccesoUbicacionTiempoReal.js';
import { PRECISION_MAXIMA_METROS } from '../Dominio/PrecisionUbicacion.js';

const INTERVALO_ACTUALIZACION_POLITICA_MS = 30000;
const ROLES_CONFIGURABLES = new Set(['todos', 'visitante', 'usuario', 'moderador', 'administrador']);

export class ControladorUbicacionEnTiempoReal {
    constructor(gatewayConfiguracion, transmision, vista, documento = globalThis.document) {
        this.gatewayConfiguracion = gatewayConfiguracion;
        this.transmision = transmision;
        this.vista = vista;
        this.documento = documento;
        this.usuarioActual = null;
        this.politica = null;
        this.dispositivoCompatible = this.esDispositivoMovilCompatible();
        this.avisoPrecisionAbierto = false;
        this.errorConfiguracionNotificado = false;
        this.actualizacionConfiguracion = null;
    }

    inicializar() {
        this.vista.enlazarClick('boton-seguimiento-ubicacion', () => this.alternarTransmision());
        this.vista.enlazarClick('boton-autorizar-gps-tiempo-real', () => this.autorizarOContinuar());
        this.vista.enlazarFormulario('formulario-configuracion-tiempo-real', (formulario) => (
            this.guardarConfiguracion(formulario)
        ));

        this.actualizarEstadoBoton(false);
        this.actualizarPolitica();
        if (this.dispositivoCompatible) {
            globalThis.setInterval(
                () => this.actualizarPolitica(),
                INTERVALO_ACTUALIZACION_POLITICA_MS
            );
        }
    }

    actualizarSesion(usuario) {
        this.usuarioActual = usuario;
        this.enlazarAccionAdministrativa();
        this.actualizarVisibilidad();

        if (this.transmision.estaActivo() && !this.puedeTransmitir()) {
            this.detenerTransmision();
            this.vista.mostrarAlertaGlobal(
                'La política administrativa ya no permite transmitir ubicación con este rol.',
                false
            );
        }
    }

    obtenerUltimaUbicacion() {
        return this.transmision.obtenerUltimaUbicacion();
    }

    async actualizarPolitica() {
        if (this.actualizacionConfiguracion) return this.actualizacionConfiguracion;

        this.actualizacionConfiguracion = this.cargarPolitica();
        try {
            await this.actualizacionConfiguracion;
        } finally {
            this.actualizacionConfiguracion = null;
        }
    }

    async cargarPolitica() {
        try {
            const respuesta = await this.gatewayConfiguracion.obtener();
            this.politica = new PoliticaAccesoUbicacionTiempoReal(
                respuesta.configuracion?.habilitada,
                respuesta.configuracion?.rolPermitido
            );
            this.errorConfiguracionNotificado = false;
            this.actualizarVisibilidad();

            if (this.transmision.estaActivo() && !this.puedeTransmitir()) {
                this.detenerTransmision();
                this.vista.mostrarAlertaGlobal(
                    'La transmisión de ubicación fue detenida por la configuración administrativa.',
                    false
                );
            }
        } catch (error) {
            this.politica = null;
            this.actualizarVisibilidad();
            if (this.transmision.estaActivo()) this.detenerTransmision();
            if (!this.errorConfiguracionNotificado) {
                this.vista.mostrarAlertaGlobal(
                    `No se pudo cargar la política de ubicación en tiempo real: ${error.message}`,
                    false
                );
                this.errorConfiguracionNotificado = true;
            }
        }
    }

    async alternarTransmision() {
        if (this.transmision.estaActivo()) {
            this.detenerTransmision();
            return;
        }

        await this.actualizarPolitica();
        if (!this.puedeTransmitir()) {
            this.actualizarVisibilidad();
            return;
        }

        this.mostrarModalGps(
            `Para mostrar tu ubicación en el mapa se requiere GPS de alta precisión (margen reportado máximo de ${PRECISION_MAXIMA_METROS} m). Tu ubicación se mantiene en este dispositivo y no se envía al servidor.`,
            'Autorizar GPS'
        );
    }

    autorizarOContinuar() {
        if (this.transmision.estaActivo()) {
            this.vista.cerrarModal('modal-gps-tiempo-real');
            return;
        }

        this.vista.cerrarModal('modal-gps-tiempo-real');
        this.iniciarTransmision();
    }

    iniciarTransmision() {
        try {
            this.avisoPrecisionAbierto = false;
            const iniciada = this.transmision.iniciar(
                (resultado) => this.manejarLecturaGps(resultado),
                (error) => this.manejarErrorGps(error)
            );
            if (iniciada) this.actualizarEstadoBoton(true);
        } catch (error) {
            this.actualizarEstadoBoton(false);
            this.mostrarModalGps(error.message, 'Intentar de nuevo');
        }
    }

    manejarLecturaGps(resultado) {
        if (!resultado.precisa) {
            if (!this.avisoPrecisionAbierto) {
                const precision = Number.isFinite(resultado.precisionMetros)
                    ? `${Math.round(resultado.precisionMetros)} m`
                    : 'desconocido';
                this.mostrarModalGps(
                    `La precisión GPS actual es ${precision}. Se requiere un margen máximo de ${PRECISION_MAXIMA_METROS} m; esta lectura no se muestra ni se usa para guardar ubicaciones. Se seguirá buscando una lectura más precisa.`,
                    'Seguir intentando'
                );
                this.avisoPrecisionAbierto = true;
            }
            return;
        }

        this.avisoPrecisionAbierto = false;
        this.vista.cerrarModal('modal-gps-tiempo-real');
        this.actualizarEstadoBoton(true);
    }

    manejarErrorGps(error) {
        this.actualizarEstadoBoton(false);
        this.mostrarModalGps(error.message, 'Intentar de nuevo');
    }

    detenerTransmision() {
        this.transmision.detener();
        this.avisoPrecisionAbierto = false;
        this.actualizarEstadoBoton(false);
        this.vista.cerrarModal('modal-gps-tiempo-real');
    }

    actualizarEstadoBoton(activo) {
        const boton = this.documento.getElementById('boton-seguimiento-ubicacion');
        if (!boton) return;

        boton.classList.toggle('esta-activo', activo);
        const botonNativo = boton.querySelector('button') || boton;
        botonNativo.setAttribute('aria-pressed', String(activo));
        botonNativo.setAttribute(
            'aria-label',
            activo ? 'Desactivar ubicación en tiempo real' : 'Activar ubicación en tiempo real'
        );
        botonNativo.setAttribute(
            'title',
            activo ? 'Desactivar ubicación en tiempo real' : 'Activar ubicación en tiempo real'
        );
    }

    actualizarVisibilidad() {
        this.vista.establecerVisibilidad(
            'boton-seguimiento-ubicacion',
            this.dispositivoCompatible && this.puedeTransmitir()
        );
    }

    puedeTransmitir() {
        return this.politica?.permite(this.usuarioActual) === true;
    }

    enlazarAccionAdministrativa() {
        const boton = this.documento.getElementById('accion-configuracion-tiempo-real');
        if (!boton || boton.dataset.controlUbicacionTiempoReal === 'enlazado') return;

        boton.dataset.controlUbicacionTiempoReal = 'enlazado';
        boton.addEventListener('click', () => this.abrirConfiguracionAdministrativa());
    }

    async abrirConfiguracionAdministrativa() {
        if (String(this.usuarioActual?.rol_usuario).toLowerCase() !== 'administrador') return;

        await this.actualizarPolitica();
        const seleccion = this.documento.getElementById('configuracion-tiempo-real-alcance');
        const alerta = this.documento.getElementById('alerta-configuracion-tiempo-real');
        if (alerta) {
            alerta.textContent = '';
            alerta.className = 'alerta-modal';
        }
        if (seleccion && this.politica) {
            seleccion.value = this.politica.habilitada
                ? this.politica.rolPermitido
                : 'desactivada';
        }
        this.vista.abrirModal('modal-configuracion-tiempo-real');
    }

    async guardarConfiguracion(formulario) {
        if (String(this.usuarioActual?.rol_usuario).toLowerCase() !== 'administrador') {
            throw new Error('Solo un administrador puede cambiar esta configuración.');
        }

        await this.actualizarPolitica();
        const alcance = this.vista.valorCampo(formulario, 'alcance');
        if (alcance !== 'desactivada' && !ROLES_CONFIGURABLES.has(alcance)) {
            throw new Error('Selecciona un alcance válido para la transmisión.');
        }

        const respuesta = await this.gatewayConfiguracion.guardar({
            habilitada: alcance !== 'desactivada',
            rolPermitido: alcance === 'desactivada' ? 'todos' : alcance
        });
        this.politica = new PoliticaAccesoUbicacionTiempoReal(
            respuesta.configuracion.habilitada,
            respuesta.configuracion.rolPermitido
        );

        if (this.transmision.estaActivo() && !this.puedeTransmitir()) {
            this.detenerTransmision();
        }

        this.actualizarVisibilidad();
        this.vista.cerrarModal('modal-configuracion-tiempo-real');
        this.vista.mostrarAlertaGlobal(respuesta.message, true);
    }

    mostrarModalGps(mensaje, textoBoton) {
        this.vista.asignarTexto('mensaje-modal-gps-tiempo-real', mensaje);
        this.vista.asignarTexto('texto-boton-autorizar-gps-tiempo-real', textoBoton);
        this.vista.abrirModal('modal-gps-tiempo-real');
    }

    esDispositivoMovilCompatible() {
        const navegador = globalThis.navigator;
        const ventana = globalThis.window;
        if (!navegador?.geolocation || !ventana?.isSecureContext) return false;

        const agenteMovil = /Android|iPhone|iPad|iPod|Mobile/i.test(navegador.userAgent || '');
        const pantallaTactilMovil = ventana.matchMedia?.('(pointer: coarse) and (max-width: 1024px)').matches;
        return agenteMovil || pantallaTactilMovil === true;
    }
}
