export class VistaPopupUbicacion {
    construir(ubicacion, mostrarAutor, alEliminar) {
        const contenedor = document.createElement('div');
        contenedor.className = 'popup-ubicacion';

        const titulo = document.createElement('h3');
        titulo.className = 'popup-titulo';
        titulo.textContent = ubicacion.titulo || 'Ubicación sin título';
        contenedor.appendChild(titulo);

        if (ubicacion.descripcion) {
            const descripcion = document.createElement('p');
            descripcion.className = 'popup-descripcion';
            descripcion.textContent = ubicacion.descripcion;
            contenedor.appendChild(descripcion);
        }

        if (mostrarAutor && ubicacion.apodo_usuario) {
            const autor = document.createElement('small');
            autor.className = 'popup-autor';
            autor.textContent = `Compartido por: ${ubicacion.apodo_usuario}`;
            contenedor.appendChild(autor);
        }

        if (!mostrarAutor && ubicacion.id_ubicacion && typeof alEliminar === 'function') {
            const accion = document.createElement('contenedor-ui');
            accion.classList.add('accion-popup');
            const botonEliminar = document.createElement('boton-ui');
            botonEliminar.setAttribute('tipo', 'button');
            botonEliminar.setAttribute('variante', 'peligro');
            botonEliminar.setAttribute('ancho', 'completo');
            botonEliminar.textContent = '🗑️ Eliminar ubicación';
            botonEliminar.addEventListener('click', () => {
                botonEliminar.disabled = true;
                botonEliminar.texto = 'Eliminando...';
                alEliminar(ubicacion.id_ubicacion);
            });
            accion.appendChild(botonEliminar);
            contenedor.appendChild(accion);
        }

        return contenedor;
    }
}
