@php
    $esOficina = Auth::check() && Auth::user()->rol === 'oficina';
@endphp



<x-app-layout>
    <x-slot name="title">{{ $user->nombre_completo }}</x-slot>

    {{-- Botones de fichaje: disponibles para todos los roles --}}
    <div class="container mx-auto px-4 pb-4">
        <div class="flex justify-center items-center gap-4">
            <button onclick="registrarFichaje('entrada')"
                class="py-3 px-8 bg-green-600 hover:bg-green-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando max-md:w-full">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Entrada</span>
            </button>

            <button onclick="registrarFichaje('salida')"
                class="py-3 px-8 bg-red-600 hover:bg-red-700 text-white text-lg font-semibold rounded-lg shadow-lg transition duration-200 btn-cargando max-md:w-full">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span class="texto">Salida</span>
            </button>
        </div>
    </div>

    <div class="container mx-auto md:px-4">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />
    </div>

    @if ($esOficina || auth()->id() === $user->id)
        <div class="container mx-auto md:px-4 pb-4" x-data="documentosManager({{ $user->id }})" @open-docs-modal.window="openModal()">

            <!-- Modal -->
            <div x-show="showModal" x-cloak class="fixed max-h-screen inset-0 z-[900]" aria-labelledby="modal-title"
                role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:block sm:p-0">
                    <div x-show="showModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" aria-hidden="true"
                        @click="closeModal()"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="showModal" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-top bg-white dark:bg-gray-800 rounded-lg text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full !max-h-[90vh] !overflow-y-auto">

                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        Gestión de Contratos y Documentos
                                    </h3>

                                    <!-- Fecha de Incorporación -->
                                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1">Fecha de
                                            Incorporación</label>
                                        <template x-if="hasIncorporacion">
                                            <div>
                                                <div class="p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-gray-700 dark:text-gray-400 sm:text-sm"
                                                    x-text="fechaIncorporacion ? formatDate(fechaIncorporacion) : 'No definida'">
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vinculada a la incorporación (No
                                                    modificable).</p>
                                            </div>
                                        </template>
                                        <template x-if="!hasIncorporacion">
                                            <div>
                                                @if (auth()->user()->rol === 'oficina')
                                                    <div class="flex gap-2">
                                                        <input type="date" x-model="fechaIncorporacion"
                                                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-md">
                                                        <button @click="updateFechaIncorporacion()"
                                                            class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 text-sm">Guardar</button>
                                                    </div>
                                                @else
                                                    <div class="p-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md text-gray-700 dark:text-gray-400 sm:text-sm"
                                                        x-text="fechaIncorporacion ? formatDate(fechaIncorporacion) : 'No definida'">
                                                    </div>
                                                @endif
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Usada para el cálculo de
                                                    vacaciones.
                                                </p>
                                            </div>
                                        </template>
                                    </div>



                                    <!-- Listado de Contratos de Incorporación -->
                                    <div class="mt-6 mb-6">
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Contratos (Incorporación)</h4>
                                        <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700">
                                            <template x-if="!hasIncorporacion">
                                                <p class="p-4 text-sm text-gray-500 dark:text-gray-400 italic">El usuario no tiene una
                                                    incorporación vinculada.</p>
                                            </template>
                                            <template x-if="hasIncorporacion && contratos.length === 0">
                                                <p class="p-4 text-sm text-gray-500 dark:text-gray-400 italic">No hay contratos subidos en
                                                    la incorporación.</p>
                                            </template>
                                            <ul class="divide-y divide-gray-200 dark:divide-gray-700 max-h-40 overflow-y-auto"
                                                x-show="hasIncorporacion && contratos.length > 0">
                                                <template x-for="doc in contratos" :key="doc.id">
                                                    <li class="p-3 hover:bg-gray-100 dark:hover:bg-gray-600 flex justify-between items-center">
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-blue-800 dark:text-blue-400 truncate cursor-pointer"
                                                                @click="window.open(doc.download_url, '_blank')"
                                                                x-text="'Contrato de Trabajo'">
                                                            </p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400"
                                                                x-text="'Subido: ' + formatDate(doc.created_at)"></p>
                                                        </div>
                                                        <div class="ml-4 flex-shrink-0 flex gap-2">
                                                            <a :href="doc.download_url" target="_blank"
                                                                class="text-blue-600 hover:text-blue-900"
                                                                title="Ver">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                            </a>
                                                            <a :href="doc.download_url + '?download=1'" download
                                                                class="text-green-600 hover:text-green-900"
                                                                title="Descargar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                    fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </a>
                                                        </div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" @click="closeModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $contratosIncorporacion = collect([]);
            $hasIncorporacion = false;
            if ($user->incorporacion) {
                $hasIncorporacion = true;
                $contratosIncorporacion = $user->incorporacion
                    ->documentos()
                    ->where('tipo', 'contrato_trabajo')
                    ->get()
                    ->map(function ($doc) use ($user) {
                        $doc->download_url = route('incorporaciones.verArchivo', [
                            'incorporacion' => $user->incorporacion->id,
                            'archivo' => $doc->archivo,
                        ]);
                        return $doc;
                    });
            }
        @endphp

        <script>
            function documentosManager(userId) {
                return {
                    showModal: false,
                    userId: userId,
                    fechaIncorporacion: '{{ $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : '' }}',
                    contratos: @json($contratosIncorporacion),
                    hasIncorporacion: @json($hasIncorporacion),
                    openModal() {
                        this.showModal = true;
                    },
                    closeModal() {
                        this.showModal = false;
                    },
                    formatDate(dateStr) {
                        if (!dateStr) return '';

                        // Si es un formato ISO completo (contiene T), usamos new Date()
                        if (dateStr.includes('T')) {
                            const date = new Date(dateStr);
                            return date.toLocaleDateString('es-ES');
                        }

                        // Para formato YYYY-MM-DD puro (evita desfases de zona horaria)
                        const parts = dateStr.split('-');
                        if (parts.length === 3) {
                            return `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                        return dateStr;
                    },
                    async updateFechaIncorporacion() {
                        try {
                            const url = "{{ route('usuarios.updateFechaIncorporacion', ':id') }}".replace(':id', this
                                .userId);
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    fecha_incorporacion: this.fechaIncorporacion || null
                                })
                            });

                            const data = await response.json();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualizado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false,
                                    timerProgressBar: true
                                });
                            } else {
                                throw new Error(data.error || 'Error al actualizar');
                            }
                        } catch (error) {
                            mostrarError(error.message);
                        }
                    },



                }
            }
        </script>
    @endif

    <!-- Bottom Modal para Vacaciones -->
    <div id="vacation-bottom-modal"
        class="fixed bottom-0 left-0 right-0 z-[9999] transform translate-y-full transition-transform duration-300 ease-in-out shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] pb-[env(safe-area-inset-bottom)]">
        <div class="bg-gray-800 text-white px-4 py-3 flex justify-center items-center border-t border-gray-600">
            <div id="vacation-bottom-content" class="flex flex-wrap items-center justify-center gap-2">
                <!-- Content injected via JS -->
            </div>
        </div>
    </div>


    <div class="calendario-full-width">
        <div class="pb-4">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar - con defer para no bloquear renderizado --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <link rel="stylesheet" href="{{ asset('css/calendario-perfil.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Usar script desde public (no migrado a Vite aún) --}}
    <script defer src="{{ asset('js/calendario/calendario.js') }}?v={{ time() }}"></script>

    <script>
        function registrarFichaje(tipo) {
            const boton = event.currentTarget;
            const textoOriginal = boton.querySelector('.texto').textContent;

            boton.disabled = true;
            boton.querySelector('.texto').textContent = 'Procesando...';
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitud = position.coords.latitude;
                    const longitud = position.coords.longitude;
                    procesarFichaje(tipo, latitud, longitud, boton, textoOriginal);
                },
                function(error) {
                    mostrarError(error.message, 'Error de ubicación');
                    boton.disabled = false;
                    boton.querySelector('.texto').textContent = textoOriginal;
                    boton.classList.remove('opacity-50', 'cursor-not-allowed');
                }, {
                    enableHighAccuracy: false,
                    timeout: 8000,
                    maximumAge: 60000
                }
            );
        }

        function procesarFichaje(tipo, latitud, longitud, boton, textoOriginal) {
            Swal.fire({
                title: 'Confirmar Fichaje',
                text: `Quieres registrar una ${tipo}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si, fichar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const payload = {
                        user_id: "{{ auth()->id() }}",
                        tipo: tipo,
                        latitud: latitud,
                        longitud: longitud,
                    };

                    fetch("{{ url('/fichar') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: data.success,
                                    text: `📍 Lugar: ${data.obra_nombre}`,
                                    showConfirmButton: false,
                                    timer: 3000
                                });

                                if (window.calendar) {
                                    window.calendar.refetchEvents();
                                }
                            } else {
                                mostrarError(data.error);
                            }
                        })
                        .catch(err => {
                            mostrarError('No se pudo comunicar con el servidor');
                        });
                }

                boton.disabled = false;
                boton.querySelector('.texto').textContent = textoOriginal;
                boton.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>
</x-app-layout>
