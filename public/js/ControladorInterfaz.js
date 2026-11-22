/**
 * Controlador principal de la interfaz de usuario (SPA).
 * Gestiona el estado visual (Invitado vs Autenticado), modales <dialog>,
 * panel lateral (menú hamburguesa) y envío de formularios.
 */
export class ControladorInterfaz {
    /**
     * @param {object} servicioAuth Instancia de ServicioAuth.
     * @param {object} servicioUsuario Instancia de ServicioUsuario.
     * @param {object} gestorMapa Instancia de GestorMapaLeaflet.
     * @param {object} servicioUbicacion Instancia de servicioUbicacion.
     */
    constructor(servicioAuth, servicioUsuario, gestorMapa, servicioUbicacion) {
        this.servicioAuth = servicioAuth;
        this.servicioUsuario = servicioUsuario;
        this.gestorMapa = gestorMapa;
        this.servicioUbicacion = servicioUbicacion;
        this.usuarioActual = null;
    }

    /**
     * Enlaza todos los botones, modales y formularios del DOM.
     */
    inicializarEventos() {
        // 1. Botones de apertura de modales desde el Navbar
        this.enlazarClick('btn-abrir-login', () => this.abrirModal('modal-login'));
        this.enlazarClick('btn-abrir-registro', () => this.abrirModal('modal-registro'));
        this.enlazarClick('btn-ir-recuperar', () => {
            this.cerrarModal('modal-login');
            this.abrirModal('modal-recuperar');
        });

        // 2. Botones internos del panel lateral (Menú Hamburguesa)
        this.enlazarClick('btn-menu-hamburguesa', () => this.alternarPanelPerfil(true));
        this.enlazarClick('btn-cerrar-panel', () => this.alternarPanelPerfil(false));

        this.enlazarClick('opcion-editar-apodo', () => {
            this.alternarPanelPerfil(false);
            this.abrirModal('modal-editar-apodo');
        });

        this.enlazarClick('opcion-editar-correo', () => {
            this.alternarPanelPerfil(false);
            this.abrirModal('modal-editar-correo');
        });

        this.enlazarClick('opcion-cambiar-clave', () => {
            this.alternarPanelPerfil(false);
            this.abrirModal('modal-cambiar-clave');
        });

        this.enlazarClick('opcion-eliminar-cuenta', () => {
            this.alternarPanelPerfil(false);
            this.abrirModal('modal-eliminar-cuenta');
        });

        this.gestorMapa.establecerManejadorEliminacion(
            (idUbicacion) => this.procesarEliminarUbicacion(idUbicacion)
        );

        this.enlazarClick('btn-cerrar-sesion', () => this.procesarLogout());

        this.enlazarClick('btn-nueva-ubicacion', () => this.abrirModal('modal-nueva-ubicacion'));
        this.enlazarFormulario('form-nueva-ubicacion', (form) => this.procesarCrearUbicacion(form));



        // 3. Botones genéricos de cierre en todos los modales <dialog>
        document.querySelectorAll('[data-cerrar-modal]').forEach((boton) => {
            boton.addEventListener('click', () => {
                const dialogo = boton.closest('dialog');
                if (dialogo) dialogo.close();
            });
        });

        // 4. Enlace de envío de formularios
        this.enlazarFormulario('form-login', (form) => this.procesarLogin(form));
        this.enlazarFormulario('form-registro', (form) => this.procesarRegistro(form));
        this.enlazarFormulario('form-recuperar', (form) => this.procesarSolicitarRecuperacion(form));
        this.enlazarFormulario('form-restablecer', (form) => this.procesarRestablecerContrasena(form));
        this.enlazarFormulario('form-editar-apodo', (form) => this.procesarActualizarApodo(form));
        this.enlazarFormulario('form-editar-correo', (form) => this.procesarActualizarCorreo(form));
        this.enlazarFormulario('form-cambiar-clave', (form) => this.procesarCambiarContrasena(form));
        this.enlazarFormulario('form-eliminar-cuenta', (form) => this.procesarEliminarCuenta(form));

        /*
        const formNuevaUbicacion = document.getElementById('form-nueva-ubicacion');

        if (formNuevaUbicacion) {
            formNuevaUbicacion.addEventListener('submit', (e) => this.procesarCrearUbicacion(e));
        }
        */

        // Botón para capturar coordenadas desde el GPS del celular
        const btnUsarGps = document.getElementById('btn-usar-gps');
        if (btnUsarGps) {
            btnUsarGps.addEventListener('click', async () => {
                const textoOriginal = btnUsarGps.textContent;
                btnUsarGps.disabled = true;
                btnUsarGps.textContent = '📡 Obteniendo señal GPS...';

                try {
                    const coords = await this.gestorMapa.obtenerUbicacionGps();
                    const inputLat = document.getElementById('ubicacion-latitud');
                    const inputLng = document.getElementById('ubicacion-longitud');

                    if (inputLat) inputLat.value = coords.latitud;
                    if (inputLng) inputLng.value = coords.longitud;

                    this.mostrarAlertaModal('modal-nueva-ubicacion', '¡Ubicación GPS capturada con éxito!', true);
                } catch (error) {
                    this.mostrarAlertaModal('modal-nueva-ubicacion', error.message, false);
                } finally {
                    btnUsarGps.disabled = false;
                    btnUsarGps.textContent = textoOriginal;
                }
            });
        }

    }

    async procesarEliminarUbicacion(idUbicacion) {
        if (!confirm('¿Estás seguro de que deseas eliminar esta ubicación de tu mapa?')) {
            // Re-renderizar para restaurar el estado del botón si cancela
            const resActual = await this.servicioUbicacion.listarPropias();
            this.gestorMapa.cargarVistaPrivada(resActual.ubicaciones || [], this.usuarioActual.apodo_usuario);
            return;
        }

        try {
            const respuesta = await this.servicioUbicacion.eliminar(idUbicacion);
            this.mostrarAlertaGlobal(respuesta.message, true);

            // Actualizar los marcadores del usuario en tiempo real
            const res = await this.servicioUbicacion.listarPropias();
            this.gestorMapa.cargarVistaPrivada(res.ubicaciones || [], this.usuarioActual.apodo_usuario);
        } catch (error) {
            this.mostrarAlertaGlobal(error.message, false);
        }
    }

    /**
     * Cambia la interfaz entre modo Invitado (Landing Page) y modo Autenticado (Privado).
     *
     * @param {object|null} usuario Datos del usuario logueado o null si es invitado.
     */
    async actualizarEstadoSesion(usuario) {
        this.usuarioActual = usuario;

        const zonaInvitado = document.getElementById('nav-invitado');
        const zonaAutenticado = document.getElementById('nav-autenticado');
        const saludo = document.getElementById('saludo-usuario');

        if (usuario) {
            if (zonaInvitado) zonaInvitado.classList.add('oculto');
            if (zonaAutenticado) zonaAutenticado.classList.remove('oculto');
            if (saludo) saludo.textContent = `Hola, ${usuario.apodo_usuario}`;

            const res = await this.servicioUbicacion.listarPropias();
            this.gestorMapa.cargarVistaPrivada(res.ubicaciones || [], usuario.apodo_usuario);
        } else {
            if (zonaAutenticado) zonaAutenticado.classList.add('oculto');
            if (zonaInvitado) zonaInvitado.classList.remove('oculto');
            if (saludo) saludo.textContent = '';

            this.alternarPanelPerfil(false);

            const res = await this.servicioUbicacion.listarPublicas();
            this.gestorMapa.cargarVistaPublica(res.ubicaciones || []);
        }
    }


    // ========================================================================
    // PROCESADORES DE FORMULARIOS (CONEXIÓN CON SERVICIOS)
    // ========================================================================

    async procesarLogin(form) {
        const correo = form.correo_usuario.value.trim();
        const contrasena = form.contrasena.value;

        const respuesta = await this.servicioAuth.iniciarSesion(correo, contrasena);
        form.reset();
        this.cerrarModal('modal-login');
        this.actualizarEstadoSesion(respuesta.usuario);
        this.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarLogout() {
        try {
            const respuesta = await this.servicioAuth.cerrarSesion();
            this.actualizarEstadoSesion(null);
            this.mostrarAlertaGlobal(respuesta.message, true);
        } catch (error) {
            this.mostrarAlertaGlobal(error.message, false);
        }
    }

    async procesarRegistro(form) {
        const apodo = form.apodo_usuario.value.trim();
        const correo = form.correo_usuario.value.trim();
        const contrasena = form.contrasena_hash_usuario.value;
        const confirmar = form.confirmar_contrasena.value;

        if (contrasena !== confirmar) {
            throw new Error('Las contraseñas ingresadas no coinciden.');
        }

        const respuesta = await this.servicioUsuario.registrar(apodo, correo, contrasena);
        form.reset();
        this.cerrarModal('modal-registro');
        this.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarSolicitarRecuperacion(form) {
        const correo = form.correo_usuario.value.trim();
        const respuesta = await this.servicioUsuario.solicitarRecuperacion(correo);
        form.reset();
        this.cerrarModal('modal-recuperar');
        this.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarRestablecerContrasena(form) {
        const token = form.token.value.trim();
        const nuevaClave = form.nueva_contrasena.value;
        const confirmar = form.confirmar_nueva_contrasena.value;

        if (nuevaClave !== confirmar) {
            throw new Error('Las contraseñas ingresadas no coinciden.');
        }

        const respuesta = await this.servicioUsuario.restablecerContrasena(token, nuevaClave);
        form.reset();
        this.cerrarModal('modal-restablecer');
        this.mostrarAlertaGlobal(respuesta.message, true);
        this.abrirModal('modal-login');
    }

    async procesarActualizarApodo(form) {
        const nuevoApodo = form.nuevo_apodo.value.trim();
        const respuesta = await this.servicioUsuario.actualizarApodo(nuevoApodo);

        if (this.usuarioActual) {
            this.usuarioActual.apodo_usuario = nuevoApodo;
            this.actualizarEstadoSesion(this.usuarioActual);
        }

        form.reset();
        this.cerrarModal('modal-editar-apodo');
        this.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarActualizarCorreo(form) {
        const nuevoCorreo = form.nuevo_correo.value.trim();
        const respuesta = await this.servicioUsuario.actualizarCorreo(nuevoCorreo);

        form.reset();
        this.cerrarModal('modal-editar-correo');
        this.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarCambiarContrasena(form) {
        const actual = form.contrasena_actual.value;
        const nueva = form.contrasena_nueva.value;
        const confirmar = form.confirmar_contrasena_nueva.value;

        if (nueva !== confirmar) {
            throw new Error('La nueva contraseña y su confirmación no coinciden.');
        }

        const respuesta = await this.servicioUsuario.cambiarContrasena(actual, nueva);
        form.reset();
        this.cerrarModal('modal-cambiar-clave');
        this.mostrarAlertaGlobal(respuesta.message, true);
    }

    async procesarEliminarCuenta(form) {
        const clave = form.contrasena_confirmacion.value;
        const respuesta = await this.servicioUsuario.eliminarCuenta(clave);

        form.reset();
        this.cerrarModal('modal-eliminar-cuenta');
        this.actualizarEstadoSesion(null);
        this.mostrarAlertaGlobal(respuesta.message, true);
    }


    //=========================================================================
    // CREAR UBICACIONES
    //=========================================================================
    async procesarCrearUbicacion(form) {
        const titulo = form.titulo.value.trim();
        const descripcion = form.descripcion.value.trim();
        const latitud = parseFloat(form.latitud.value);
        const longitud = parseFloat(form.longitud.value);

        const respuesta = await this.servicioUbicacion.crear(titulo, descripcion, latitud, longitud);
        form.reset();
        this.cerrarModal('modal-nueva-ubicacion');
        this.mostrarAlertaGlobal(respuesta.message, true);

        // Recargar pines del usuario en el mapa
        const res = await this.servicioUbicacion.listarPropias();
        this.gestorMapa.cargarVistaPrivada(res.ubicaciones || [], this.usuarioActual.apodo_usuario);
    }

    /**
     * Rellena el formulario de nueva ubicación y abre el modal si hay sesión activa.
     *
     * @param {{ lat: number, lng: number }} coordenadas
     */
    manejarClicEnMapa({ lat, lng }) {
        // Si es un visitante invitado (no logueado), ignoramos el clic en el mapa
        if (!this.usuarioActual) {
            return;
        }

        const inputLat = document.getElementById('ubicacion-latitud');
        const inputLng = document.getElementById('ubicacion-longitud');

        if (inputLat) inputLat.value = lat;
        if (inputLng) inputLng.value = lng;

        this.abrirModal('modal-nueva-ubicacion');
    }




    // ========================================================================
    // UTILIDADES DEL DOM (MODALES, PANEL Y ALERTAS)
    // ========================================================================

    enlazarClick(idElemento, accion) {
        const elemento = document.getElementById(idElemento);
        if (elemento) {
            elemento.addEventListener('click', accion);
        }
    }

    enlazarFormulario(idFormulario, manejadorAsync) {
        const formulario = document.getElementById(idFormulario);
        if (!formulario) return;

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const contenedorAlerta = formulario.querySelector('.alerta-modal');
            const botonSubmit = formulario.querySelector('button[type="submit"]');

            if (contenedorAlerta) {
                contenedorAlerta.textContent = '';
                contenedorAlerta.className = 'alerta-modal';
            }

            try {
                if (botonSubmit) botonSubmit.disabled = true;
                await manejadorAsync(formulario);
            } catch (error) {
                if (contenedorAlerta) {
                    contenedorAlerta.textContent = error.message;
                    contenedorAlerta.className = 'alerta-modal alerta-error';
                } else {
                    this.mostrarAlertaGlobal(error.message, false);
                }
            } finally {
                if (botonSubmit) botonSubmit.disabled = false;
            }
        });
    }
    abrirModal(idModal) {
        const modal = document.getElementById(idModal);
        if (modal && typeof modal.showModal === 'function') {
            const alerta = modal.querySelector('.alerta-modal');
            if (alerta) {
                alerta.textContent = '';
                alerta.className = 'alerta-modal';
            }
            modal.showModal();
        }
    }
    cerrarModal(idModal) {
        const modal = document.getElementById(idModal);
        if (modal && modal.open) {
            modal.close();
        }
    }
    alternarPanelPerfil(abrir) {
        const panel = document.getElementById('panel-perfil');
        if (!panel) return;
        panel.classList.toggle('abierto', abrir);
    }
    mostrarAlertaGlobal(mensaje, esExito = true) {
        const contenedor = document.getElementById('alerta-global');
        if (!contenedor) return;
        contenedor.textContent = mensaje;
        contenedor.className = `alerta-global ${esExito ? 'alerta-exito' : 'alerta-error'}`;

        setTimeout(() => {
            contenedor.className = 'alerta-global oculto';
            contenedor.textContent = '';
        }, 5500);
    }

    mostrarAlertaModal(idModal, mensaje, esExito = true) {
        const modal = document.getElementById(idModal);
        if (!modal) return;

        const contenedor = modal.querySelector('.alerta-modal');
        if (!contenedor) return;

        contenedor.textContent = mensaje;
        contenedor.className = `alerta-modal ${esExito ? 'alerta-exito' : 'alerta-error'}`;
    }

    async cargarMarcadoresEnMapa() {
        const datosPublicas = await this.servicioUbicacion.listarPublicas();

        if (datosPublicas?.geocerca && this.gestorMapa) {
            const { lat, lng, radioKm } = datosPublicas.geocerca;
            this.gestorMapa.actualizarGeocerca(lat, lng, radioKm);
        }

    }

};

