import { BotonUI } from './BotonUI.js';
import { ContenedorUI } from './ContenedorUI.js';
import { ModalUI } from './ModalUI.js';

const COMPONENTES = [
    ['boton-ui', BotonUI],
    ['contenedor-ui', ContenedorUI],
    ['modal-ui', ModalUI]
];

for (const [nombre, componente] of COMPONENTES) {
    if (!customElements.get(nombre)) {
        customElements.define(nombre, componente);
    }
}
