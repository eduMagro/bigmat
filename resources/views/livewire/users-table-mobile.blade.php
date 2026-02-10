<div x-data="agendaUsuarios(@js($contactosAgenda ?? []))" class="users-mobile md:hidden mt-4 space-y-3">
    <style>
        /* Evitar zoom en iOS al enfocar inputs en la vista móvil */
        .users-mobile input,
        .users-mobile textarea,
        .users-mobile select,
        .users-mobile button {
            font-size: 16px;
        }
    </style>
    <div class="sticky top-0 z-20 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-lg">
        <div class="flex items-center gap-2 px-4 py-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.9 14.32a8 8 0 1 1 1.414-1.414l3.387 3.387a1 1 0 0 1-1.414 1.414l-3.387-3.387ZM14 8a6 6 0 1 0-12 0 6 6 0 0 0 12 0Z" clip-rule="evenodd" />
            </svg>
            <input type="text" x-model.debounce.200ms="filtro" placeholder="Buscar por nombre o apellido"
                class="w-full border-0 focus:ring-0 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-800 placeholder:text-gray-400" />
            <a href="{{ route('incorporaciones.index') }}" class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Incorporaciones
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
        <template x-for="contacto in filtrados" :key="contacto.id">
            <div class="p-4 flex items-start gap-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer" @click="abrirModal(contacto)">
                <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                    <img x-show="contacto.imagen" :src="contacto.imagen" alt="Avatar" class="w-full h-full object-cover" loading="lazy">
                    <div x-show="!contacto.imagen" class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 2a5 5 0 0 0-5 5v1a5 5 0 0 0 10 0V7a5 5 0 0 0-5-5Zm-3.464 9.95a7 7 0 0 0 6.928 0A7 7 0 0 1 20 18.07V19a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-1a7 7 0 0 1 4.536-6.05Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 dark:text-gray-100 truncate" x-text="contacto.nombre_completo"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate" x-text="contacto.empresa || 'Sin empresa'"></p>
                        </div>
                    </div>
                    <p class="text-xs mt-1 text-gray-600 dark:text-gray-400" x-text="contacto.categoria || contacto.rol || 'Rol no asignado'"></p>
                </div>
            </div>
        </template>

        <div x-show="!filtrados.length" class="p-4 text-center text-gray-600 dark:text-gray-400 text-sm">
            No hay contactos con ese nombre.
        </div>
    </div>

    <div x-cloak x-show="modalAbierto || cerrandoPorDrag" class="fixed inset-0 z-40 flex items-end sm:items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-black/40" @click="cerrarModal"></div>
        <div x-show="modalAbierto || cerrandoPorDrag" class="bg-white dark:bg-gray-800 rounded-t-3xl mx-auto p-0 shadow-2xl overflow-hidden flex flex-col h-[calc(100vh-110px)] bottom-0 absolute sm:w-[calc(95vw)] w-screen"
            x-transition:enter="transform transition ease-out duration-150"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transform transition ease-in duration-150"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            :class="{ 'transition-transform duration-150 ease-out': cerrandoPorDrag }"
            :style="{
                'backdrop-filter': 'blur(10px)',
                transform: cerrandoPorDrag ? 'translateY(100vh)' : (offsetY > 0 ? `translateY(${offsetY}px)` : '')
            }">
            <div class="relative h-56 bg-gradient-to-b from-gray-900/90 via-gray-800/70 to-white"
                @touchstart.passive="onTouchStart($event)"
                @touchmove.passive="onTouchMove($event)"
                @touchend="onTouchEnd">
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_20%_20%,_rgba(255,255,255,0.6),_transparent_40%),radial-gradient(circle_at_80%_30%,_rgba(255,255,255,0.35),_transparent_45%)]"></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-4 pt-6">
                    <div class="w-28 h-28 rounded-full overflow-hidden ring-4 ring-white/40 shadow-lg bg-gray-200">
                        <img x-show="seleccionado?.imagen" :src="seleccionado?.imagen" alt="Avatar grande" class="w-full h-full object-cover">
                        <div x-show="!seleccionado?.imagen" class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M12 2a5 5 0 0 0-5 5v1a5 5 0 0 0 10 0V7a5 5 0 0 0-5-5Zm-3.464 9.95a7 7 0 0 0 6.928 0A7 7 0 0 1 20 18.07V19a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-1a7 7 0 0 1 4.536-6.05Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-semibold text-white drop-shadow-md" x-text="seleccionado?.nombre_completo"></p>
                    <p class="text-sm text-gray-200" x-text="seleccionado?.rol || 'Rol no asignado'"></p>
                </div>
                <div class="absolute right-3 top-3 flex items-center gap-2">
                    <template x-if="!editando">
                        <button type="button" class="text-white/85 hover:text-white bg-white/10 border border-white/20 rounded-full p-2"
                            @click="editando = true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16.862 3.487a2.5 2.5 0 0 1 3.535 3.535l-9.9 9.9a8 8 0 0 1-3.597 2.036l-3.134.9a.5.5 0 0 1-.62-.62l.9-3.134a8 8 0 0 1 2.036-3.597l9.78-9.78Z" />
                                <path d="M4 19h16v2H4z" />
                            </svg>
                        </button>
                    </template>
                    <template x-if="editando">
                        <button type="button" class="text-white/90 hover:text-white bg-emerald-600 rounded-full px-3 py-2 text-sm font-semibold shadow"
                            @click="guardarSeleccionado()">
                            Guardar
                        </button>
                    </template>
                    <button type="button" class="text-white/80 hover:text-white bg-white/10 border border-white/20 rounded-full p-2" @click="cerrarModal" aria-label="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 0 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="px-5 pt-4 space-y-4"
                @touchstart.passive="onTouchStart($event)"
                @touchmove.passive="onTouchMove($event)"
                @touchend="onTouchEnd">
                <div class="flex items-center justify-center gap-4 pb-2">
                    <template x-if="seleccionado?.movil_personal">
                        <a :href="'tel:' + limpiarTelefono(seleccionado.movil_personal)"
                            class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center text-green-600 border border-green-100"
                            title="Llamar móvil personal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.25 5.25A3.75 3.75 0 0 1 6 1.5h1.26c.82 0 1.52.546 1.72 1.33l.83 3.32a1.75 1.75 0 0 1-.46 1.63l-1.3 1.3a14.5 14.5 0 0 0 6.94 6.94l1.3-1.3a1.75 1.75 0 0 1 1.63-.46l3.32.83c.784.2 1.33.9 1.33 1.72V18a3.75 3.75 0 0 1-3.75 3.75h-.5C9.364 21.75 2.25 14.636 2.25 5.75v-.5Z" />
                            </svg>
                        </a>
                    </template>
                    <template x-if="seleccionado?.movil_empresa">
                        <a :href="'tel:' + limpiarTelefono(seleccionado.movil_empresa)"
                            class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center text-blue-600 border border-blue-100"
                            title="Llamar móvil empresa">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.25 5.25A3.75 3.75 0 0 1 6 1.5h1.26c.82 0 1.52.546 1.72 1.33l.83 3.32a1.75 1.75 0 0 1-.46 1.63l-1.3 1.3a14.5 14.5 0 0 0 6.94 6.94l1.3-1.3a1.75 1.75 0 0 1 1.63-.46l3.32.83c.784.2 1.33.9 1.33 1.72V18a3.75 3.75 0 0 1-3.75 3.75h-.5C9.364 21.75 2.25 14.636 2.25 5.75v-.5Z" />
                            </svg>
                        </a>
                    </template>
                    <template x-if="seleccionado?.numero_corto">
                        <a :href="'tel:' + limpiarTelefono(seleccionado.numero_corto)"
                            class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center text-amber-600 border border-amber-100"
                            title="Llamar número corporativo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.25 5.25A3.75 3.75 0 0 1 6 1.5h1.26c.82 0 1.52.546 1.72 1.33l.83 3.32a1.75 1.75 0 0 1-.46 1.63l-1.3 1.3a14.5 14.5 0 0 0 6.94 6.94l1.3-1.3a1.75 1.75 0 0 1 1.63-.46l3.32.83c.784.2 1.33.9 1.33 1.72V18a3.75 3.75 0 0 1-3.75 3.75h-.5C9.364 21.75 2.25 14.636 2.25 5.75v-.5Z" />
                            </svg>
                        </a>
                    </template>
                    <template x-if="seleccionado?.email">
                        <a :href="seleccionado && seleccionado.email ? 'mailto:' + seleccionado.email : null"
                            class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center text-gray-700 border border-gray-100"
                            title="Enviar email">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M1.5 6.75A3.75 3.75 0 0 1 5.25 3h13.5A3.75 3.75 0 0 1 22.5 6.75v10.5A3.75 3.75 0 0 1 18.75 21h-13.5A3.75 3.75 0 0 1 1.5 17.25V6.75Zm3.053-.3A.75.75 0 0 0 4.5 7.11v.129l7.5 4.5 7.5-4.5V7.11a.75.75 0 0 0-.053-.66.75.75 0 0 0-1.025-.236L12 10.94 5.578 5.8a.75.75 0 0 0-1.025.236Z" />
                            </svg>
                        </a>
                    </template>
                    {{-- Botón ver perfil --}}
                    <a :href="'{{ url('users') }}/' + seleccionado?.id"
                        class="w-12 h-12 rounded-full bg-white shadow-md flex items-center justify-center text-purple-600 border border-purple-100"
                        title="Ver perfil">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                            <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="px-5 pb-6 space-y-4 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm pb-4">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Nombre</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.nombre || 'Sin nombre'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.nombre"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Nombre">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Primer apellido</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.primer_apellido || 'N/D'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.primer_apellido"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Primer apellido">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Segundo apellido</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.segundo_apellido || 'N/D'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.segundo_apellido"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Segundo apellido">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Email</p>
                        <a x-show="seleccionado && seleccionado.email && !editando"
                            :href="seleccionado && seleccionado.email ? ('mailto:' + seleccionado.email) : null"
                            class="text-blue-700 dark:text-blue-400 font-semibold break-words"
                            x-text="seleccionado && seleccionado.email ? seleccionado.email : ''"></a>
                        <input x-show="editando" type="email" x-model="seleccionado.email"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="correo@ejemplo.com">
                        <p x-show="!seleccionado || !seleccionado.email" class="text-gray-400">Sin email</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Empresa</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.empresa || 'Sin empresa'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.empresa"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Empresa">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Categoría</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.categoria || 'Sin categoría'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.categoria"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Categoría">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Turno</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.turno || 'No definido'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.turno"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Turno">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">DNI</p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold" x-show="!editando" x-text="seleccionado?.dni || 'N/D'"></p>
                        <input x-show="editando" type="text" x-model="seleccionado.dni"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="DNI">
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Móvil personal</p>
                        <template x-if="seleccionado?.movil_personal && !editando">
                            <a :href="'tel:' + limpiarTelefono(seleccionado.movil_personal)"
                                class="text-green-700 dark:text-green-400 font-semibold" x-text="seleccionado.movil_personal"></a>
                        </template>
                        <input x-show="editando" type="text" x-model="seleccionado.movil_personal"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Móvil personal">
                        <p x-show="!seleccionado?.movil_personal" class="text-gray-400">No disponible</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Móvil empresa</p>
                        <template x-if="seleccionado?.movil_empresa && !editando">
                            <a :href="'tel:' + limpiarTelefono(seleccionado.movil_empresa)"
                                class="text-blue-700 dark:text-blue-400 font-semibold" x-text="seleccionado.movil_empresa"></a>
                        </template>
                        <input x-show="editando" type="text" x-model="seleccionado.movil_empresa"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="Móvil empresa">
                        <p x-show="!seleccionado?.movil_empresa" class="text-gray-400">No disponible</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Nº corporativo</p>
                        <template x-if="seleccionado?.numero_corto && !editando">
                            <a :href="'tel:' + limpiarTelefono(seleccionado.numero_corto)"
                                class="text-amber-700 dark:text-amber-400 font-semibold" x-text="seleccionado.numero_corto"></a>
                        </template>
                        <input x-show="editando" type="text" x-model="seleccionado.numero_corto"
                            class="w-full border border-gray-200 dark:border-gray-700 rounded px-2 py-1 text-sm text-gray-800 dark:text-gray-100 dark:bg-gray-700" placeholder="0000">
                        <p x-show="!seleccionado?.numero_corto" class="text-gray-400">No disponible</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>


<script>
    window.usersBaseUrlMobile = '{{ url("/users") }}';
</script>