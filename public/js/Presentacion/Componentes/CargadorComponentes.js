export class CargadorComponentes {
    constructor(documento = globalThis.document, solicitar = globalThis.fetch.bind(globalThis)) {
        this.documento = documento;
        this.solicitar = solicitar;
    }

    async cargar() {
        const marcadores = [...this.documento.querySelectorAll('[data-componente]')];
        await Promise.all(marcadores.map((marcador) => this.cargarEnMarcador(marcador)));
    }

    async cargarEnMarcador(marcador) {
        const ruta = marcador.getAttribute('data-componente');
        if (!ruta) {
            throw new Error('Se encontró un marcador de componente sin ruta.');
        }

        const respuesta = await this.solicitar(ruta, { credentials: 'same-origin' });
        if (!respuesta.ok) {
            throw new Error(`No se pudo cargar el componente "${ruta}" (HTTP ${respuesta.status}).`);
        }

        const plantilla = this.documento.createElement('template');
        plantilla.innerHTML = await respuesta.text();
        marcador.replaceWith(plantilla.content);
    }
}
