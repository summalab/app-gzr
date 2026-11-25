const ROLES = new Set(['todos', 'visitante', 'usuario', 'moderador', 'administrador']);

export class PoliticaAccesoUbicacionTiempoReal {
    constructor(habilitada, rolPermitido) {
        if (typeof habilitada !== 'boolean' || !ROLES.has(rolPermitido)) {
            throw new Error('La política de ubicación en tiempo real recibida no es válida.');
        }

        this.habilitada = habilitada;
        this.rolPermitido = rolPermitido;
    }

    permite(usuario) {
        if (!this.habilitada) return false;
        if (this.rolPermitido === 'todos') return true;

        const rol = usuario?.rol_usuario
            ? String(usuario.rol_usuario).toLowerCase()
            : 'visitante';
        return rol === this.rolPermitido;
    }
}
