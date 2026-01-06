@props(['seccion'])

<div x-data="modalClave('{{ $seccion }}')" x-init="verificarClaveSeccion()">
    <!-- Modal -->
    <div x-show="mostrarModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="modal-overlay-clave"
        x-cloak>

        <div class="bg-white rounded-xl p-6 w-80 shadow-xl relative z-50">
            <h2 class="text-lg font-bold mb-4 text-center">Sección protegida</h2>

            <form @submit.prevent="enviarClave">
                <input type="password" x-model="clave" placeholder="Introduce la clave..."
                    class="w-full border rounded px-3 py-2 mb-4" autofocus />
                <div class="text-right">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Acceder
                    </button>
                </div>
            </form>

            <template x-if="error">
                <p class="text-sm text-red-600 mt-3" x-text="error"></p>
            </template>
        </div>
    </div>
</div>
<style>
    .modal-overlay-clave {
        position: fixed;
        top: 3.5rem;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 30;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    @media (min-width: 768px) {
        .modal-overlay-clave {
            left: 16rem;
        }
    }
</style>
<script>
    function modalClave(seccion) {
        return {
            clave: '',
            mostrarModal: false,
            error: '',

            verificarClaveSeccion() {
                fetch(`/verificar-seccion/${seccion}`)
                    .then(res => {
                        if (res.status === 403) {
                            this.mostrarModal = true;
                        }
                    });
            },

            enviarClave() {
                fetch(`/proteger/${seccion}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            clave: this.clave
                        })
                    })
                    .then(async res => {
                        if (res.ok) {
                            this.mostrarModal = false;
                            location.reload();
                        } else {
                            const data = await res.json();
                            this.error = data.message || 'Clave incorrecta';
                        }
                    });
            }
        }
    }
</script>
