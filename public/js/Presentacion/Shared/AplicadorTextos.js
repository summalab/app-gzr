import { TEXTOS_ES } from '../../textos-es.js';

export class AplicadorTextos {
    constructor(documento = globalThis.document) {
        this.documento = documento;
    }

    aplicar() {
        for (const elemento of this.documento.querySelectorAll('[data-texto]')) {
            const clave = elemento.getAttribute('data-texto');
            if (Object.hasOwn(TEXTOS_ES, clave)) {
                if (typeof elemento.texto === 'string') elemento.texto = TEXTOS_ES[clave];
                else elemento.textContent = TEXTOS_ES[clave];
            }
        }
        for (const elemento of this.documento.querySelectorAll('[data-aria-texto]')) {
            const clave = elemento.getAttribute('data-aria-texto');
            if (Object.hasOwn(TEXTOS_ES, clave)) elemento.setAttribute('aria-label', TEXTOS_ES[clave]);
        }
        for (const elemento of this.documento.querySelectorAll('[data-titulo-texto]')) {
            const clave = elemento.getAttribute('data-titulo-texto');
            if (Object.hasOwn(TEXTOS_ES, clave)) elemento.setAttribute('title', TEXTOS_ES[clave]);
        }
    }

    obtener(clave) {
        return TEXTOS_ES[clave] ?? clave;
    }
}
