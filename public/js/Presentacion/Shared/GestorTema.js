export class GestorTema {
    static CLAVE = 'preferencia-tema';

    constructor(documento = globalThis.document, almacenamiento = globalThis.localStorage) {
        this.documento = documento;
        this.almacenamiento = almacenamiento;
        this.consultaSistema = globalThis.matchMedia?.('(prefers-color-scheme: dark)');
    }

    inicializar() {
        const preferencia = this.obtenerPreferencia();
        this.aplicar(preferencia);
        this.actualizarControles(preferencia);
        this.consultaSistema?.addEventListener?.('change', () => {
            if (this.obtenerPreferencia() === 'sistema') {
                this.aplicar('sistema');
                this.actualizarControles('sistema');
            }
        });
    }

    obtenerPreferencia() {
        try {
            const valor = this.almacenamiento.getItem(GestorTema.CLAVE);
            return ['claro', 'oscuro', 'sistema'].includes(valor) ? valor : 'sistema';
        } catch {
            return 'sistema';
        }
    }

    aplicar(preferencia) {
        const efectiva = preferencia === 'sistema'
            ? (this.consultaSistema?.matches ? 'oscuro' : 'claro')
            : preferencia;

        this.documento.documentElement.dataset.tema = efectiva;
        this.documento.documentElement.style.colorScheme = efectiva;
        this.documento.documentElement.dataset.preferenciaTema = preferencia;
        this.actualizarMetaTema(efectiva);

        try {
            this.almacenamiento.setItem(GestorTema.CLAVE, preferencia);
        } catch {
            // El tema sigue funcionando aunque el almacenamiento no esté disponible.
        }
    }

    actualizarMetaTema(tema) {
        const meta = this.documento.querySelector('meta[name="theme-color"]');
        meta?.setAttribute('content', tema === 'oscuro' ? '#0f0d13' : '#fdf7ff');
    }

    enlazarControles(vista) {
        vista.enlazarClick('boton-tema-claro', () => this.seleccionar('claro'));
        vista.enlazarClick('boton-tema-oscuro', () => this.seleccionar('oscuro'));
        vista.enlazarClick('boton-tema-sistema', () => this.seleccionar('sistema'));
    }

    seleccionar(preferencia) {
        this.aplicar(preferencia);
        this.actualizarControles(preferencia);
    }

    actualizarControles(preferencia) {
        for (const id of ['boton-tema-claro', 'boton-tema-oscuro', 'boton-tema-sistema']) {
            this.documento.getElementById(id)?.querySelector('.boton')?.removeAttribute('aria-pressed');
        }
        const id = preferencia === 'claro'
            ? 'boton-tema-claro'
            : preferencia === 'oscuro'
                ? 'boton-tema-oscuro'
                : 'boton-tema-sistema';
        this.documento.getElementById(id)?.querySelector('.boton')?.setAttribute('aria-pressed', 'true');
    }
}
