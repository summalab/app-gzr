const CLASES_VARIANTE = Object.freeze({
    principal: 'boton-principal',
    secundario: 'boton-secundario',
    peligro: 'boton-peligro',
    menu: 'boton-menu',
    cerrar: 'boton-cerrar',
    enlace: 'boton-enlace',
    opcion: 'boton-opcion',
    'opcion-peligro': 'boton-opcion-peligro',
    flotante: 'boton-flotante',
    tema: 'boton-tema',
    dashboard: 'boton-dashboard'
});

export class BotonUI extends HTMLElement {
    static get observedAttributes() {
        return ['aria-expanded'];
    }

    constructor() {
        super();
        this.botonInterno = null;
    }

    connectedCallback() {
        if (this.botonInterno) return;

        const boton = document.createElement('button');
        const variante = this.getAttribute('variante') || 'principal';
        boton.type = this.getAttribute('tipo') || 'button';
        boton.className = `boton ${CLASES_VARIANTE[variante] || CLASES_VARIANTE.principal}`;

        if (this.getAttribute('ancho') === 'completo') {
            boton.classList.add('boton-ancho-completo');
        }
        if (this.hasAttribute('deshabilitado')) {
            boton.disabled = true;
        }

        for (const atributo of ['aria-label', 'aria-expanded', 'aria-controls', 'title']) {
            const valor = this.getAttribute(atributo);
            if (valor !== null) boton.setAttribute(atributo, valor);
        }

        while (this.firstChild) {
            boton.appendChild(this.firstChild);
        }

        this.replaceChildren(boton);
        this.botonInterno = boton;
    }

    attributeChangedCallback(nombre, anterior, valor) {
        if (nombre === 'aria-expanded' && anterior !== valor && this.botonInterno) {
            this.botonInterno.setAttribute(nombre, valor);
        }
    }

    get disabled() {
        return this.botonInterno?.disabled ?? false;
    }

    set disabled(valor) {
        if (this.botonInterno) this.botonInterno.disabled = Boolean(valor);
    }

    get texto() {
        return this.botonInterno?.textContent ?? this.textContent;
    }

    set texto(valor) {
        if (this.botonInterno) this.botonInterno.textContent = valor;
        else this.textContent = valor;
    }
}
