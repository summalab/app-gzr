import { TEXTOS_ES } from '../../textos-es.js';

export class VistaPopupUbicacion {
    construir(ubicacion, mostrarAutor, alEliminar) {
        const contenedor = document.createElement('div');
        contenedor.className = 'popup-ubicacion';

        const titulo = document.createElement('h3');
        titulo.className = 'popup-titulo';
        titulo.textContent = ubicacion.titulo || TEXTOS_ES.ubicacionSinTitulo;
        contenedor.appendChild(titulo);

        if (ubicacion.descripcion) {
            const descripcion = document.createElement('p');
            descripcion.className = 'popup-descripcion';
            descripcion.textContent = ubicacion.descripcion;
            contenedor.appendChild(descripcion);
        }

        const clasificaciones = [
            ubicacion.tipo_infraestructura,
            ubicacion.estado_infraestructura,
            ubicacion.riesgos_directos,
            ubicacion.riesgos_indirectos,
            ubicacion.actividades_delictivas,
            ubicacion.condiciones_sociales
        ].filter(Boolean);

        if (clasificaciones.length > 0) {
            const detalle = document.createElement('small');
            detalle.className = 'popup-clasificaciones';
            detalle.textContent = clasificaciones.join(' | ');
            contenedor.appendChild(detalle);
        }

        if (mostrarAutor && ubicacion.apodo_usuario) {
            const autor = document.createElement('small');
            autor.className = 'popup-autor';
            autor.textContent = `${TEXTOS_ES.compartirAutor}: ${ubicacion.apodo_usuario}`;
            contenedor.appendChild(autor);
        }

        if (!mostrarAutor && ubicacion.id_ubicacion && typeof alEliminar === 'function') {
            const accion = document.createElement('contenedor-ui');
            accion.classList.add('accion-popup');
            const botonEliminar = document.createElement('boton-ui');
            botonEliminar.setAttribute('tipo', 'button');
            botonEliminar.setAttribute('variante', 'peligro');
            botonEliminar.setAttribute('ancho', 'completo');
            botonEliminar.textContent = `🗑️ ${TEXTOS_ES.eliminarUbicacion}`;
            botonEliminar.addEventListener('click', () => {
                botonEliminar.disabled = true;
                botonEliminar.texto = TEXTOS_ES.eliminandoUbicacion;
                alEliminar(ubicacion.id_ubicacion);
            });
            accion.appendChild(botonEliminar);
            contenedor.appendChild(accion);
        }

        return contenedor;
    }
}
