let agendaUsuariosRegistrado = false;

function agendaUsuariosFactory(contactos) {
    return {
        filtro: '',
        contactos,
        modalAbierto: false,
        seleccionado: {},
        editando: false,
        touchStartY: null,
        offsetY: 0,
        cerrandoPorDrag: false,
        get filtrados() {
            if (!this.filtro) return this.contactos;
            const termino = this.filtro.toLowerCase();
            return this.contactos.filter((c) => (c.nombre_completo || '').toLowerCase().includes(termino));
        },
        abrirModal(contacto) {
            this.seleccionado = JSON.parse(JSON.stringify(contacto || {}));
            this.editando = false;
            this.modalAbierto = true;
            document.body.classList.add('overflow-hidden');
        },
        cerrarModal() {
            this.modalAbierto = false;
            this.seleccionado = {};
            this.editando = false;
            this.cerrandoPorDrag = false;
            this.offsetY = 0;
            this.touchStartY = null;
            document.body.classList.remove('overflow-hidden');
        },
        limpiarTelefono(numero) {
            return (numero || '').toString().replace(/\s+/g, '');
        },
        onTouchStart(e) {
            this.touchStartY = e.touches?.[0]?.clientY ?? null;
            this.offsetY = 0;
        },
        onTouchMove(e) {
            if (this.touchStartY === null) return;
            const currentY = e.touches?.[0]?.clientY ?? 0;
            const delta = currentY - this.touchStartY;
            this.offsetY = delta > 0 ? delta : 0;
        },
        onTouchEnd() {
            if (this.offsetY > 80) {
                this.modalAbierto = false;
                this.cerrandoPorDrag = true;
                setTimeout(() => {
                    this.cerrandoPorDrag = false;
                    this.offsetY = 0;
                    this.touchStartY = null;
                    this.seleccionado = {};
                    this.editando = false;
                    document.body.classList.remove('overflow-hidden');
                }, 150);
            } else {
                this.offsetY = 0;
                this.touchStartY = null;
            }
        },
        async guardarSeleccionado() {
            if (!this.seleccionado?.id) return;
            try {
                const resp = await fetch(window.usersBaseUrlMobile + '/' + this.seleccionado.id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        _method: 'PUT',
                        name: this.seleccionado.nombre,
                        primer_apellido: this.seleccionado.primer_apellido,
                        segundo_apellido: this.seleccionado.segundo_apellido,
                        email: this.seleccionado.email,
                        movil_personal: this.seleccionado.movil_personal,
                        movil_empresa: this.seleccionado.movil_empresa,
                        numero_corto: this.seleccionado.numero_corto,
                        dni: this.seleccionado.dni,
                        empresa: this.seleccionado.empresa,
                        categoria: this.seleccionado.categoria,
                        maquina: this.seleccionado.maquina,
                        turno: this.seleccionado.turno,
                    })
                });
                const data = await resp.json();
                if (resp.ok && data.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Usuario actualizado",
                        toast: true,
                        position: "top-end",
                        timer: 1800,
                        showConfirmButton: false
                    });
                    this.editando = false;
                } else {
                    const errMsg = data.message || 'No se pudo guardar el usuario.';
                    mostrarError(errMsg);
                }
            } catch (e) {
                mostrarError(e.message || "No se pudo guardar el usuario.", "Error de conexión");
            }
        },
    };
}

function registerAgendaUsuarios() {
    window.agendaUsuarios = agendaUsuariosFactory;

    if (!window.Alpine || typeof window.Alpine.data !== 'function') {
        return false;
    }

    if (!agendaUsuariosRegistrado) {
        window.Alpine.data('agendaUsuarios', agendaUsuariosFactory);
        agendaUsuariosRegistrado = true;
    }

    return true;
}

if (!registerAgendaUsuarios()) {
    document.addEventListener('alpine:init', registerAgendaUsuarios, { once: true });
}

document.addEventListener('livewire:navigated', registerAgendaUsuarios);
