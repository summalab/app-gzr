import { TEXTOS_ES } from '../../textos-es.js';

export class ConstructorDashboard {
    constructor(documento = globalThis.document) {
        this.documento = documento;
    }

    construir(rol) {
        const configuraciones = {
            usuario: {
                nombre: TEXTOS_ES.dashboardUsuario,
                acciones: [
                    ['accion-mis-ubicaciones', TEXTOS_ES.misUbicaciones],
                    ['accion-nueva-ubicacion', TEXTOS_ES.nuevaUbicacion],
                    ['accion-reportar', TEXTOS_ES.reportarUbicacion]
                ]
            },
            moderador: {
                nombre: TEXTOS_ES.dashboardModerador,
                acciones: [
                    ['accion-mis-ubicaciones', TEXTOS_ES.misUbicaciones],
                    ['accion-nueva-ubicacion', TEXTOS_ES.nuevaUbicacion],
                    ['accion-revisar-reportes', TEXTOS_ES.revisarReportes],
                    ['accion-aprobar-ubicaciones', TEXTOS_ES.aprobarUbicaciones]
                ]
            },
            administrador: {
                nombre: TEXTOS_ES.dashboardAdministrador,
                acciones: [
                    ['accion-mis-ubicaciones', TEXTOS_ES.misUbicaciones],
                    ['accion-nueva-ubicacion', TEXTOS_ES.nuevaUbicacion],
                    ['accion-gestion-usuarios', TEXTOS_ES.gestionarUsuarios],
                    ['accion-auditoria', TEXTOS_ES.auditoria],
                    ['accion-configuracion', TEXTOS_ES.configuracion]
                ]
            }
        };

        const configuracion = configuraciones[String(rol).toLowerCase()] || configuraciones.usuario;
        this.documento.getElementById('nombre-dashboard').textContent = configuracion.nombre;

        const contenedor = this.documento.getElementById('acciones-dashboard');
        contenedor.replaceChildren();
        for (const [id, texto] of configuracion.acciones) {
            const boton = this.documento.createElement('boton-ui');
            boton.id = id;
            boton.setAttribute('tipo', 'button');
            boton.setAttribute('variante', 'dashboard');
            boton.textContent = texto;
            contenedor.appendChild(boton);
        }
    }
}
