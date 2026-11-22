/**
 * Controlador encargado de la lógica visual e interacción con el DOM para Usuarios.
 */
export class ControladorInterfazUsuario {
    /**
     * @param {ServicioUsuario} servicioUsuario 
     */
    constructor(servicioUsuario) {
        this.servicioUsuario = servicioUsuario;
        this.formulario = null;
        this.contenedorMensajes = null;
    }

    /**
     * Inicializa los nodos del DOM y amarra los escuchadores de eventos.
     * 
     * @param {string} idFormulario ID del elemento <form> en el HTML.
     * @param {string} idMensajes ID del contenedor <div> para alertas de éxito/error.
     */
    inicializar(idFormulario, idMensajes) {
        this.formulario = document.getElementById(idFormulario);
        this.contenedorMensajes = document.getElementById(idMensajes);

        if (this.formulario) {
            this.formulario.addEventListener('submit', (evento) => this.procesarEnvioFormulario(evento));
        }
    }

    /**
     * Captura el envío, valida campos vacíos y delega la llamada al servicio lógico.
     */
    async procesarEnvioFormulario(evento) {
        evento.preventDefault(); // Detiene la recarga nativa de la página web
        this.limpiarMensaje();

        const apodo = this.formulario.querySelector('[name="apodo_usuario"]')?.value.trim() || '';
        const correo = this.formulario.querySelector('[name="correo_usuario"]')?.value.trim() || '';
        const contrasena = this.formulario.querySelector('[name="contrasena_hash_usuario"]')?.value || '';
        const confirmarContrasena = this.formulario.querySelector('[name="confirmar_contrasena"]')?.value || '';

        // Validación defensiva en el cliente (Falla Rápida)
        if (apodo === '' || correo === '' || contrasena === '' || confirmarContrasena === '') {
            this.mostrarMensaje('Todos los campos son obligatorios.', false);
            return;
        }

        if (contrasena.length < 8) {
            this.mostrarMensaje('La contraseña debe tener un mínimo de 8 caracteres.', false);
            return;
        }

        if (contrasena !== confirmarContrasena) {
            this.mostrarMensaje('Las contraseñas ingresadas no coinciden. Por favor, verifíquelas.', false);
            return;
        }

        try {
            // Deshabilitar botón para evitar múltiples clics concurrentes
            const botonEnvio = this.formulario.querySelector('button[type="submit"]');
            if (botonEnvio) botonEnvio.disabled = true;

            // Invocamos el caso de uso del frontend
            const respuesta = await this.servicioUsuario.registrar(apodo, correo, contrasena);

            if (respuesta.success) {
                this.mostrarMensaje(respuesta.message, true);
                this.formulario.reset(); // Limpia los campos del formulario tras el éxito
            }

        } catch (error) {
            // Captura los errores controlados devueltos por el backend (400, 422, etc.)
            this.mostrarMensaje(error.message, false);
        } finally {
            // Rehabilitar el botón pase lo que pase
            const botonEnvio = this.formulario.querySelector('button[type="submit"]');
            if (botonEnvio) botonEnvio.disabled = false;
        }
    }

    /**
     * Renderiza una alerta visual dinámica en el DOM utilizando clases de estilo nativas.
     */
    mostrarMensaje(texto, esExito) {
        if (!this.contenedorMensajes) return;

        this.contenedorMensajes.textContent = texto;
        this.contenedorMensajes.className = esExito 
            ? 'alerta-exito' // Clase CSS nativa para estados correctos
            : 'alerta-error'; // Clase CSS nativa para estados fallidos
    }

    /**
     * Limpia los textos e hilos de alertas previos.
     */
    limpiarMensaje() {
        if (!this.contenedorMensajes) return;
        this.contenedorMensajes.textContent = '';
        this.contenedorMensajes.className = '';
    }
}
