import { AutenticacionUseCases } from './Aplicacion/Autenticacion/AutenticacionUseCases.js';
import { CuentaUsuarioUseCases } from './Aplicacion/Usuarios/CuentaUsuarioUseCases.js';
import { UbicacionUseCases } from './Aplicacion/Ubicaciones/UbicacionUseCases.js';
import { AuthGateway } from './Infraestructura/Api/AuthGateway.js';
import { UbicacionGateway } from './Infraestructura/Api/UbicacionGateway.js';
import { UsuarioGateway } from './Infraestructura/Api/UsuarioGateway.js';
import { HttpApiClient } from './Infraestructura/Http/HttpApiClient.js';
import { GeolocalizadorNavegador } from './Infraestructura/Map/GeolocalizadorNavegador.js';
import { LeafletMapAdapter } from './Infraestructura/Map/LeafletMapAdapter.js';
import { ControladorAutenticacion } from './Presentacion/Autenticacion/ControladorAutenticacion.js';
import { ControladorPerfilUsuario } from './Presentacion/Usuarios/ControladorPerfilUsuario.js';
import { ControladorUbicaciones } from './Presentacion/Ubicaciones/ControladorUbicaciones.js';
import { VistaInterfaz } from './Presentacion/Shared/VistaInterfaz.js';
import { VistaPopupUbicacion } from './Presentacion/Ubicaciones/VistaPopupUbicacion.js';
import { ControladorInterfaz } from './ControladorInterfaz.js';
import './Presentacion/Componentes/RegistrarComponentesUI.js';
import { CargadorComponentes } from './Presentacion/Componentes/CargadorComponentes.js';
import { GestorTema } from './Presentacion/Shared/GestorTema.js';
import { ConstructorDashboard } from './Presentacion/Shared/ConstructorDashboard.js';

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await new CargadorComponentes().cargar();
    } catch (error) {
        console.error('No se pudo componer la interfaz:', error);
        const aviso = document.createElement('p');
        aviso.className = 'error-carga-componentes';
        aviso.setAttribute('role', 'alert');
        aviso.textContent = 'No se pudo cargar la interfaz. Recarga la página o inténtalo más tarde.';
        document.body.prepend(aviso);
        return;
    }

    const clienteHttp = new HttpApiClient();
    const autenticacion = new AutenticacionUseCases(new AuthGateway(clienteHttp));
    const cuentaUsuario = new CuentaUsuarioUseCases(new UsuarioGateway(clienteHttp));
    const ubicaciones = new UbicacionUseCases(new UbicacionGateway(clienteHttp));
    const vista = new VistaInterfaz();
    const gestorTema = new GestorTema();
    gestorTema.inicializar();
    gestorTema.enlazarControles(vista);
    vista.constructorDashboard = new ConstructorDashboard();
    const popupUbicacion = new VistaPopupUbicacion();
    const mapa = new LeafletMapAdapter(
        'mapa-interactivo',
        'indicador-vista-mapa',
        new GeolocalizadorNavegador(),
        popupUbicacion
    );

    const controladorAutenticacion = new ControladorAutenticacion(autenticacion, cuentaUsuario, vista);
    const controladorPerfil = new ControladorPerfilUsuario(cuentaUsuario, autenticacion, vista);
    const controladorUbicaciones = new ControladorUbicaciones(ubicaciones, mapa, vista);
    const aplicacion = new ControladorInterfaz({
        autenticacion,
        autenticacionController: controladorAutenticacion,
        perfilController: controladorPerfil,
        ubicacionesController: controladorUbicaciones,
        gestorMapa: mapa,
        vista
    });

    aplicacion.inicializar();
    await controladorAutenticacion.procesarEnlaceInicial();
    await aplicacion.restaurarSesion();
});
