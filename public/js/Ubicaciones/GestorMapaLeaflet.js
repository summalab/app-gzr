/**
 * Módulo encargado de encapsular la lógica del mapa interactivo con Leaflet.js.
 * Cumple con el Principio de Responsabilidad Única (SRP).
 */
export class GestorMapaLeaflet {
    /**
     * @param {string} idContenedorMapa ID del elemento <div> donde se renderiza el mapa.
     * @param {string} idInsigniaVista ID del elemento indicador de modo (Público / Privado).
     */
    // 1. Agrega en el constructor de GestorMapaLeaflet:
    constructor(idContenedorMapa = 'mapa-leaflet', idInsigniaVista = 'insignia-vista') {
        this.idContenedorMapa = idContenedorMapa;
        this.idInsigniaVista = idInsigniaVista;
        this.mapa = null;
        this.capaMarcadores = null;
        this.alEliminarUbicacion = null;
    }


    // 2. Agrega este método nuevo en GestorMapaLeaflet:
    establecerManejadorEliminacion(callback) {
        this.alEliminarUbicacion = callback;
    }


    /**
     * Inicializa el lienzo de Leaflet centrado en coordenadas base.
     *
     * @param {number[]} coordenadasCentro [latitud, longitud]
     * @param {number} zoomInicial Nivel de acercamiento inicial.
     * @param {Function|null} alHacerClicEnMapa Callback que recibe ({ lat, lng }) al hacer clic.
     */
    inicializar(coordenadasCentro = [4.8133, -75.6961], zoomInicial = 11, alHacerClicEnMapa = null) {
        // Valida la existencia del contenedor y Leaflet
        const contenedor = document.getElementById(this.idContenedorMapa);
        if (!contenedor || typeof L === 'undefined') {
            console.error('Error crítico: Contenedor de mapa no encontrado o librería Leaflet no cargada.');
            return;
        }

        const limitesRegion = L.latLngBounds([4.20, -76.35], [5.40, -75.05]);

        // 1. Instancia el mapa con maxBounds y minZoom configurados
        this.mapa = L.map(this.idContenedorMapa, {
            maxBounds: limitesRegion,
            maxBoundsViscosity: 0.85,
            minZoom: 9
        }).setView(coordenadasCentro, zoomInicial);

        // 2. Agrega la capa base de OpenStreetMap y el círculo de radio (40 km)
        // 2. Cargar teselas gratuitas de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.mapa);
        L.circle([4.8133, -75.6961], { radius: 40000, color: '#2563eb', interactive: false }).addTo(this.mapa);

        // 3. Inicializa grupo de capas y escucha eventos de clic
        this.capaMarcadores = L.layerGroup().addTo(this.mapa);
        // 4. Escuchar clics directos sobre el mapa y delegar coordenadas
        if (typeof alHacerClicEnMapa === 'function') {
            this.mapa.on('click', (evento) => {
                alHacerClicEnMapa({
                    lat: Number(evento.latlng.lat.toFixed(6)),
                    lng: Number(evento.latlng.lng.toFixed(6))
                });
            });
        }
    }



    /**
     * Configura el mapa en modo Landing Page (Invitado) y pinta ubicaciones comunitarias.
     *
     * @param {Array<object>} ubicaciones Lista de ubicaciones públicas.
     */
    cargarVistaPublica(ubicaciones = []) {
        this.actualizarInsignia('🌍 Vista Pública: Ubicaciones de toda la comunidad');
        this.renderizarMarcadores(ubicaciones, true);
    }

    /**
     * Configura el mapa en modo Panel Privado (Usuario Autenticado) y pinta solo sus ubicaciones.
     *
     * @param {Array<object>} ubicacionesPropias Lista de ubicaciones del usuario logueado.
     * @param {string} apodoUsuario Nombre del usuario autenticado.
     */
    cargarVistaPrivada(ubicacionesPropias = [], apodoUsuario = '') {
        const etiqueta = apodoUsuario
            ? `📍 Mis Ubicaciones (${apodoUsuario})`
            : '📍 Mis Ubicaciones Privadas';

        this.actualizarInsignia(etiqueta);
        this.renderizarMarcadores(ubicacionesPropias, false);
    }

    /**
     * Limpia los marcadores anteriores y dibuja los nuevos en el mapa.
     *
     * @param {Array<object>} ubicaciones
     * @param {boolean} mostrarAutor Indica si debe mostrarse el apodo del creador en el popup.
     */
    renderizarMarcadores(ubicaciones, mostrarAutor = true) {
        if (!this.mapa || !this.capaMarcadores) return;

        // Limpiar pines anteriores
        this.capaMarcadores.clearLayers();

        if (!Array.isArray(ubicaciones) || ubicaciones.length === 0) {
            return;
        }

        const limites = [];

        ubicaciones.forEach((ubicacion) => {
            const lat = parseFloat(ubicacion.latitud);
            const lng = parseFloat(ubicacion.longitud);

            if (Number.isNaN(lat) || Number.isNaN(lng)) return;

            const marcador = L.marker([lat, lng]);
            const contenidoPopup = this.construirPopupSeguro(ubicacion, mostrarAutor);

            marcador.bindPopup(contenidoPopup);
            marcador.addTo(this.capaMarcadores);
            limites.push([lat, lng]);
        });

        // Ajustar encuadre automático si hay ubicaciones válidas
        if (limites.length > 0) {
            this.mapa.fitBounds(limites, { padding: [40, 40], maxZoom: 14 });
        }
    }

    /**
     * Construye el nodo DOM del popup evitando inyección XSS mediante textContent.
     *
     * @param {object} ubicacion
     * @param {boolean} mostrarAutor
     * @returns {HTMLElement}
     */
    // 3. Reemplaza construirPopupSeguro() por esta versión con botón seguro:
    construirPopupSeguro(ubicacion, mostrarAutor) {
        const contenedor = document.createElement('div');
        contenedor.style.minWidth = '180px';

        const titulo = document.createElement('h3');
        titulo.style.fontSize = '1rem';
        titulo.style.marginBottom = '0.25rem';
        titulo.textContent = ubicacion.titulo || 'Ubicación sin título';
        contenedor.appendChild(titulo);

        if (ubicacion.descripcion) {
            const desc = document.createElement('p');
            desc.style.fontSize = '0.85rem';
            desc.style.marginBottom = '0.5rem';
            desc.textContent = ubicacion.descripcion;
            contenedor.appendChild(desc);
        }

        if (mostrarAutor && ubicacion.apodo_usuario) {
            const autor = document.createElement('small');
            autor.style.color = '#4b5563';
            autor.textContent = `Compartido por: ${ubicacion.apodo_usuario}`;
            contenedor.appendChild(autor);
        }

        // Vista Privada: agregar botón de eliminar si existe ID y callback configurado
        if (!mostrarAutor && ubicacion.id_ubicacion && typeof this.alEliminarUbicacion === 'function') {
            const botonEliminar = document.createElement('button');
            botonEliminar.type = 'button';
            botonEliminar.textContent = '🗑️ Eliminar ubicación';
            botonEliminar.className = 'btn btn-peligro';
            botonEliminar.style.marginTop = '0.5rem';
            botonEliminar.style.width = '100%';
            botonEliminar.style.padding = '0.35rem 0.6rem';
            botonEliminar.style.fontSize = '0.8rem';

            botonEliminar.addEventListener('click', () => {
                botonEliminar.disabled = true;
                botonEliminar.textContent = 'Eliminando...';
                this.alEliminarUbicacion(ubicacion.id_ubicacion);
            });

            contenedor.appendChild(botonEliminar);
        }

        return contenedor;
    }

    /**
 * Solicita las coordenadas GPS actuales al dispositivo móvil o navegador.
 *
 * @returns {Promise<{latitud: number, longitud: number}>}
 */
    obtenerUbicacionGps() {
        return new Promise((resolve, reject) => {
            if (!('geolocation' in navigator)) {
                reject(new Error('Tu navegador o dispositivo no soporta geolocalización GPS.'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (posicion) => {
                    const latitud = Number(posicion.coords.latitude.toFixed(6));
                    const longitud = Number(posicion.coords.longitude.toFixed(6));

                    if (this.mapa) {
                        this.mapa.setView([latitud, longitud], 16);
                    }

                    resolve({ latitud, longitud });
                },
                (error) => {
                    let mensaje = 'No se pudo obtener tu ubicación GPS.';
                    if (error.code === error.PERMISSION_DENIED) {
                        mensaje = 'Permiso de ubicación denegado. Activa el GPS y permite el acceso en tu navegador.';
                    } else if (error.code === error.TIMEOUT) {
                        mensaje = 'Se agotó el tiempo esperando señal GPS. Intenta en un espacio más abierto.';
                    }
                    reject(new Error(mensaje));
                },
                {
                    enableHighAccuracy: true, // Prioriza el chip GPS real sobre antenas Wi-Fi
                    timeout: 10000,           // Máximo 10 segundos de espera
                    maximumAge: 0             // No usar ubicaciones viejas en caché
                }
            );
        });
    }


    /**
     * Actualiza el texto de la insignia flotante inferior.
     *
     * @param {string} texto
     */
    actualizarInsignia(texto) {
        const insignia = document.getElementById(this.idInsigniaVista);
        if (insignia) {
            insignia.textContent = texto;
        }
    }
}
