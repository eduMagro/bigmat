<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>

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

    <div class="container mx-auto sm:px-4">
        <x-ficha-trabajador :user="$user" :resumen="$resumen" />
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

    <div class="container mx-auto px-4 pb-4" x-data="documentosManager({{ $user->id }})" @open-docs-modal.window="openModal()">
        <!-- Modal -->
        <div x-show="showModal" x-cloak class="fixed max-h-screen inset-0 z-[900]" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" aria-hidden="true"
                    @click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-top bg-white rounded-lg text-left shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full !max-h-[90vh] !overflow-y-auto">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Contratos y
                                    Documentos</h3>
                                <!-- Fecha de Incorporación -->
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de
                                        Incorporación</label>
                                    <template x-if="hasIncorporacion">
                                        <div>
                                            <div class="p-2 bg-white border border-gray-200 rounded-md text-gray-700 sm:text-sm"
                                                x-text="fechaIncorporacion ? formatDate(fechaIncorporacion) : 'No definida'">
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Vinculada a la incorporación (No
                                                modificable).</p>
                                        </div>
                                    </template>
                                    <template x-if="!hasIncorporacion">
                                        <div>
                                            @if (auth()->user()->rol === 'oficina')
                                                <div class="flex gap-2">
                                                    <input type="date" x-model="fechaIncorporacion"
                                                        class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                    <button @click="updateFechaIncorporacion()"
                                                        class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 text-sm">Guardar</button>
                                                </div>
                                            @else
                                                <div class="p-2 bg-white border border-gray-200 rounded-md text-gray-700 sm:text-sm"
                                                    x-text="fechaIncorporacion ? formatDate(fechaIncorporacion) : 'No definida'">
                                                </div>
                                            @endif
                                            <p class="text-xs text-gray-500 mt-1">Usada para el cálculo de vacaciones.
                                            </p>
                                        </div>
                                    </template>
                                </div>
                                <!-- Listado de Contratos de Incorporación -->
                                <div class="mt-6 mb-6">
                                    <h4 class="font-medium text-gray-900 mb-2">Contratos (Incorporación)</h4>
                                    <div class="overflow-hidden border border-gray-200 rounded-lg bg-gray-50">
                                        <template x-if="!hasIncorporacion">
                                            <p class="p-4 text-sm text-gray-500 italic">No hay incorporación vinculada.
                                            </p>
                                        </template>
                                        <template x-if="hasIncorporacion && contratos.length === 0">
                                            <p class="p-4 text-sm text-gray-500 italic">No hay contratos subidos.</p>
                                        </template>
                                        <ul class="divide-y divide-gray-200 max-h-40 overflow-y-auto"
                                            x-show="hasIncorporacion && contratos.length > 0">
                                            <template x-for="doc in contratos" :key="doc.id">
                                                <li class="p-3 hover:bg-gray-100 flex justify-between items-center">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-blue-800 truncate cursor-pointer"
                                                            @click="window.open(doc.download_url, '_blank')"
                                                            x-text="'Contrato de Trabajo'"></p>
                                                        <p class="text-xs text-gray-500"
                                                            x-text="'Subido: ' + formatDate(doc.created_at)"></p>
                                                    </div>
                                                    <div class="ml-4 flex-shrink-0">
                                                        <a :href="doc.download_url" target="_blank"
                                                            class="text-blue-600 hover:text-blue-900" title="Ver">
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
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="closeModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function documentosManager(userId) {
                return {
                    showModal: false,
                    userId: userId,
                    fechaIncorporacion: '{{ $user->incorporacion && $user->incorporacion->fecha_incorporacion ? $user->incorporacion->fecha_incorporacion->format('Y-m-d') : ($user->fecha_incorporacion ? $user->fecha_incorporacion->format('Y-m-d') : '') }}',
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
                        if (dateStr.includes('T')) {
                            const date = new Date(dateStr);
                            return date.toLocaleDateString('es-ES');
                        }
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.message
                            });
                        }
                    }
                }
            }
        </script>
    </div>

    <!-- Bottom Modal para Vacaciones -->
    <div id="vacation-bottom-modal"
        class="fixed bottom-0 left-0 right-0 z-[9999] transform translate-y-full transition-transform duration-300 ease-in-out shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] pb-[env(safe-area-inset-bottom)]">
        <div
            class="bg-gray-900 text-white px-6 py-4 flex flex-col sm:flex-row justify-center items-center gap-4 border-t border-gray-700">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-lg font-medium">Estimación de vacaciones</span>
            </div>
            <div id="vacation-bottom-content" class="flex items-center gap-3">
                <!-- Content injected via JS -->
            </div>
            <button
                onclick="document.getElementById('vacation-bottom-modal').classList.remove('translate-y-0'); document.getElementById('vacation-bottom-modal').classList.add('translate-y-full')"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Calendario a ancho completo --}}
    <div class="calendario-full-width">
        <div class="">
            <div id="calendario" class="fc-calendario" data-config='@json($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'></div>
        </div>
    </div>

    {{-- FullCalendar --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Calendario a ancho completo - salir del contenedor padre */
        .calendario-full-width {
            width: 100%;
            margin: auto;
        }

        .fc {
            width: 100% !important;
            max-width: 100% !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .fc-daygrid-day {
            min-width: 0 !important;
        }

        /* Header del calendario - mismo color que sidebar */
        .fc .fc-toolbar {
            padding: 1rem;
            background: #111827;
            /* gray-900 */
            border-radius: 12px 12px 0 0;
            margin-bottom: 0 !important;
        }

        .fc .fc-toolbar-title {
            color: white !important;
            font-weight: 700;
            font-size: 1.25rem;
            text-transform: capitalize;
        }

        .fc .fc-button {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px !important;
            transition: all 0.2s ease;
            text-transform: capitalize;
        }

        .fc .fc-button:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px);
        }

        .fc .fc-button-active {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .fc .fc-button:disabled {
            opacity: 0.5;
        }

        /* Encabezados de dias */
        .fc .fc-col-header {
            background: #f8fafc;
        }

        .fc .fc-col-header-cell {
            padding: 0.75rem 0;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-color: #e2e8f0 !important;
        }

        /* Celdas de dias */
        .fc .fc-daygrid-day {
            transition: background-color 0.15s ease;
            border-color: #e2e8f0 !important;
        }

        .fc .fc-daygrid-day:hover {
            background-color: #f1f5f9;
        }

        .fc .fc-daygrid-day-number {
            font-weight: 600;
            color: #334155;
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        .fc .fc-day-today {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important;
        }

        .fc .fc-day-today .fc-daygrid-day-number {
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
        }

        /* Dias de otros meses */
        .fc .fc-day-other .fc-daygrid-day-number {
            color: #94a3b8;
        }

        /* Eventos */
        .fc .fc-event {
            border-radius: 6px;
            border: none !important;
            padding: 2px 6px;
            font-size: 0.75rem;
            font-weight: 500;
            margin: 1px 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .fc .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .fc .fc-daygrid-event-dot {
            display: none;
        }

        /* Mas eventos link */
        .fc .fc-daygrid-more-link {
            color: #6366f1;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Scrollbar del calendario */
        .fc .fc-scroller::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .fc .fc-scroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .fc .fc-scroller::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Seleccion de rango */
        .fc .bg-select-range {
            background: rgba(99, 102, 241, 0.25) !important;
            border-radius: 4px;
        }

        .fc .bg-select-endpoint {
            background: rgba(99, 102, 241, 0.45) !important;
        }

        .fc .bg-select-endpoint-left {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .fc .bg-select-endpoint-right {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .fc .fc-daygrid-day-bg {
            overflow: visible;
        }

        /* que los bg events no intercepten el mouse */
        .fc .bg-select-range,
        .fc .bg-select-endpoint {
            pointer-events: none !important;
        }

        /* Vista lista */
        .fc .fc-list {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        .fc .fc-list-day-cushion {
            background: #f8fafc !important;
            padding: 0.75rem 1rem;
        }

        .fc .fc-list-event:hover td {
            background: #f1f5f9 !important;
        }

        /* Bordes redondeados del contenedor */
        .fc .fc-view-harness {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: none;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .fc .fc-toolbar-title {
                font-size: 1.1rem;
            }

            .fc .fc-button {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }

            .fc .fc-col-header-cell {
                font-size: 0.65rem;
                padding: 0.5rem 0;
            }

            .fc .fc-daygrid-day-number {
                font-size: 0.75rem;
                padding: 0.25rem;
            }

            .fc .fc-event {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
        }

        /* SweetAlert personalizado para gestion de turnos */
        .swal-calendario-popup {
            border-radius: 12px !important;
            overflow: hidden;
        }

        .swal-calendario-popup .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
        }

        .swal-calendario-popup .swal2-actions {
            margin-top: 20px;
            gap: 12px;
        }

        .swal-calendario-popup .swal2-confirm,
        .swal-calendario-popup .swal2-cancel {
            padding: 10px 24px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            transition: transform 0.15s, box-shadow 0.15s !important;
        }

        .swal-calendario-popup .swal2-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.3);
        }

        .swal-calendario-popup .swal2-cancel:hover {
            transform: translateY(-1px);
        }

        .swal-calendario-popup select optgroup {
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
        }

        .swal-calendario-popup select option {
            padding: 8px;
            font-size: 14px;
        }
    </style>

    {{-- Usar script desde public (no migrado a Vite aun) --}}
    <script src="{{ asset('js/calendario/calendario.js') }}?v={{ time() }}"></script>

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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de ubicacion',
                        text: `${error.message}`
                    });
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
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.error
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo comunicar con el servidor'
                            });
                        });
                }

                boton.disabled = false;
                boton.querySelector('.texto').textContent = textoOriginal;
                boton.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>

</x-app-layout>
