export class VistaInterfaz {
    constructor(documento = globalThis.document) {
        this.documento = documento;
    }

    enlazarClick(idElemento, manejador) {
        this.documento.getElementById(idElemento)?.addEventListener('click', manejador);
    }

    enlazarFormulario(idFormulario, manejador) {
        const formulario = this.documento.getElementById(idFormulario);
        if (!formulario) return;

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const alerta = formulario.querySelector('.alerta-modal');
            const boton = formulario.querySelector('button[type="submit"]');

            if (alerta) {
                alerta.textContent = '';
                alerta.className = 'alerta-modal';
            }

            try {
                if (boton) boton.disabled = true;
                await manejador(formulario);
            } catch (error) {
                if (alerta) {
                    alerta.textContent = error.message;
                    alerta.className = 'alerta-modal estado-error';
                } else {
                    this.mostrarAlertaGlobal(error.message, false);
                }
            } finally {
                if (boton) boton.disabled = false;
            }
        });
    }

    enlazarCierreModales() {
        this.documento.querySelectorAll('[data-cerrar-modal]').forEach((boton) => {
            boton.addEventListener('click', () => {
                boton.closest('dialog')?.close();
            });
        });
    }

    valorCampo(formulario, nombre) {
        const campo = formulario.elements.namedItem(nombre);
        if (!campo) {
            throw new Error(`No se encontró el campo "${nombre}" en el formulario.`);
        }
        return campo.value;
    }

    asignarValor(idElemento, valor) {
        const elemento = this.documento.getElementById(idElemento);
        if (elemento) elemento.value = valor;
    }

    async ejecutarConBotonEnCarga(idBoton, textoCarga, accion) {
        const elemento = this.documento.getElementById(idBoton);
        const boton = elemento?.querySelector('button') || elemento;
        if (!boton) return false;

        const textoOriginal = boton.textContent;
        boton.disabled = true;
        boton.textContent = textoCarga;
        try {
            await accion();
            return true;
        } finally {
            boton.disabled = false;
            boton.textContent = textoOriginal;
        }
    }

    abrirModal(idModal) {
        const modal = this.documento.getElementById(idModal);
        if (!modal || typeof modal.showModal !== 'function') return;

        const alerta = modal.querySelector('.alerta-modal');
        if (alerta) {
            alerta.textContent = '';
            alerta.className = 'alerta-modal';
        }
        modal.showModal();
    }

    cerrarModal(idModal) {
        const modal = this.documento.getElementById(idModal);
        if (modal?.open) modal.close();
    }

    alternarPanelPerfil(abrir) {
        this.documento.getElementById('panel-cuenta')?.classList.toggle('abierto', abrir);
        this.documento.getElementById('boton-menu-cuenta')?.setAttribute('aria-expanded', String(abrir));
    }

    mostrarAlertaGlobal(mensaje, esExito = true) {
        const contenedor = this.documento.getElementById('alerta-global');
        if (!contenedor) return;

        contenedor.textContent = mensaje;
        contenedor.className = `alerta-global ${esExito ? 'estado-exito' : 'estado-error'}`;
        globalThis.setTimeout(() => {
            contenedor.className = 'alerta-global oculto';
            contenedor.textContent = '';
        }, 5500);
    }

    mostrarAlertaModal(idModal, mensaje, esExito = true) {
        const modal = this.documento.getElementById(idModal);
        const contenedor = modal?.querySelector('.alerta-modal');
        if (!contenedor) return;

        contenedor.textContent = mensaje;
        contenedor.className = `alerta-modal ${esExito ? 'estado-exito' : 'estado-error'}`;
    }

    renderizarSesion(usuario) {
        const zonaInvitado = this.documento.getElementById('navegacion-invitado');
        const zonaAutenticado = this.documento.getElementById('navegacion-autenticada');
        const saludo = this.documento.getElementById('saludo-usuario');
        const rol = this.documento.getElementById('rol-usuario');

        if (usuario) {
            zonaInvitado?.classList.add('oculto');
            zonaAutenticado?.classList.remove('oculto');
            if (saludo) saludo.textContent = `Hola, ${usuario.apodo_usuario}`;
            if (rol) rol.textContent = this.etiquetaRol(usuario.rol_usuario);
            return;
        }

        zonaAutenticado?.classList.add('oculto');
        zonaInvitado?.classList.remove('oculto');
        if (saludo) saludo.textContent = '';
        if (rol) rol.textContent = '';
        this.alternarPanelPerfil(false);
    }

    etiquetaRol(rol) {
        const etiquetas = {
            usuario: 'Usuario',
            moderador: 'Moderador',
            administrador: 'Administrador'
        };
        const rolNormalizado = String(rol || '').toLowerCase();
        return `Rol: ${etiquetas[rolNormalizado] || 'No disponible'}`;
    }
}
