export class ConstructorDashboard {
    constructor(documento = globalThis.document) {
        this.documento = documento;
    }

    construir(rol) {
        const configuraciones = {
            usuario: {
                nombre: 'Dashboard de usuario',
                acciones: [
                    ['accion-mis-ubicaciones', '📍 Mis ubicaciones'],
                    ['accion-nueva-ubicacion', '＋ Nueva ubicación'],
                    ['accion-reportar', '⚑ Reportar ubicación']
                ]
            },
            moderador: {
                nombre: 'Dashboard de moderación',
                acciones: [
                    ['accion-mis-ubicaciones', '📍 Mis ubicaciones'],
                    ['accion-nueva-ubicacion', '＋ Nueva ubicación'],
                    ['accion-revisar-reportes', '🛡 Revisar reportes'],
                    ['accion-aprobar-ubicaciones', '✓ Aprobar ubicaciones']
                ]
            },
            administrador: {
                nombre: 'Dashboard administrativo',
                acciones: [
                    ['accion-mis-ubicaciones', '📍 Mis ubicaciones'],
                    ['accion-nueva-ubicacion', '＋ Nueva ubicación'],
                    ['accion-gestion-usuarios', '👥 Gestionar usuarios'],
                    ['accion-auditoria', '📋 Auditoría'],
                    ['accion-configuracion', '⚙ Configuración']
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
