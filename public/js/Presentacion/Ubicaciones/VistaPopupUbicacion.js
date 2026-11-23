export class VistaPopupUbicacion {
    construir(ubicacion, mostrarAutor, alEliminar) {
        const contenedor = document.createElement('div');
        contenedor.style.minWidth = '180px';

        const titulo = document.createElement('h3');
        titulo.style.fontSize = '1rem';
        titulo.style.marginBottom = '0.25rem';
        titulo.textContent = ubicacion.titulo || 'Ubicación sin título';
        contenedor.appendChild(titulo);

        if (ubicacion.descripcion) {
            const descripcion = document.createElement('p');
            descripcion.style.fontSize = '0.85rem';
            descripcion.style.marginBottom = '0.5rem';
            descripcion.textContent = ubicacion.descripcion;
            contenedor.appendChild(descripcion);
        }

        if (mostrarAutor && ubicacion.apodo_usuario) {
            const autor = document.createElement('small');
            autor.style.color = '#4b5563';
            autor.textContent = `Compartido por: ${ubicacion.apodo_usuario}`;
            contenedor.appendChild(autor);
        }

        if (!mostrarAutor && ubicacion.id_ubicacion && typeof alEliminar === 'function') {
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
                alEliminar(ubicacion.id_ubicacion);
            });
            contenedor.appendChild(botonEliminar);
        }

        return contenedor;
    }
}
