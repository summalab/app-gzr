import { HttpApiClient } from './Shared/HttpApiClient.js';
import { ServicioAuth } from './Auth/ServicioAuth.js';
import { ServicioUsuario } from './Usuarios/ServicioUsuario.js';
import { GestorMapaLeaflet } from './Ubicaciones/GestorMapaLeaflet.js';
import { ControladorInterfaz } from './ControladorInterfaz.js';
import { ServicioUbicacion } from './Ubicaciones/ServicioUbicacion.js';

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Inicializar dependencias principales (Inyección de Dependencias manual en JS)
    const clienteHttp = new HttpApiClient();
    const servicioAuth = new ServicioAuth(clienteHttp);
    const servicioUsuario = new ServicioUsuario(clienteHttp);
    const gestorMapa = new GestorMapaLeaflet('mapa-leaflet', 'insignia-vista');
    const servicioUbicacion = new ServicioUbicacion(clienteHttp);
    const interfaz = new ControladorInterfaz(servicioAuth, servicioUsuario, gestorMapa, servicioUbicacion);

    // 2. Encender el lienzo del mapa con soporte de clic directo y enlazar eventos
    gestorMapa.inicializar(
        [4.8133, -75.6961],
        6,
        (coordenadas) => interfaz.manejarClicEnMapa(coordenadas)
    );
    interfaz.inicializarEventos();



    // 3. Revisar si la URL contiene enlaces mágicos desde el correo (?token= o ?recuperar=)
    const parametrosUrl = new URLSearchParams(window.location.search);
    const tokenVerificacion = parametrosUrl.get('token');
    const tokenRecuperacion = parametrosUrl.get('recuperar');

    if (tokenVerificacion) {
        try {
            const respuesta = await servicioUsuario.verificarCorreo(tokenVerificacion);
            interfaz.mostrarAlertaGlobal(respuesta.message, true);
            interfaz.abrirModal('modal-login');
        } catch (error) {
            interfaz.mostrarAlertaGlobal(error.message, false);
        } finally {
            limpiarUrl();
        }
    } else if (tokenRecuperacion) {
        const inputToken = document.getElementById('token-recuperacion');
        if (inputToken) {
            inputToken.value = tokenRecuperacion;
        }
        interfaz.abrirModal('modal-restablecer');
        limpiarUrl();
    }

    // 4. Consultar al servidor si el usuario ya tiene una sesión activa
    try {
        const estado = await servicioAuth.verificarSesion();
        if (estado.autenticado && estado.usuario) {
            interfaz.actualizarEstadoSesion(estado.usuario);
        } else {
            interfaz.actualizarEstadoSesion(null);
        }
    } catch (error) {
        console.error('Error al verificar la sesión inicial:', error);
        interfaz.actualizarEstadoSesion(null);
    }
});

/**
 * Elimina los parámetros ?token= o ?recuperar= de la barra de direcciones
 * sin recargar la página para evitar reenvíos accidentales.
 */
function limpiarUrl() {
    const urlLimpia = window.location.origin + window.location.pathname;
    window.history.replaceState({}, document.title, urlLimpia);
}
