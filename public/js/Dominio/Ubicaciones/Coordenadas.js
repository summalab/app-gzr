export class Coordenadas {
    constructor(latitud, longitud) {
        if (!Number.isFinite(latitud) || latitud < -90 || latitud > 90) {
            throw new Error('La latitud debe estar entre -90 y 90 grados.');
        }

        if (!Number.isFinite(longitud) || longitud < -180 || longitud > 180) {
            throw new Error('La longitud debe estar entre -180 y 180 grados.');
        }

        this.latitud = latitud;
        this.longitud = longitud;
        Object.freeze(this);
    }
}
