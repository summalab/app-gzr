import { GeolocalizadorNavegador } from './GeolocalizadorNavegador.js';

export class LeafletMapAdapter {
    constructor(
        idContenedorMapa = 'mapa-leaflet',
        idInsigniaVista = 'insignia-vista',
        geolocalizador = new GeolocalizadorNavegador(),
        vistaPopup
    ) {
        if (!vistaPopup) {
            throw new Error('LeafletMapAdapter requiere una vista para los popups.');
        }
        this.idContenedorMapa = idContenedorMapa;
        this.idInsigniaVista = idInsigniaVista;
        this.geolocalizador = geolocalizador;
        this.vistaPopup = vistaPopup;
        this.mapa = null;
        this.capaMarcadores = null;
        this.capaGeocerca = null;
        this.alEliminarUbicacion = null;
    }

    establecerManejadorEliminacion(manejador) {
        this.alEliminarUbicacion = manejador;
    }

    inicializar(coordenadasCentro = [4.8133, -75.6961], zoomInicial = 11, alHacerClic = null) {
        const contenedor = document.getElementById(this.idContenedorMapa);
        if (!contenedor || typeof L === 'undefined') {
            console.error('Error crítico: Contenedor de mapa no encontrado o librería Leaflet no cargada.');
            return;
        }

        const limitesRegion = L.latLngBounds([4.20, -76.35], [5.40, -75.05]);
        this.mapa = L.map(this.idContenedorMapa, {
            maxBounds: limitesRegion,
            maxBoundsViscosity: 0.85,
            minZoom: 9
        }).setView(coordenadasCentro, zoomInicial);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.mapa);
        this.capaMarcadores = L.layerGroup().addTo(this.mapa);

        if (typeof alHacerClic === 'function') {
            this.mapa.on('click', (evento) => {
                alHacerClic({
                    lat: Number(evento.latlng.lat.toFixed(6)),
                    lng: Number(evento.latlng.lng.toFixed(6))
                });
            });
        }
    }

    actualizarGeocerca(lat, lng, radioKm, modo = 'circular') {
        if (!this.mapa) return;

        if (this.capaGeocerca) {
            this.mapa.removeLayer(this.capaGeocerca);
            this.capaGeocerca = null;
        }

        if (modo === 'nacional') {
            const limitesColombia = L.latLngBounds([-4.50, -82.00], [13.80, -66.50]);
            this.mapa.setMaxBounds(limitesColombia);
            this.mapa.setMinZoom(5);
            this.mapa.setView([4.5709, -74.2973], 6);
            return;
        }

        this.mapa.setMinZoom(9);
        this.mapa.setView([lat, lng]);
        this.capaGeocerca = L.circle([lat, lng], {
            radius: radioKm * 1000,
            color: '#2563eb',
            weight: 1.5,
            fillColor: '#3b82f6',
            fillOpacity: 0.06,
            interactive: false
        }).addTo(this.mapa);

        const margenGrados = (radioKm / 111) * 1.6;
        this.mapa.setMaxBounds(L.latLngBounds(
            [lat - margenGrados, lng - margenGrados],
            [lat + margenGrados, lng + margenGrados]
        ));
    }

    cargarVistaPublica(ubicaciones = []) {
        this.actualizarInsignia('🌍 Vista Pública: Ubicaciones de toda la comunidad');
        this.renderizarMarcadores(ubicaciones, true);
    }

    cargarVistaPrivada(ubicaciones = [], apodoUsuario = '') {
        const etiqueta = apodoUsuario
            ? `📍 Mis Ubicaciones (${apodoUsuario})`
            : '📍 Mis Ubicaciones Privadas';
        this.actualizarInsignia(etiqueta);
        this.renderizarMarcadores(ubicaciones, false);
    }

    renderizarMarcadores(ubicaciones, mostrarAutor = true) {
        if (!this.mapa || !this.capaMarcadores) return;
        this.capaMarcadores.clearLayers();
        if (!Array.isArray(ubicaciones) || ubicaciones.length === 0) return;

        const limites = [];
        ubicaciones.forEach((ubicacion) => {
            const lat = Number.parseFloat(ubicacion.latitud);
            const lng = Number.parseFloat(ubicacion.longitud);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const marcador = L.marker([lat, lng]);
            marcador.bindPopup(this.vistaPopup.construir(
                ubicacion,
                mostrarAutor,
                this.alEliminarUbicacion
            ));
            marcador.addTo(this.capaMarcadores);
            limites.push([lat, lng]);
        });

        if (limites.length > 0) {
            this.mapa.fitBounds(limites, { padding: [40, 40], maxZoom: 14 });
        }
    }

    async obtenerUbicacionGps() {
        const coordenadas = await this.geolocalizador.obtenerUbicacion();
        this.mapa?.setView([coordenadas.latitud, coordenadas.longitud], 16);
        return coordenadas;
    }

    actualizarInsignia(texto) {
        const insignia = document.getElementById(this.idInsigniaVista);
        if (insignia) insignia.textContent = texto;
    }
}
