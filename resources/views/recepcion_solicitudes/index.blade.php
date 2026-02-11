<x-app-layout>
    <x-slot name="title">Recepción de Solicitudes</x-slot>

    @once
    @assets
    @vite('resources/js/vistas/recepcion-solicitudes/index.js')
    @endassets
    @endonce

    <div class="relative rounded-3xl p-5 md:p-8" x-data="window.recepcionSolicitudesApp({
            routes: {
                buscar: @js(route('recepcion-solicitudes.buscar')),
                recepcionar: @js(route('recepcion-solicitudes.recepcionar')),
                recepcionadas: @js(route('recepcion-solicitudes.recepcionadas')),
            },
            initialRecepcionadas: @js($recepcionadas ?? []),
        })" x-init="init()">

        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl">
            <div class="absolute -top-16 -right-16 h-64 w-64 rounded-full"></div>
            <div class="absolute -bottom-20 -left-12 h-72 w-72 rounded-full"></div>
        </div>

        <div class="relative">
            <div
                class="reveal-on-scroll mb-6 rounded-3xl border border-white/40 bg-white p-6 shadow-xl shadow-black/15 backdrop-blur-xl">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Recepción de Solicitudes</h1>
                    </div>

                    <div class="flex w-full items-center justify-center gap-2 sm:w-auto">
                        <button type="button" @click="openScannerModal()"
                            class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-scan-qr-code-icon lucide-scan-qr-code">
                                <path d="M17 12v4a1 1 0 0 1-1 1h-4" />
                                <path d="M17 3h2a2 2 0 0 1 2 2v2" />
                                <path d="M17 8V7" />
                                <path d="M21 17v2a2 2 0 0 1-2 2h-2" />
                                <path d="M3 7V5a2 2 0 0 1 2-2h2" />
                                <path d="M7 17h.01" />
                                <path d="M7 21H5a2 2 0 0 1-2-2v-2" />
                                <rect x="7" y="7" width="5" height="5" rx="1" />
                            </svg>
                            <span>Escanear QR</span>
                        </button>

                        <button type="button" @click="openManualModal()"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white/90 text-slate-700 transition hover:bg-slate-100"
                            title="Introducir QR manualmente" aria-label="Introducir QR manualmente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-square-pen-icon lucide-square-pen">
                                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                <path
                                    d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z" />
                            </svg>
                        </button>
                    </div>
                </div>

                @if (!empty($syncError))
                <div class="mt-4 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    {{ $syncError }}
                </div>
                @endif

                <p class=" hidden mt-3 text-xs text-slate-500" x-text="statusMessage"></p>
                <p class="hidden mt-1 text-xs text-slate-400">
                    La entrada manual está disponible en el botón pequeño de la mano.
                </p>
            </div>

            <div
                class="hidden reveal-on-scroll mb-5 rounded-3xl border border-white/40 bg-white p-4 shadow-xl shadow-black/15 backdrop-blur-xl">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Secciones</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="switchSection('recepcionar')"
                        class="rounded-2xl px-3 py-2 text-sm font-semibold transition" :class="activeSection === 'recepcionar'
                            ? 'bg-slate-900 text-white shadow'
                            : 'bg-white/85 text-slate-700 hover:bg-white'">
                        Recepcionar
                    </button>
                    <button type="button" @click="switchSection('lista')"
                        class="rounded-2xl px-3 py-2 text-sm font-semibold transition" :class="activeSection === 'lista'
                            ? 'bg-slate-900 text-white shadow'
                            : 'bg-white/85 text-slate-700 hover:bg-white'">
                        Lista de recepcionados
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5">
                <section id="section-recepcionar" x-show="activeSection === 'recepcionar'" x-cloak
                    class="reveal-on-scroll rounded-3xl border border-white/40 bg-white p-5 shadow-xl shadow-black/15 backdrop-blur-xl">
                    <h2 class="text-lg font-bold text-slate-800">Solicitud Escaneada</h2>

                    <template x-if="!solicitudActual">
                        <div
                            class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white/40 px-4 py-8 text-center text-sm text-slate-500">
                            Aún no hay una solicitud cargada.
                        </div>
                    </template>

                    <template x-if="solicitudActual">
                        <div class="mt-4 space-y-4">
                            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Solicitud</p>
                                    <p class="text-xs font-semibold text-slate-600"
                                        x-text="'#' + (solicitudActual.id ?? '-')"></p>
                                </div>
                                <p class="hidden mt-2 text-sm text-slate-700">
                                    Fecha aprobación:
                                    <span class="font-semibold"
                                        x-text="formatDate(solicitudActual.fecha_aprobacion)"></span>
                                </p>
                                <p class="text-sm text-slate-700">
                                    Estado recepción:
                                    <span class="font-semibold"
                                        :class="solicitudActual.recepcionado ? 'text-emerald-700' : 'text-amber-700'"
                                        x-text="solicitudActual.recepcionado ? 'Recepcionada' : 'Pendiente'"></span>
                                </p>
                            </div>
                            <div class="flex items-center justify-center md:justify-start md:gap-4">
                                <div class="rounded-2xl flex flex-col gap-1 items-center">
                                    <p class="font-semibold text-slate-800"
                                        x-text="personaNombre(solicitudActual.solicitante_detalle, solicitudActual.solicitante)">
                                    </p>
                                    <p class="text-sm text-slate-600 hidden"
                                        x-text="'DNI: ' + (solicitudActual.solicitante_detalle?.dni || 'Sin dato')"></p>
                                </div>

                                <div class=" hidden rounded-2xl bg-slate-50 border border-slate-100 p-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Aprobado por</p>
                                    <p class="mt-1 font-semibold text-slate-800"
                                        x-text="personaNombre(solicitudActual.aprobado_por_detalle, solicitudActual.aprobado_por)">
                                    </p>
                                    <p class="text-sm text-slate-600"
                                        x-text="'DNI: ' + (solicitudActual.aprobado_por_detalle?.dni || 'Sin dato')">
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-white/70 p-4">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Artículos</p>
                                <ul class="mt-2 space-y-2 text-sm text-slate-700 max-h-40 overflow-auto pr-1">
                                    <template x-for="(articulo, idx) in (solicitudActual.articulos || [])" :key="idx">
                                        <li
                                            class="flex items-center justify-between rounded-xl bg-slate-50 border border-slate-100 px-3 py-2">
                                            <span x-text="articulo.nombre"></span>
                                            <span class="font-semibold text-slate-800"
                                                x-text="'x' + (articulo.cantidad ?? 1)"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <button type="button" @click="recepcionarSolicitud()"
                                class="w-full rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                                :disabled="loadingRecepcionar || solicitudActual.recepcionado">
                                <span x-show="!loadingRecepcionar && !solicitudActual.recepcionado">Marcar como
                                    recepcionada</span>
                                <span x-show="loadingRecepcionar">Guardando recepción...</span>
                                <span x-show="!loadingRecepcionar && solicitudActual.recepcionado">Ya
                                    recepcionada</span>
                            </button>
                        </div>
                    </template>
                </section>

                <section id="section-lista" x-show="activeSection === 'lista'" x-cloak
                    class="reveal-on-scroll rounded-3xl border border-white/40 bg-white p-5 shadow-xl shadow-indigo-900/5 backdrop-blur-xl">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Solicitudes Recepcionadas</h2>
                            <p class="mt-1 text-xs text-slate-500">Últimas recepciones sincronizadas con HPR.</p>
                        </div>
                        <button type="button" @click="refreshRecepcionadas(true)"
                            class="rounded-xl border border-slate-300 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                            Actualizar
                        </button>
                    </div>

                    <div x-show="isLoadingRecepcionadas"
                        class="mt-4 rounded-2xl border border-white/50 bg-white/70 px-4 py-4 text-center text-sm text-slate-500">
                        Cargando recepcionadas...
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="item in recepcionadas" :key="item.id">
                            <article class="rounded-2xl border border-white/50 bg-neutral-200/50 p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-700" x-text="'#' + item.id"></p>
                                            <p class="mt-1 text-sm font-medium text-slate-800"
                                                x-text="solicitanteNombreCorto(item.solicitante)"></p>
                                        </div>
                                        <p class="mt-1 text-xs text-emerald-700"
                                            x-text="'Recepción: ' + formatDate(item.fecha_recepcion)"></p>
                                    </div>
                                    <button type="button" @click="toggleRecepcionadaDetalle(item.id)"
                                        class="shrink-0 rounded-xl border border-slate-300 bg-white/90 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                        x-text="expandedRecepcionadaId === item.id ? 'Ocultar' : 'Ver más'"></button>
                                </div>

                                <div x-show="expandedRecepcionadaId === item.id" x-transition
                                    class="mt-3 rounded-xl bg-slate-50/90 p-3 text-xs text-slate-700 space-y-2">
                                    <p>
                                        <span class="font-semibold">Solicitante:</span>
                                        <span x-text="personaNombre(item.solicitante)"></span>
                                        <span x-text="' | DNI: ' + (item.solicitante?.dni || 'Sin dato')"></span>
                                    </p>
                                    <p>
                                        <span class="font-semibold">Aprobado por:</span>
                                        <span x-text="personaNombre(item.aprobado_por)"></span>
                                        <span x-text="' | DNI: ' + (item.aprobado_por?.dni || 'Sin dato')"></span>
                                    </p>
                                    <p>
                                        <span class="font-semibold">Fecha aprobación:</span>
                                        <span x-text="formatDate(item.fecha_aprobacion)"></span>
                                    </p>
                                    <p>
                                        <span class="font-semibold">Fecha recepción:</span>
                                        <span x-text="formatDate(item.fecha_recepcion)"></span>
                                    </p>
                                    <p>
                                        <span class="font-semibold">Recepcionado por:</span>
                                        <span x-text="item.recepcionado_por || 'N/D'"></span>
                                    </p>
                                    <div>
                                        <p class="font-semibold mb-1">Artículos:</p>
                                        <template x-if="Array.isArray(item.articulos) && item.articulos.length > 0">
                                            <ul class="space-y-1">
                                                <template x-for="(articulo, idx) in item.articulos" :key="idx">
                                                    <li class="rounded-lg bg-white px-2 py-1">
                                                        <span x-text="articulo.nombre || 'Sin nombre'"></span>
                                                        <span class="font-semibold"
                                                            x-text="' x' + (articulo.cantidad ?? 1)"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </template>
                                        <template x-if="!Array.isArray(item.articulos) || item.articulos.length === 0">
                                            <p class="text-slate-500">Sin artículos</p>
                                        </template>
                                    </div>
                                </div>
                            </article>
                        </template>

                        <div x-show="!isLoadingRecepcionadas && recepcionadas.length === 0"
                            class="rounded-2xl border border-white/50 bg-white/70 px-4 py-10 text-center text-sm text-slate-500">
                            No hay solicitudes recepcionadas para mostrar.
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal escáner -->
        <div x-show="showScannerModal" x-cloak
            class="fixed inset-0 z-[1200] flex items-center justify-center bg-black/70 p-4"
            @keydown.escape.window="closeScannerModal()">
            <div class="w-full max-w-xl rounded-3xl bg-slate-950 p-5 text-white shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Escanear QR</h3>
                    <button class="rounded-lg px-3 py-2 text-sm hover:bg-white/10"
                        @click="closeScannerModal()">Cerrar</button>
                </div>

                <p class="mt-2 text-xs text-slate-300">
                    Apunta la cámara al QR. Al detectar código, la consulta se lanza automáticamente.
                </p>

                <div id="qr-reader" class="mt-4 min-h-[280px] overflow-hidden rounded-2xl bg-black"></div>

                <p class="mt-3 text-xs text-amber-300" x-show="scannerError" x-text="scannerError"></p>
            </div>
        </div>

        <!-- Modal manual -->
        <div x-show="showManualModal" x-cloak
            class="fixed inset-0 z-[1190] flex items-center justify-center bg-black/70 p-4"
            @keydown.escape.window="closeManualModal()">
            <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Introducir QR manualmente</h3>
                    <button class="rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-100"
                        @click="closeManualModal()">Cerrar</button>
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    Pega el código del QR y pulsa buscar.
                </p>

                <div class="mt-4 space-y-3">
                    <input id="manual-qr-input" type="text" x-model.trim="manualCodeDraft"
                        @keydown.enter.prevent="submitManualCode()"
                        placeholder="Ej: 01ce274c-41e2-4672-b661-fb8c0d19eda1"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200" />

                    <button type="button" @click="submitManualCode()"
                        class="w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                        :disabled="loadingSearch">
                        <span x-show="!loadingSearch">Buscar código</span>
                        <span x-show="loadingSearch">Consultando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(14px);
            transition: opacity 420ms ease, transform 420ms ease;
        }

        .reveal-on-scroll.revealed {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</x-app-layout>