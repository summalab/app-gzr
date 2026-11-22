/**
 * Cliente HTTP unificado utilizando la API nativa fetch().
 * Sigue el principio de Responsabilidad Única (S en SOLID).
 */
export class HttpApiClient {
    /**
     * @param {string} baseUrl Ruta base del servidor PHP (ej: 'http://localhost/api').
     */
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl.replace(/\/$/, ''); // Limpia barras finales automáticas
    }

    /**
     * Realiza una petición asíncrona genérica encapsulando el manejo de errores de red.
     * 
     * @param {string} endpoint El segmento de la URL al que se apunta (ej: '/register').
     * @param {RequestInit} options Opciones de configuración nativas de fetch.
     * @returns {Promise<any>} Datos convertidos automáticamente de JSON.
     * @throws {Error} Si el servidor responde con un código de error o falla la red.
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint.replace(/^\//, '')}`;

        const configuracionFetch = {
            credentials: 'same-origin',
            ...options
        };

        try {
            const response = await fetch(url, configuracionFetch);

            // Intentamos parsear la respuesta como JSON nativo
            const data = await response.json();

            // Principio de Falla Rápida: Si el código HTTP no está en el rango 200-299
            if (!response.ok) {
                // Si el backend envió un mensaje de error estructurado, lo usamos
                throw new Error(data.error || `Error en el servidor web (Código: ${response.status})`);
            }

            return data;

        } catch (error) {
            // Si el error fue lanzado por response.ok, lo propagamos intacto
            if (error instanceof Error && error.message.includes('Error')) {
                throw error;
            }
            // Si falló el parseo de JSON o hubo un corte físico de red/internet
            throw new Error(`Fallo crítico de comunicación: ${error.message}`);
        }
    }

    /**
     * Realiza una petición GET limpia (Lectura).
     * 
     * @param {string} endpoint 
     * @returns {Promise<any>}
     */
    async get(endpoint) {
        return this.request(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
    }

    /**
     * Realiza una petición POST segura enviando datos binarios o de formulario.
     * Ideal para registros y subida de archivos adjuntos (FormData).
     * 
     * @param {string} endpoint 
     * @param {FormData|object} body 
     * @returns {Promise<any>}
     */
    async post(endpoint, body) {
        const options = {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            }
        };

        // Si el cuerpo es un FormData nativo del navegador, NO debemos configurar 
        // la cabecera 'Content-Type'. El navegador inyectará automáticamente 
        // el límite (boundary) correcto para que PHP llene las globales $_POST y $_FILES.
        if (body instanceof FormData) {
            options.body = body;
        } else {
            // Si pasamos un objeto plano, lo serializamos a string JSON ordinario
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        return this.request(endpoint, options);
    }
}
