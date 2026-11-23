export class ModalUI extends HTMLElement {
    constructor() {
        super();
        this.dialogo = null;
    }

    connectedCallback() {
        if (this.dialogo) return;

        const dialogo = document.createElement('dialog');
        dialogo.className = 'dialogo-estandar';
        dialogo.setAttribute('aria-modal', 'true');

        for (const atributo of ['aria-labelledby', 'aria-describedby']) {
            const valor = this.getAttribute(atributo);
            if (valor !== null) dialogo.setAttribute(atributo, valor);
        }

        while (this.firstChild) {
            dialogo.appendChild(this.firstChild);
        }

        this.replaceChildren(dialogo);
        this.dialogo = dialogo;
    }

    showModal() {
        if (!this.dialogo) {
            throw new Error('El componente modal aún no está inicializado.');
        }
        this.dialogo.showModal();
    }

    close() {
        this.dialogo?.close();
    }

    get open() {
        return this.dialogo?.open ?? false;
    }
}
