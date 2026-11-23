export class HttpApiClient {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}/${endpoint.replace(/^\//, '')}`;
        const configuracionFetch = {
            credentials: 'same-origin',
            ...options
        };

        try {
            const response = await fetch(url, configuracionFetch);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || `Error en el servidor web (Código: ${response.status})`);
            }

            return data;
        } catch (error) {
            if (error instanceof Error && error.message.includes('Error')) {
                throw error;
            }
            throw new Error(`Fallo crítico de comunicación: ${error.message}`);
        }
    }

    get(endpoint) {
        return this.request(endpoint, {
            method: 'GET',
            headers: { Accept: 'application/json' }
        });
    }

    post(endpoint, body) {
        const options = {
            method: 'POST',
            headers: { Accept: 'application/json' }
        };

        if (body instanceof FormData) {
            options.body = body;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        return this.request(endpoint, options);
    }
}
