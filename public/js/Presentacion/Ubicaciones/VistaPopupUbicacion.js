import { TEXTOS_ES } from '../../textos-es.js';

export class VistaPopupUbicacion {
    construir(ubicacion, mostrarAutor, alEliminar, alEditar) {
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
            ['Tipo de infraestructura', ubicacion.tipo_infraestructura],
            ['Estado de infraestructura', ubicacion.estado_infraestructura],
            ['Riesgos directos', ubicacion.riesgos_directos],
            ['Riesgos indirectos', ubicacion.riesgos_indirectos],
            ['Actividades delictivas', ubicacion.actividades_delictivas],
            ['Condiciones sociales', ubicacion.condiciones_sociales]
        ].filter(([, valor]) => typeof valor === 'string' && valor.trim() !== '');

        if (clasificaciones.length > 0) {
            const detalle = document.createElement('ul');
            detalle.className = 'popup-clasificaciones';

            for (const [etiqueta, valor] of clasificaciones) {
                const elemento = document.createElement('li');
                const nombre = document.createElement('strong');
                nombre.textContent = `${etiqueta}: `;
                elemento.append(nombre, valor.replace(/_/g, ' '));
                detalle.appendChild(elemento);
            }

            contenedor.appendChild(detalle);
        }

        if (mostrarAutor && ubicacion.apodo_usuario) {
            const autor = document.createElement('small');
            autor.className = 'popup-autor';
            autor.textContent = `${TEXTOS_ES.compartirAutor}: ${ubicacion.apodo_usuario}`;
            contenedor.appendChild(autor);
        }

        if (!mostrarAutor && ubicacion.id_ubicacion && (
            typeof alEliminar === 'function' || typeof alEditar === 'function'
        )) {
            const accion = document.createElement('contenedor-ui');
            accion.classList.add('accion-popup');

            if (typeof alEditar === 'function') {
                const botonEditar = document.createElement('boton-ui');
                botonEditar.setAttribute('tipo', 'button');
                botonEditar.setAttribute('variante', 'secundario');
                botonEditar.setAttribute('ancho', 'completo');
                botonEditar.textContent = 'Editar ubicación';
                botonEditar.addEventListener('click', () => alEditar(ubicacion));
                accion.appendChild(botonEditar);
            }

            if (typeof alEliminar === 'function') {
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
            }
            contenedor.appendChild(accion);
        }

        return contenedor;
    }
}
