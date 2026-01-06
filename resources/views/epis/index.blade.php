<x-app-layout>
    <x-slot name="title">EPIs</x-slot>

    <div class="py-6 px-4 bg-slate-100 min-h-screen" x-data="episPage()" x-init="init()">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">EPIs por trabajador</h1>
                    <p class="text-sm text-gray-600 mt-1">Agenda de usuarios con EPIs en posesión.</p>
                </div>

                <div class="flex gap-2 items-end">
                    <div class="relative w-full sm:w-96" data-user-suggest>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Buscar trabajador</label>
                        <input type="text" x-model="query" @input="onQueryChange()"
                            @keydown.escape="closeSuggestions()" @focus="openSuggestions()"
                            placeholder="Nombre, apellidos, DNI, móvil…"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />

                        <!-- Helper / sugerencias -->
                        <div x-cloak x-show="suggestionsOpen"
                            class="absolute z-50 mt-2 w-full bg-white/95 backdrop-blur border border-slate-200 rounded-xl shadow-2xl overflow-hidden">
                            <div class="max-h-80 overflow-y-auto">
                                <template x-if="suggestions.length === 0">
                                    <div class="p-4 text-sm text-gray-600">Sin coincidencias.</div>
                                </template>
                                <template x-for="u in suggestions" :key="u.id">
                                    <button type="button"
                                        class="w-full text-left px-4 py-3 hover:bg-gray-200 transition-colors flex items-center gap-3"
                                        @click="selectUser(u)">
                                        <div
                                            class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <template x-if="u.ruta_imagen">
                                                <img :src="u.ruta_imagen" :alt="`Foto de ${u.nombre_completo}`"
                                                    class="w-full h-full object-cover" />
                                            </template>
                                            <template x-if="!u.ruta_imagen">
                                                <span class="text-gray-600 font-semibold"
                                                    x-text="u.nombre_completo?.slice(0,1)?.toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 truncate"
                                                x-text="u.nombre_completo"></p>
                                            <p class="text-xs text-gray-600 truncate">
                                                <span x-text="u.dni || 'DNI N/D'"></span>
                                                <span class="mx-1">·</span>
                                                <span x-text="u.movil_personal || 'Móvil N/D'"></span>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1 flex flex-col">
                                                <span
                                                    x-text="`Categoría: ${u.categoria?.nombre || 'Sin asignar'}`"></span>
                                                <span x-text="`Empresa: ${u.empresa?.nombre || 'Sin empresa'}`"></span>
                                            </p>
                                        </div>
                                        <span
                                            class="text-xs font-medium text-blue-700 bg-blue-50 rounded-full px-2 py-0.5"
                                            x-text="`${u.epis_en_posesion} EPIs`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <span class="block text-xs font-medium text-transparent mb-1 select-none">.</span>
                        <button type="button"
                            class="px-4 py-2 rounded-lg bg-blue-600 text-white shadow-md hover:shadow-lg hover:bg-blue-700 transition whitespace-nowrap"
                            @click="openCatalog()">
                            Gestionar EPIs
                        </button>
                        <input type="file" class="hidden" x-ref="epiImportInput" accept=".xlsx,.xls"
                            @change="handleImportFile">
                        <button type="button"
                            class="hidden px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 whitespace-nowrap disabled:opacity-50"
                            :disabled="importUploading" @click="$refs.epiImportInput.click()">
                            <span x-text="importUploading ? 'Importando…' : 'Importar Excel'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    class="bg-white rounded-xl border border-slate-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-200">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Usuarios con EPIs</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1" x-text="stats.usuariosConEpis"></p>
                </div>
                <div
                    class="bg-white rounded-xl border border-slate-200 p-4 shadow-md hover:shadow-lg transition-shadow duration-200">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Catálogo</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1" x-text="catalogoCount"></p>
                </div>
            </div>

            <div
                class="bg-white/95 rounded-xl border border-slate-200 p-4 space-y-4 shadow-lg hover:shadow-xl transition-shadow duration-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Filtrar usuarios por EPI</label>
                        <input type="text" x-model="agendaEpiQuery" @input="onAgendaEpiQueryChange()"
                            placeholder="Nombre, codigo o categoria del EPI"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Filtrar por empresa</label>
                        <select x-model="agendaEmpresaId" @change="onAgendaFiltersChange()"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todas</option>
                            @foreach ($empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Filtrar por categoría</label>
                        <select x-model="agendaCategoriaId" @change="onAgendaFiltersChange()"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todas</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50 shadow-sm hover:shadow-md transition"
                        @click="resetAgendaFilters()"
                        :disabled="!agendaEpiQuery && !agendaEmpresaId && !agendaCategoriaId">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <div
                class="bg-white/95 rounded-xl border border-slate-200 overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70">
                    <h2 class="font-semibold text-gray-900">Agenda</h2>
                </div>

                <template x-if="loadingUsers">
                    <div class="p-6 text-gray-700">Cargando…</div>
                </template>

                <template x-if="!loadingUsers && agendaUsers.length === 0">
                    <div class="p-6 text-gray-700">No hay usuarios con EPIs en posesión.</div>
                </template>

                <template x-if="!loadingUsers && agendaUsers.length > 0">
                    <div class="divide-y divide-gray-100">
                        <template x-for="u in agendaUsers" :key="u.id">
                            <div
                                class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-gray-200 group">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div
                                        class="group flex items-center justify-center w-12 h-12 rounded-full overflow-hidden bg-gray-100 border-0 border-transparent group-hover:border-2 group-hover:border-gray-700 transition-all duration-100">
                                        <template x-if="u.ruta_imagen">
                                            <img :src="u.ruta_imagen" :alt="`Foto de ${u.nombre_completo}`"
                                                class="w-full h-full object-cover" />
                                        </template>
                                        <template x-if="!u.ruta_imagen">
                                            <span class="text-gray-600 font-semibold"
                                                x-text="u.nombre_completo?.slice(0,1)?.toUpperCase()"></span>
                                        </template>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <p class="font-semibold text-gray-900 truncate"
                                                x-text="u.nombre_completo">
                                            </p>
                                            <span
                                                class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium"
                                                x-text="`${u.epis_en_posesion} EPIs`"></span>
                                        </div>
                                        <div class="text-sm text-gray-600 flex flex-col sm:flex-row sm:gap-4 mt-1">
                                            <span x-text="`DNI: ${u.dni || 'N/D'}`"></span>
                                            <span class="truncate" x-text="`Email: ${u.email || 'N/D'}`"></span>
                                            <span x-text="`Móvil: ${u.movil_personal || 'N/D'}`"></span>
                                        </div>
                                        <div class="text-sm text-gray-600 flex flex-col sm:flex-row sm:gap-4 mt-1">
                                            <span x-text="`Categoría: ${u.categoria?.nombre || 'Sin asignar'}`"></span>
                                            <span class="truncate"
                                                x-text="`Empresa: ${u.empresa?.nombre || 'Sin empresa'}`"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 shadow-sm hover:shadow-md transition"
                                        @click="openUser(u)">
                                        EPIs
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-400 text-gray-900 hover:bg-gray-600 hover:text-white shadow-sm hover:shadow-md"
                                        @click="openRecentModalForUser(u)">
                                        Últimos
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal -->
        <div x-cloak x-show="modalOpen" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>

            <div
                class="relative w-full sm:max-w-5xl bg-white/95 backdrop-blur rounded-t-2xl sm:rounded-2xl border border-slate-200 shadow-[0_20px_60px_rgba(15,23,42,0.35)] overflow-hidden max-h-[92vh] sm:max-h-[85vh] flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/80 flex items-center justify-between">
                    <div class="min-w-0 w-full">
                        <p class="text-xs uppercase tracking-wide text-gray-500">EPIs</p>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:gap-3">
                            <p class="font-semibold text-gray-900 truncate" x-text="modalTitle"></p>
                            <template x-if="modalTab === 'usuario' && selectedUser">
                                <div
                                    class="text-xs text-gray-600 flex flex-col sm:flex-row sm:items-center sm:gap-3 w-full sm:w-auto">
                                    <span
                                        x-text="`Categoría: ${selectedUser.categoria?.nombre || 'Sin asignar'}`"></span>
                                    <span x-text="`Empresa: ${selectedUser.empresa?.nombre || 'Sin empresa'}`"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <button type="button" class="p-2 rounded-lg shadow-sm hover:shadow-md" @click="closeModal()"
                        aria-label="Cerrar">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 pt-4">
                    <div class="flex gap-2 border-b border-slate-200">
                        <button type="button" class="px-4 py-2 text-sm font-medium rounded-t-lg"
                            :class="modalTab === 'usuario' ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-200'"
                            @click="switchModalTab('usuario')" :disabled="!selectedUser">
                            Usuario
                        </button>
                        <button type="button" class="px-4 py-2 text-sm font-medium rounded-t-lg"
                            :class="modalTab === 'catalogo' ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-200'"
                            @click="switchModalTab('catalogo')">
                            Catálogo
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto space-y-6">
                    <!-- Tab usuario -->
                    <template x-if="modalTab === 'usuario'">
                        <div>
                            <template x-if="!selectedUser">
                                <div
                                    class="rounded-lg border border-dashed border-gray-300 bg-slate-50 p-6 text-gray-700">
                                    Selecciona un usuario para ver/gestionar sus EPIs.
                                </div>
                            </template>

                            <template x-if="selectedUser">
                                <div class="space-y-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <div
                                            class="lg:col-span-2 bg-white/95 border border-slate-200 rounded-xl p-4 shadow-md">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Asignar EPI</p>
                                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
                                                <div class="sm:col-span-2">
                                                    <label
                                                        class="block text-xs font-medium text-gray-700 mb-1">EPI</label>
                                                    <div class="relative" data-epi-suggest>
                                                        <input type="text" x-model="epiAssignQuery"
                                                            @input="onEpiAssignQueryChange()"
                                                            @keydown.escape="closeEpiAssignSuggestions()"
                                                            @focus="openEpiAssignSuggestions()"
                                                            placeholder="Buscar EPI (nombre, código, categoría)…"
                                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />

                                                        <div x-cloak x-show="epiAssignSuggestionsOpen"
                                                            class="absolute z-50 mt-2 w-full bg-white/95 border border-slate-200 shadow-sm rounded-xl shadow-xl overflow-hidden">
                                                            <div class="max-h-80 overflow-y-auto">
                                                                <template x-if="epiAssignSuggestions.length === 0">
                                                                    <div class="p-4 text-sm text-gray-600">Sin
                                                                        coincidencias.</div>
                                                                </template>
                                                                <template x-for="e in epiAssignSuggestions"
                                                                    :key="e.id">
                                                                    <button type="button"
                                                                        class="w-full text-left px-4 py-3 hover:bg-gray-200 flex items-center gap-3"
                                                                        @click="selectEpiToAssign(e)">
                                                                        <div
                                                                            class="w-9 h-9 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                                            <template x-if="e.imagen_url">
                                                                                <img :src="e.imagen_url"
                                                                                    :alt="`Imagen de ${e.nombre}`"
                                                                                    class="w-full h-full object-cover" />
                                                                            </template>
                                                                            <template x-if="!e.imagen_url">
                                                                                <span class="text-gray-500 text-xs">Sin
                                                                                    img</span>
                                                                            </template>
                                                                        </div>
                                                                        <div class="min-w-0 flex-1">
                                                                            <p class="text-sm font-semibold text-gray-900 truncate"
                                                                                x-text="e.nombre"></p>
                                                                            <p class="text-xs text-gray-600 truncate">
                                                                                <span
                                                                                    x-text="e.codigo ? `Código: ${e.codigo}` : 'Sin código'"></span>
                                                                                <span class="mx-1">·</span>
                                                                                <span
                                                                                    x-text="e.categoria ? `Categoría: ${e.categoria}` : 'Sin categoría'"></span>
                                                                            </p>
                                                                        </div>
                                                                        <template x-if="!e.activo">
                                                                            <span
                                                                                class="text-xs font-medium text-gray-700 bg-gray-100 rounded-full px-2 py-0.5">Inactivo</span>
                                                                        </template>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>

                                                        <template x-if="assignForm.epi_id">
                                                            <p class="text-xs text-gray-600 mt-2">
                                                                Seleccionado: <span class="font-medium"
                                                                    x-text="selectedEpiLabel"></span>
                                                            </p>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                                                    <input type="number" min="1"
                                                        x-model.number="assignForm.cantidad"
                                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-xs font-medium text-gray-700 mb-1">Notas</label>
                                                    <input type="text" x-model="assignForm.notas"
                                                        placeholder="Opcional"
                                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                </div>
                                                <div class="sm:col-span-4 flex justify-end">
                                                    <button type="button"
                                                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                                        :disabled="!assignForm.epi_id || assignForm.cantidad < 1 || saving"
                                                        @click="assignEpi()">
                                                        Asignar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white/95 border border-slate-200 rounded-xl p-4 shadow-md">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Resumen</p>
                                            <div class="mt-2 flex items-center gap-3">
                                                <div
                                                    class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <template x-if="selectedUser.ruta_imagen">
                                                        <img :src="selectedUser.ruta_imagen"
                                                            :alt="`Foto de ${selectedUser.nombre_completo}`"
                                                            class="w-full h-full object-cover" />
                                                    </template>
                                                    <template x-if="!selectedUser.ruta_imagen">
                                                        <span class="text-gray-600 font-semibold"
                                                            x-text="selectedUser.nombre_completo?.slice(0,1)?.toUpperCase()"></span>
                                                    </template>
                                                </div>
                                                <div class="min-w-0 w-full">
                                                    <div
                                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                        <p class="text-lg font-semibold text-gray-900 truncate"
                                                            x-text="selectedUser.nombre_completo"></p>
                                                        <div
                                                            class="text-xs text-gray-600 flex flex-col sm:flex-row sm:items-center sm:gap-3">
                                                        </div>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mt-0.5"
                                                        x-text="`DNI: ${selectedUser.dni || 'N/D'}`"></p>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-2"
                                                x-text="`Email: ${selectedUser.email || 'N/D'}`"></p>
                                            <p class="text-sm text-gray-600"
                                                x-text="`Móvil: ${selectedUser.movil_personal || 'N/D'}`"></p>
                                            <p class="text-sm text-gray-600 mt-3">
                                                En posesión: <span class="font-semibold"
                                                    x-text="userTotalEnPosesion"></span>
                                            </p>
                                            <div class="mt-4">
                                                <button type="button"
                                                    class="w-full px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800"
                                                    @click="openRecentModal()">
                                                    Últimos asignados
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="bg-white/95 border border-slate-200 rounded-xl overflow-hidden shadow-md">
                                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/70">
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                <h3 class="font-semibold text-gray-900">EPIs en posesión</h3>
                                                <input type="text" x-model="userEpiFilterQuery"
                                                    placeholder="Filtrar por producto…"
                                                    class="w-full sm:w-72 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                            </div>
                                        </div>

                                        <template x-if="loadingAsignaciones">
                                            <div class="p-6 text-gray-700">Cargando…</div>
                                        </template>

                                        <template x-if="!loadingAsignaciones && asignacionesEnPosesion.length === 0">
                                            <div class="p-6 text-gray-700">Este usuario no tiene EPIs en posesión.
                                            </div>
                                        </template>

                                        <template x-if="!loadingAsignaciones && userEpiGroupsFiltered.length > 0">
                                            <div class="divide-y divide-gray-100">
                                                <template x-for="g in userEpiGroupsFiltered" :key="g.epi.id">
                                                    <div>
                                                        <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 cursor-pointer"
                                                            @click="toggleEpiGroup(g.epi.id)"
                                                            :class="expandedEpiId === Number(g.epi.id) ? 'bg-slate-50' : ''">
                                                            <div class="flex items-center gap-4 min-w-0">
                                                                <div
                                                                    class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                                    <template x-if="g.epi.imagen_url">
                                                                        <img :src="g.epi.imagen_url"
                                                                            :alt="`Imagen de ${g.epi.nombre}`"
                                                                            class="w-full h-full object-cover" />
                                                                    </template>
                                                                    <template x-if="!g.epi.imagen_url">
                                                                        <span class="text-gray-500 text-xs">Sin
                                                                            img</span>
                                                                    </template>
                                                                </div>
                                                                <div class="min-w-0">
                                                                    <p class="font-semibold text-gray-900 truncate">
                                                                        <span x-text="g.epi.nombre"></span>
                                                                        <template x-if="g.epi.codigo">
                                                                            <span class="text-sm text-gray-600"
                                                                                x-text="` (${g.epi.codigo})`"></span>
                                                                        </template>
                                                                    </p>
                                                                    <p class="text-sm text-gray-600">
                                                                        Cantidad: <span class="font-semibold"
                                                                            x-text="g.total_en_posesion"></span>
                                                                    </p>
                                                                    <p class="text-xs text-gray-500 mt-1">Click para
                                                                        ver historial</p>
                                                                </div>
                                                            </div>

                                                            <button type="button"
                                                                class="text-sm font-medium px-3 py-2 rounded-lg bg-gradient-to-tr from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800"
                                                                @click.stop="toggleEpiGroup(g.epi.id)">
                                                                <span
                                                                    x-text="expandedEpiId === Number(g.epi.id) ? 'Ocultar' : 'Ver'"></span>
                                                            </button>
                                                        </div>

                                                        <div :id="`epi-details-${g.epi.id}`"
                                                            x-show="expandedEpiId === Number(g.epi.id)" x-transition
                                                            class="px-4 sm:px-6 py-4 border-t border-gray-300 bg-gray-300">
                                                            <div
                                                                class="bg-white/95 border border-slate-200 shadow-sm rounded-xl overflow-hidden mt-2">
                                                                <div
                                                                    class="px-4 py-3 border-b border-gray-300 flex items-center justify-between">
                                                                    <p class="text-sm font-semibold text-gray-900">
                                                                        Historial de asignaciones</p>
                                                                    <p class="text-xs text-gray-600">
                                                                        Devueltos: <span class="font-semibold"
                                                                            x-text="g.total_devueltos"></span>
                                                                    </p>
                                                                </div>

                                                                <div class="divide-y divide-gray-100">
                                                                    <template x-for="a in g.asignaciones"
                                                                        :key="a.id">
                                                                        <div
                                                                            class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                                            <div class="min-w-0">
                                                                                <p
                                                                                    class="text-sm font-medium text-gray-900">
                                                                                    <span
                                                                                        x-text="formatDate(a.fecha_asignacion)"></span>
                                                                                    <span
                                                                                        class="mx-2 text-gray-300">|</span>
                                                                                    <span
                                                                                        x-text="`Cantidad: ${a.cantidad}`"></span>
                                                                                </p>
                                                                                <p class="text-xs text-gray-600 mt-1">
                                                                                    <template x-if="a.devuelto_en">
                                                                                        <span
                                                                                            x-text="`Devuelto: ${formatDate(a.devuelto_en)}`"></span>
                                                                                    </template>
                                                                                    <template x-if="!a.devuelto_en">
                                                                                        <span
                                                                                            class="text-green-700 font-medium">En
                                                                                            posesión</span>
                                                                                    </template>
                                                                                    <span class="mx-1">-</span>
                                                                                    <span class="truncate"
                                                                                        x-text="`Notas: ${a.notas || 'Sin notas'}`"></span>
                                                                                </p>
                                                                            </div>

                                                                            <template x-if="!a.devuelto_en">
                                                                                <div class="flex gap-2 flex-wrap">
                                                                                    <button type="button"
                                                                                        class="px-2 py-1 rounded-lg bg-white border border-gray-300 text-gray-900 hover:bg-gray-200 disabled:opacity-50"
                                                                                        :disabled="saving"
                                                                                        @click.stop="openFechaModal(a, 'entrega')">
                                                                                        <svg class="h-6 w-6"
                                                                                            fill="#000000"
                                                                                            viewBox="0 0 122.88 122.88"
                                                                                            version="1.1"
                                                                                            id="Layer_1"
                                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                                            xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                                            style="enable-background:new 0 0 122.88 122.88"
                                                                                            xml:space="preserve">
                                                                                            <g id="SVGRepo_bgCarrier"
                                                                                                stroke-width="0"></g>
                                                                                            <g id="SVGRepo_tracerCarrier"
                                                                                                stroke-linecap="round"
                                                                                                stroke-linejoin="round">
                                                                                            </g>
                                                                                            <g
                                                                                                id="SVGRepo_iconCarrier">
                                                                                                <g>
                                                                                                    <path
                                                                                                        d="M81.61,4.73c0-2.61,2.58-4.73,5.77-4.73c3.19,0,5.77,2.12,5.77,4.73v20.72c0,2.61-2.58,4.73-5.77,4.73 c-3.19,0-5.77-2.12-5.77-4.73V4.73L81.61,4.73z M66.11,103.81c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2H81.9 c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H66.11L66.11,103.81z M15.85,67.09c-0.34,0-0.61-1.43-0.61-3.2 c0-1.77,0.27-3.2,0.61-3.2h15.79c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H15.85L15.85,67.09z M40.98,67.09 c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2h15.79c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H40.98 L40.98,67.09z M66.11,67.09c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2H81.9c0.34,0,0.61,1.43,0.61,3.2 c0,1.77-0.27,3.2-0.61,3.2H66.11L66.11,67.09z M91.25,67.09c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2h15.79 c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H91.25L91.25,67.09z M15.85,85.45c-0.34,0-0.61-1.43-0.61-3.2 c0-1.77,0.27-3.2,0.61-3.2h15.79c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H15.85L15.85,85.45z M40.98,85.45 c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2h15.79c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H40.98 L40.98,85.45z M66.11,85.45c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2H81.9c0.34,0,0.61,1.43,0.61,3.2 c0,1.77-0.27,3.2-0.61,3.2H66.11L66.11,85.45z M91.25,85.45c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2h15.79 c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H91.25L91.25,85.45z M15.85,103.81c-0.34,0-0.61-1.43-0.61-3.2 c0-1.77,0.27-3.2,0.61-3.2h15.79c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H15.85L15.85,103.81z M40.98,103.81 c-0.34,0-0.61-1.43-0.61-3.2c0-1.77,0.27-3.2,0.61-3.2h15.79c0.34,0,0.61,1.43,0.61,3.2c0,1.77-0.27,3.2-0.61,3.2H40.98 L40.98,103.81z M29.61,4.73c0-2.61,2.58-4.73,5.77-4.73s5.77,2.12,5.77,4.73v20.72c0,2.61-2.58,4.73-5.77,4.73 s-5.77-2.12-5.77-4.73V4.73L29.61,4.73z M6.4,45.32h110.07V21.47c0-0.8-0.33-1.53-0.86-2.07c-0.53-0.53-1.26-0.86-2.07-0.86H103 c-1.77,0-3.2-1.43-3.2-3.2c0-1.77,1.43-3.2,3.2-3.2h10.55c2.57,0,4.9,1.05,6.59,2.74c1.69,1.69,2.74,4.02,2.74,6.59v27.06v65.03 c0,2.57-1.05,4.9-2.74,6.59c-1.69,1.69-4.02,2.74-6.59,2.74H9.33c-2.57,0-4.9-1.05-6.59-2.74C1.05,118.45,0,116.12,0,113.55V48.52 V21.47c0-2.57,1.05-4.9,2.74-6.59c1.69-1.69,4.02-2.74,6.59-2.74H20.6c1.77,0,3.2,1.43,3.2,3.2c0,1.77-1.43,3.2-3.2,3.2H9.33 c-0.8,0-1.53,0.33-2.07,0.86c-0.53,0.53-0.86,1.26-0.86,2.07V45.32L6.4,45.32z M116.48,51.73H6.4v61.82c0,0.8,0.33,1.53,0.86,2.07 c0.53,0.53,1.26,0.86,2.07,0.86h104.22c0.8,0,1.53-0.33,2.07-0.86c0.53-0.53,0.86-1.26,0.86-2.07V51.73L116.48,51.73z M50.43,18.54 c-1.77,0-3.2-1.43-3.2-3.2c0-1.77,1.43-3.2,3.2-3.2h21.49c1.77,0,3.2,1.43,3.2,3.2c0,1.77-1.43,3.2-3.2,3.2H50.43L50.43,18.54z">
                                                                                                    </path>
                                                                                                </g>
                                                                                            </g>
                                                                                        </svg>
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-50"
                                                                                        :disabled="saving"
                                                                                        @click.stop="markReturned(a)">
                                                                                        Marcar devuelto
                                                                                    </button>
                                                                                </div>
                                                                            </template>
                                                                            <template x-if="a.devuelto_en">
                                                                                <button type="button"
                                                                                    class="px-2 py-1 rounded-lg bg-white border border-gray-300 text-gray-900 hover:bg-gray-200 disabled:opacity-50"
                                                                                    :disabled="saving"
                                                                                    @click.stop="openFechaModal(a, 'devolucion')">
                                                                                    <svg viewBox="0 0 24 24"
                                                                                        class="h-7 w-7" fill="none"
                                                                                        xmlns="http://www.w3.org/2000/svg">
                                                                                        <g id="SVGRepo_bgCarrier"
                                                                                            stroke-width="0"></g>
                                                                                        <g id="SVGRepo_tracerCarrier"
                                                                                            stroke-linecap="round"
                                                                                            stroke-linejoin="round">
                                                                                        </g>
                                                                                        <g id="SVGRepo_iconCarrier">
                                                                                            <path
                                                                                                d="M6.94028 2C7.35614 2 7.69326 2.32421 7.69326 2.72414V4.18487C8.36117 4.17241 9.10983 4.17241 9.95219 4.17241H13.9681C14.8104 4.17241 15.5591 4.17241 16.227 4.18487V2.72414C16.227 2.32421 16.5641 2 16.98 2C17.3958 2 17.733 2.32421 17.733 2.72414V4.24894C19.178 4.36022 20.1267 4.63333 20.8236 5.30359C21.5206 5.97385 21.8046 6.88616 21.9203 8.27586L22 9H2.92456H2V8.27586C2.11571 6.88616 2.3997 5.97385 3.09665 5.30359C3.79361 4.63333 4.74226 4.36022 6.1873 4.24894V2.72414C6.1873 2.32421 6.52442 2 6.94028 2Z"
                                                                                                fill="#000000"></path>
                                                                                            <path opacity="0.5"
                                                                                                d="M21.9995 14.0001V12.0001C21.9995 11.161 21.9963 9.66527 21.9834 9H2.00917C1.99626 9.66527 1.99953 11.161 1.99953 12.0001V14.0001C1.99953 17.7713 1.99953 19.6569 3.1711 20.8285C4.34267 22.0001 6.22829 22.0001 9.99953 22.0001H13.9995C17.7708 22.0001 19.6564 22.0001 20.828 20.8285C21.9995 19.6569 21.9995 17.7713 21.9995 14.0001Z"
                                                                                                fill="#000000"></path>
                                                                                            <path
                                                                                                d="M18 17C18 17.5523 17.5523 18 17 18C16.4477 18 16 17.5523 16 17C16 16.4477 16.4477 16 17 16C17.5523 16 18 16.4477 18 17Z"
                                                                                                fill="#000000"></path>
                                                                                            <path
                                                                                                d="M18 13C18 13.5523 17.5523 14 17 14C16.4477 14 16 13.5523 16 13C16 12.4477 16.4477 12 17 12C17.5523 12 18 12.4477 18 13Z"
                                                                                                fill="#000000"></path>
                                                                                            <path
                                                                                                d="M13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17Z"
                                                                                                fill="#000000"></path>
                                                                                            <path
                                                                                                d="M13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13Z"
                                                                                                fill="#000000"></path>
                                                                                            <path
                                                                                                d="M8 17C8 17.5523 7.55228 18 7 18C6.44772 18 6 17.5523 6 17C6 16.4477 6.44772 16 7 16C7.55228 16 8 16.4477 8 17Z"
                                                                                                fill="#000000"></path>
                                                                                            <path
                                                                                                d="M8 13C8 13.5523 7.55228 14 7 14C6.44772 14 6 13.5523 6 13C6 12.4477 6.44772 12 7 12C7.55228 12 8 12.4477 8 13Z"
                                                                                                fill="#000000"></path>
                                                                                        </g>
                                                                                    </svg>
                                                                                </button>
                                                                            </template>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Tab catálogo -->
                    <template x-if="modalTab === 'catalogo'">
                        <div class="space-y-6">
                            <div class="bg-white/95 border border-slate-200 shadow-sm rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Crear EPI</p>
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-6 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                                        <input type="text" x-model="epiCreate.nombre"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Código</label>
                                        <input type="text" x-model="epiCreate.codigo" placeholder="Opcional"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                                        <input type="text" x-model="epiCreate.categoria" placeholder="Opcional"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Activo</label>
                                        <label class="inline-flex items-center gap-2 mt-2">
                                            <input type="checkbox" x-model="epiCreate.activo"
                                                class="rounded border-gray-300" />
                                            <span class="text-sm text-gray-700">Sí</span>
                                        </label>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Descripción</label>
                                        <input type="text" x-model="epiCreate.descripcion" placeholder="Opcional"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Imagen</label>
                                        <input type="file" accept="image/*"
                                            @change="epiCreateFile = $event.target.files?.[0] || null"
                                            class="w-full text-sm" />
                                    </div>
                                    <div class="sm:col-span-6 flex justify-end">
                                        <button type="button"
                                            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                            :disabled="!epiCreate.nombre || saving" @click="createEpi()">
                                            Crear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white/95 border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-300">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <h3 class="font-semibold text-gray-900">EPIs en base de datos</h3>
                                        <div class="flex gap-2">
                                            <button type="button"
                                                class="px-4 py-2 rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-200"
                                                @click="openCompras()">
                                                Compras
                                            </button>
                                            <button type="button"
                                                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                                                @click="openCompraCreate()">
                                                Hacer compra
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center gap-2">
                                        <input id="epis-show-inactive" type="checkbox" x-model="showInactiveEpis"
                                            class="rounded border-gray-300" />
                                        <label for="epis-show-inactive" class="text-sm text-gray-700">Mostrar
                                            inactivos</label>
                                    </div>
                                </div>

                                <template x-if="loadingEpis">
                                    <div class="p-6 text-gray-700">Cargando…</div>
                                </template>

                                <template x-if="!loadingEpis && epis.length === 0">
                                    <div class="p-6 text-gray-700">No hay EPIs creados.</div>
                                </template>

                                <template x-if="!loadingEpis && catalogEpis.length > 0">
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="e in catalogEpis" :key="e.id">
                                            <div class="p-4 sm:p-6" x-data="{ editOpen: false, form: { ...e } }">
                                                <div
                                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                                    <div class="flex items-center gap-4 min-w-0">
                                                        <div
                                                            class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                            <template x-if="e.imagen_url">
                                                                <img :src="e.imagen_url"
                                                                    :alt="`Imagen de ${e.nombre}`"
                                                                    class="w-full h-full object-cover" />
                                                            </template>
                                                            <template x-if="!e.imagen_url">
                                                                <span class="text-gray-500 text-xs">Sin img</span>
                                                            </template>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="font-semibold text-gray-900 truncate">
                                                                <span x-text="e.nombre"></span>
                                                                <template x-if="!e.activo">
                                                                    <span
                                                                        class="ml-2 text-xs font-medium text-gray-600">(inactivo)</span>
                                                                </template>
                                                            </p>
                                                            <p class="text-sm text-gray-600">
                                                                <template x-if="e.codigo"><span
                                                                        x-text="`Código: ${e.codigo}`"></span></template>
                                                                <template x-if="e.codigo && e.categoria"><span> ·
                                                                    </span></template>
                                                                <template x-if="e.categoria"><span
                                                                        x-text="`Categoría: ${e.categoria}`"></span></template>
                                                            </p>
                                                            <template x-if="e.descripcion">
                                                                <p class="text-sm text-gray-600 mt-1"
                                                                    x-text="e.descripcion"></p>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-2">
                                                        <button type="button"
                                                            class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-900 text-sm font-medium"
                                                            @click="editOpen = !editOpen">
                                                            Editar
                                                        </button>
                                                        <button type="button"
                                                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-medium disabled:opacity-50"
                                                            :disabled="saving" @click="deleteEpi(e)">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </div>

                                                <div x-cloak x-show="editOpen"
                                                    class="mt-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                                                        <div class="sm:col-span-2">
                                                            <label
                                                                class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                                                            <input type="text" x-model="form.nombre"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-xs font-medium text-gray-700 mb-1">Código</label>
                                                            <input type="text" x-model="form.codigo"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <label
                                                                class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                                                            <input type="text" x-model="form.categoria"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-xs font-medium text-gray-700 mb-1">Activo</label>
                                                            <label class="inline-flex items-center gap-2 mt-2">
                                                                <input type="checkbox" x-model="form.activo"
                                                                    class="rounded border-gray-300" />
                                                                <span class="text-sm text-gray-700">Sí</span>
                                                            </label>
                                                        </div>
                                                        <div class="sm:col-span-4">
                                                            <label
                                                                class="block text-xs font-medium text-gray-700 mb-1">Descripción</label>
                                                            <input type="text" x-model="form.descripcion"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <label
                                                                class="block text-xs font-medium text-gray-700 mb-1">Nueva
                                                                imagen</label>
                                                            <input type="file" accept="image/*"
                                                                @change="form._file = $event.target.files?.[0] || null"
                                                                class="w-full text-sm" />
                                                        </div>
                                                        <div class="sm:col-span-6 flex justify-end">
                                                            <button type="button"
                                                                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                                                :disabled="!form.nombre || saving"
                                                                @click="updateEpi(e.id, form)">
                                                                Guardar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <!-- Modal: últimos asignados -->
        <div x-cloak x-show="recentModalOpen" x-transition.opacity
            class="fixed inset-0 z-[20000] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeRecentModal()"></div>

            <div
                class="relative w-full sm:max-w-3xl bg-white/95 backdrop-blur border border-slate-200 rounded-t-2xl sm:rounded-2xl shadow-[0_20px_60px_rgba(15,23,42,0.35)] overflow-hidden max-h-[92vh] sm:max-h-[85vh] flex flex-col">
                <div
                    class="px-6 py-4 border-b border-slate-200 bg-gradient-to-tr from-gray-700 to-gray-800 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-200">Historial de EPIs</p>
                        <p class="font-semibold text-white truncate"
                            x-text="recentModalUser?.nombre_completo || 'Usuario'"></p>
                    </div>

                    <button type="button" class="p-2 rounded-lg shadow-sm hover:shadow-md"
                        @click="closeRecentModal()" aria-label="Cerrar">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="white" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-gray-300 flex items-center justify-between">
                    <p class="text-sm text-gray-700" x-text="recentModalSummary"></p>
                    <div class="flex items-center gap-2">
                        <button type="button"
                            class="px-3 py-1.5 rounded-lg bg-gradient-to-tr from-gray-300 to-gray-400 hover:from-gray-400 hover:to-gray-500 text-sm disabled:opacity-50"
                            :disabled="recentModalLoading || recentModalPage <= 1"
                            @click="loadRecentModalPage(recentModalPage - 1)">
                            Anterior
                        </button>
                        <button type="button"
                            class="px-3 py-1.5 rounded-lg bg-gradient-to-tr from-gray-300 to-gray-400 hover:from-gray-400 hover:to-gray-500 text-sm disabled:opacity-50"
                            :disabled="recentModalLoading || recentModalPage >= recentModalLastPage"
                            @click="loadRecentModalPage(recentModalPage + 1)">
                            Siguiente
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto">
                    <template x-if="recentModalLoading">
                        <div class="p-6 text-gray-700">Cargando…</div>
                    </template>

                    <template x-if="!recentModalLoading && recentModalItems.length === 0">
                        <div class="p-6 text-gray-700">Sin movimientos.</div>
                    </template>

                    <div class="space-y-3" x-show="!recentModalLoading && recentModalItems.length > 0">
                        <template x-for="a in recentModalItems" :key="a.id">
                            <div
                                class="p-4 border border-slate-200 rounded-xl hover:bg-gray-200 flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <template x-if="a.epi?.imagen_url">
                                        <img :src="a.epi.imagen_url" :alt="`Imagen de ${a.epi.nombre}`"
                                            class="w-full h-full object-cover" />
                                    </template>
                                    <template x-if="!a.epi?.imagen_url">
                                        <span class="text-gray-500 text-xs">Sin img</span>
                                    </template>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 truncate"
                                        x-text="a.epi?.nombre || 'EPI'"></p>
                                    <p class="text-xs text-gray-600 truncate mt-0.5"
                                        x-text="`Notas: ${a.notas || 'Sin notas'}`">
                                    </p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <span x-text="`Entregado: ${formatDate(a.fecha_asignacion)}`"></span>
                                        <span class="mx-1">·</span>
                                        <span x-text="`Cantidad: ${a.cantidad}`"></span>
                                    </p>
                                    <template x-if="a.devuelto_en">
                                        <p class="text-xs text-gray-600">
                                            <span x-text="`Devuelto: ${formatDate(a.devuelto_en)}`"></span>
                                        </p>
                                    </template>
                                    <template x-if="!a.devuelto_en">
                                        <p class="text-xs text-green-700 font-medium">En posesión</p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal: editar fechas de asignacion -->
        <div x-cloak x-show="editFechaModalOpen" x-transition.opacity
            class="fixed inset-0 z-[20050] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeFechaModal()"></div>

            <div
                class="relative w-full sm:max-w-lg bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[85vh] flex flex-col">
                <div class="px-5 py-4 border-b border-gray-300 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-500">EPIs</p>
                        <p class="font-semibold text-gray-900 truncate"
                            x-text="editFechaTipo === 'entrega' ? 'Editar fecha de entrega' : 'Editar fecha de devoluci?n'">
                        </p>
                    </div>

                    <button type="button" class="p-2 rounded-lg hover:bg-gray-200" @click="closeFechaModal()"
                        aria-label="Cerrar">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 space-y-4 overflow-y-auto">
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                        <p class="text-sm text-gray-700">Fecha actual:</p>
                        <p class="text-lg font-semibold text-gray-900" x-text="editFechaAnterior || 'N/D'"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"
                            x-text="editFechaTipo === 'entrega' ? 'Nueva fecha de entrega' : 'Nueva fecha de devoluci?n'"></label>
                        <input type="date"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            x-model="editFechaNueva">
                        <p class="text-xs text-gray-500 mt-1">Si dejas el campo vac?o no se aplicar? ning?n cambio.</p>
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-gray-300 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200"
                        @click="closeFechaModal()">
                        Cancelar
                    </button>
                    <button type="button"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                        :disabled="editFechaSaving" @click="saveFechaAsignacion()">
                        <span x-text="editFechaSaving ? 'Guardando?' : 'Guardar'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: compras -->
        <div x-cloak x-show="comprasOpen" x-transition.opacity
            class="fixed inset-0 z-[21000] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeCompras()"></div>

            <div
                class="relative w-full sm:max-w-4xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] sm:max-h-[85vh] flex flex-col">
                <div class="px-6 py-4 border-b border-gray-300 flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Compras</p>
                        <p class="font-semibold text-gray-900">Compras de EPIs</p>
                    </div>
                    <button type="button" class="p-2 rounded-lg hover:bg-gray-200" @click="closeCompras()"
                        aria-label="Cerrar">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-3 border-b border-gray-300 flex items-center justify-between">
                    <p class="text-sm text-gray-700"
                        x-text="comprasLoading ? 'Cargando…' : `${compras.length} compras`"></p>
                    <button type="button" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                        @click="openCompraCreate()">
                        Hacer compra
                    </button>
                </div>

                <div class="px-6 py-4 border-b border-gray-300">
                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Fecha (día)</label>
                            <input type="date" x-model="comprasFilterDate" @change="refreshCompras()"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Buscar por producto</label>
                            <div class="relative" data-compras-filter-epi-suggest>
                                <input type="text" x-model="comprasFilterEpiQuery"
                                    @input="onComprasFilterEpiQueryChange()"
                                    @keydown.escape="closeComprasFilterEpiSuggestions()"
                                    @focus="openComprasFilterEpiSuggestions()"
                                    placeholder="Nombre, código o categoría…"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />

                                <div x-show="comprasFilterEpiSuggestionsOpen"
                                    class="absolute z-50 mt-2 w-full bg-white/95 border border-slate-200 shadow-sm rounded-xl shadow-xl overflow-hidden">
                                    <div class="max-h-80 overflow-y-auto">
                                        <template x-if="comprasFilterEpiSuggestions.length === 0">
                                            <div class="p-4 text-sm text-gray-600">Sin coincidencias.</div>
                                        </template>
                                        <template x-for="e in comprasFilterEpiSuggestions" :key="e.id">
                                            <button type="button"
                                                class="w-full text-left px-4 py-3 hover:bg-gray-200 flex items-center gap-3"
                                                @click="selectComprasFilterEpi(e)">
                                                <div
                                                    class="w-9 h-9 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <template x-if="e.imagen_url">
                                                        <img :src="e.imagen_url" :alt="`Imagen de ${e.nombre}`"
                                                            class="w-full h-full object-cover" />
                                                    </template>
                                                    <template x-if="!e.imagen_url">
                                                        <span class="text-gray-500 text-xs">Sin img</span>
                                                    </template>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-semibold text-gray-900 truncate"
                                                        x-text="e.nombre"></p>
                                                    <p class="text-xs text-gray-600 truncate">
                                                        <span
                                                            x-text="e.codigo ? `Código: ${e.codigo}` : 'Sin código'"></span>
                                                        <span class="mx-1">·</span>
                                                        <span
                                                            x-text="e.categoria ? `Categoría: ${e.categoria}` : 'Sin categoría'"></span>
                                                    </p>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <template x-if="comprasFilterEpiId">
                                    <div class="mt-2 flex items-center justify-between gap-2">
                                        <p class="text-xs text-gray-600 truncate">
                                            Filtrando por: <span class="font-medium"
                                                x-text="comprasFilterEpiLabel"></span>
                                        </p>
                                        <button type="button"
                                            class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200"
                                            @click="clearComprasFilterEpi()">
                                            Quitar filtro
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto">
                    <template x-if="comprasLoading">
                        <div class="p-6 text-gray-700">Cargando…</div>
                    </template>

                    <template x-if="!comprasLoading && compras.length === 0">
                        <div class="p-6 text-gray-700">No hay compras registradas.</div>
                    </template>

                    <div class="space-y-3" x-show="!comprasLoading && compras.length > 0">
                        <template x-for="c in compras" :key="c.id">
                            <div class="p-4 border rounded-xl"
                                :class="c.items?.some(it => it.precio_unitario === null || it.precio_unitario === undefined ||
                                    it.precio_unitario === '') ? 'border-yellow-400' : 'border-slate-200'">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate"
                                            x-text="`Compra del ${formatDateTime(c.created_at)}`"></p>
                                        <p class="text-xs text-gray-600 mt-1">
                                            <span x-text="`Productos: ${c.productos ?? 0}`"></span>
                                            <span class="mx-1">·</span>
                                            <span x-text="`EPIs: ${c.unidades ?? 0}`"></span>
                                            <span class="mx-1">·</span>
                                            <span x-text="`Total: ${formatMoney(c.total ?? 0)}`"></span>
                                            <span class="mx-1">·</span>
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="c.estado === 'comprada' ? 'bg-green-100 text-green-800' :
                                                    'bg-yellow-100 text-yellow-800'">
                                                <span
                                                    x-text="c.estado === 'comprada' ? 'comprada' : 'pendiente'"></span>
                                            </span>
                                        </p>
                                        <template
                                            x-if="c.items?.some(it => it.precio_unitario === null || it.precio_unitario === undefined || it.precio_unitario === '')">
                                            <p class="text-xs text-yellow-800 mt-2">Faltan precios en algunos
                                                productos.</p>
                                        </template>
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="button"
                                            class="px-4 py-2 rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-200"
                                            @click="openCompraEdit(c.id)">
                                            Editar
                                        </button>
                                        <button type="button"
                                            class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800"
                                            @click="toggleCompraEstado(c)">
                                            <span
                                                x-text="c.estado === 'comprada' ? 'Marcar pendiente' : 'Marcar comprada'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: crear/editar compra -->
        <div x-cloak x-show="compraModalOpen" x-transition.opacity
            class="fixed inset-0 z-[22000] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeCompraModal()"></div>

            <div
                class="relative w-full sm:max-w-5xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] sm:max-h-[85vh] flex flex-col">
                <div class="px-6 py-4 border-b border-gray-300 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-500"
                            x-text="compraMode === 'create' ? 'Nueva compra' : 'Editar compra'"></p>
                        <p class="font-semibold text-gray-900 truncate"
                            x-text="compraMode === 'create' ? 'Hacer compra' : `Compra #${compraId}`"></p>
                    </div>
                    <button type="button" class="p-2 rounded-lg hover:bg-gray-200" @click="closeCompraModal()"
                        aria-label="Cerrar">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6">
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-900">Añadir EPIs a la compra</p>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" x-model="compraEstadoComprada"
                                    class="rounded border-gray-300" />
                                <span class="text-sm text-gray-700">Marcar como comprada</span>
                            </label>
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-6 gap-3">
                            <div class="sm:col-span-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">EPI</label>
                                <div class="relative" data-compra-epi-suggest>
                                    <input type="text" x-model="compraEpiQuery" @input="onCompraEpiQueryChange()"
                                        @keydown.escape="closeCompraEpiSuggestions()"
                                        @focus="openCompraEpiSuggestions()"
                                        placeholder="Buscar EPI (nombre, código, categoría)…"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />

                                    <div x-show="compraEpiSuggestionsOpen"
                                        class="absolute z-50 mt-2 w-full bg-white/95 border border-slate-200 shadow-sm rounded-xl shadow-xl overflow-hidden">
                                        <div class="max-h-80 overflow-y-auto">
                                            <template x-if="compraEpiSuggestions.length === 0">
                                                <div class="p-4 text-sm text-gray-600">Sin coincidencias.</div>
                                            </template>
                                            <template x-for="e in compraEpiSuggestions" :key="e.id">
                                                <button type="button"
                                                    class="w-full text-left px-4 py-3 hover:bg-gray-200 flex items-center gap-3"
                                                    @click="addCompraItem(e)">
                                                    <div
                                                        class="w-9 h-9 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                        <template x-if="e.imagen_url">
                                                            <img :src="e.imagen_url" :alt="`Imagen de ${e.nombre}`"
                                                                class="w-full h-full object-cover" />
                                                        </template>
                                                        <template x-if="!e.imagen_url">
                                                            <span class="text-gray-500 text-xs">Sin img</span>
                                                        </template>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-semibold text-gray-900 truncate"
                                                            x-text="e.nombre"></p>
                                                        <p class="text-xs text-gray-600 truncate">
                                                            <span
                                                                x-text="e.codigo ? `Código: ${e.codigo}` : 'Sin código'"></span>
                                                            <span class="mx-1">·</span>
                                                            <span
                                                                x-text="e.categoria ? `Categoría: ${e.categoria}` : 'Sin categoría'"></span>
                                                        </p>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ticket (opcional)</label>
                                <input type="file" accept="image/*,application/pdf"
                                    @change="compraTicketFile = $event.target.files?.[0] || null"
                                    class="w-full text-sm" />
                                <template x-if="compraTicketUrl">
                                    <a class="text-xs text-blue-700 hover:underline mt-1 inline-block" target="_blank"
                                        :href="compraTicketUrl">Ver ticket actual</a>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/95 border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-300 flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">Items</h3>
                                <p class="text-xs text-gray-600 mt-1">
                                    <span x-text="`Productos: ${compraTotalProductos}`"></span>
                                    <span class="mx-1">·</span>
                                    <span x-text="`EPIs: ${compraTotalUnidades}`"></span>
                                    <span class="mx-1">·</span>
                                    <span x-text="`Total: ${formatMoney(compraTotalPrecio)}`"></span>
                                </p>
                            </div>
                            <button type="button"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                :disabled="compraSaving || compraItems.length === 0" @click="saveCompra()">
                                Guardar
                            </button>
                        </div>

                        <template x-if="compraItems.length === 0">
                            <div class="p-6 text-gray-700">Añade EPIs para crear la compra.</div>
                        </template>

                        <div class="divide-y divide-gray-100" x-show="compraItems.length > 0">
                            <template x-for="(it, idx) in compraItems" :key="it.epi_id">
                                <div
                                    class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <div
                                            class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <template x-if="it.epi?.imagen_url">
                                                <img :src="it.epi.imagen_url" :alt="`Imagen de ${it.epi.nombre}`"
                                                    class="w-full h-full object-cover" />
                                            </template>
                                            <template x-if="!it.epi?.imagen_url">
                                                <span class="text-gray-500 text-xs">Sin img</span>
                                            </template>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate"
                                                x-text="it.epi?.nombre || 'EPI'"></p>
                                            <p class="text-xs text-gray-600 truncate">
                                                <span
                                                    x-text="it.epi?.codigo ? `Código: ${it.epi.codigo}` : 'Sin código'"></span>
                                                <span class="mx-1">·</span>
                                                <span
                                                    x-text="it.epi?.categoria ? `Categoría: ${it.epi.categoria}` : 'Sin categoría'"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                                            <input type="number" min="1" x-model.number="it.cantidad"
                                                class="w-28 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-700 mb-1">Precio/epi</label>
                                            <input type="number" min="0" step="0.01"
                                                x-model.number="it.precio_unitario" placeholder="Opcional"
                                                class="w-36 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                        </div>
                                        <button type="button"
                                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700"
                                            @click="removeCompraItem(idx)">
                                            Quitar
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script data-navigate-once>
        function episPage() {
            return {
                query: '',
                suggestionsOpen: false,
                suggestions: [],
                allUsers: [],
                agendaUsers: [],
                loadingUsers: true,
                importUploading: false,

                modalOpen: false,
                modalTab: 'usuario', // usuario | catalogo
                selectedUser: null,
                asignacionesEnPosesion: [],
                asignacionesAll: [],
                historialAsignaciones: [],
                recentAssignments: [],
                userTotalEnPosesion: 0,
                loadingAsignaciones: false,
                expandedEpiId: null,
                editFechaModalOpen: false,
                editFechaAsignacion: null,
                editFechaTipo: 'entrega', // entrega | devolucion
                editFechaAnterior: '',
                editFechaNueva: '',
                editFechaSaving: false,

                epis: [],
                loadingEpis: false,
                catalogoCount: 0,
                showInactiveEpis: false,

                saving: false,

                recentModalOpen: false,
                recentModalUser: null,
                recentModalItems: [],
                recentModalLoading: false,
                recentModalPage: 1,
                recentModalLastPage: 1,
                recentModalTotal: 0,
                recentModalPer: 10,

                comprasOpen: false,
                comprasLoading: false,
                compras: [],
                comprasFilterDate: '',
                comprasFilterEpiId: null,
                comprasFilterEpiQuery: '',
                comprasFilterEpiSuggestionsOpen: false,
                comprasFilterEpiSuggestions: [],

                compraModalOpen: false,
                compraMode: 'create', // create | edit
                compraId: null,
                compraSaving: false,
                compraEstadoComprada: false,
                compraTicketFile: null,
                compraTicketUrl: null,
                compraItems: [],
                compraEpiQuery: '',
                compraEpiSuggestionsOpen: false,
                compraEpiSuggestions: [],

                agendaEpiQuery: '',
                agendaEpiDebounceId: null,
                agendaEmpresaId: '',
                agendaCategoriaId: '',

                userEpiFilterQuery: '',

                stats: {
                    usuariosConEpis: 0,
                },

                assignForm: {
                    epi_id: '',
                    cantidad: 1,
                    notas: ''
                },
                epiAssignQuery: '',
                epiAssignSuggestionsOpen: false,
                epiAssignSuggestions: [],
                epiCreate: {
                    nombre: '',
                    codigo: '',
                    categoria: '',
                    descripcion: '',
                    activo: true
                },
                epiCreateFile: null,

                get modalTitle() {
                    if (this.modalTab === 'catalogo') return 'Catálogo';
                    return this.selectedUser ? this.selectedUser.nombre_completo : 'Usuario';
                },

                get selectedEpiLabel() {
                    const e = this.epis.find(x => x.id === this.assignForm.epi_id);
                    if (!e) return '';
                    const code = e.codigo ? ` (${e.codigo})` : '';
                    const cat = e.categoria ? ` · ${e.categoria}` : '';
                    return `${e.nombre}${code}${cat}`;
                },

                get recentModalSummary() {
                    if (!this.recentModalUser) return '';
                    const total = this.recentModalTotal || 0;
                    if (total === 0) return 'Sin movimientos.';
                    const page = this.recentModalPage || 1;
                    const per = this.recentModalPer || 10;
                    const start = (page - 1) * per + 1;
                    const end = Math.min(start + (this.recentModalItems?.length || 0) - 1, total);
                    return `Mostrando ${start}-${end} de ${total} · Página ${page}/${this.recentModalLastPage}`;
                },

                get catalogEpis() {
                    const list = (this.epis || []).slice();
                    const showInactive = !!this.showInactiveEpis;
                    const filtered = showInactive ? list : list.filter(e => e.activo);
                    filtered.sort((a, b) => {
                        const aInact = a.activo ? 0 : 1;
                        const bInact = b.activo ? 0 : 1;
                        if (aInact !== bInact) return bInact - aInact; // inactivos primero
                        return (a.nombre || '').localeCompare(b.nombre || '');
                    });
                    return filtered;
                },

                get comprasFilterEpiLabel() {
                    const e = this.epis.find(x => x.id === this.comprasFilterEpiId);
                    if (!e) return '';
                    const code = e.codigo ? ` (${e.codigo})` : '';
                    const cat = e.categoria ? ` · ${e.categoria}` : '';
                    return `${e.nombre}${code}${cat}`;
                },

                async init() {
                    await this.refreshUsers();
                    await this.refreshEpis();

                    document.addEventListener('click', (ev) => {
                        const el = ev.target;
                        if (!el) return;

                        if (this.suggestionsOpen) {
                            const userBox = el.closest('[data-user-suggest]');
                            if (!userBox) this.closeSuggestions();
                        }

                        if (this.epiAssignSuggestionsOpen) {
                            const epiBox = el.closest('[data-epi-suggest]');
                            if (!epiBox) this.closeEpiAssignSuggestions();
                        }

                        if (this.compraEpiSuggestionsOpen) {
                            const compraBox = el.closest('[data-compra-epi-suggest]');
                            if (!compraBox) this.closeCompraEpiSuggestions();
                        }

                        if (this.comprasFilterEpiSuggestionsOpen) {
                            const compraFilterBox = el.closest('[data-compras-filter-epi-suggest]');
                            if (!compraFilterBox) this.closeComprasFilterEpiSuggestions();
                        }
                    }, {
                        capture: true
                    });
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.content || '';
                },

                async api(url, options = {}) {
                    const headers = options.headers ? {
                        ...options.headers
                    } : {};
                    if (!headers['X-CSRF-TOKEN']) headers['X-CSRF-TOKEN'] = this.csrf();
                    if (!headers['Accept']) headers['Accept'] = 'application/json';
                    return fetch(url, {
                        credentials: 'same-origin',
                        ...options,
                        headers
                    });
                },

                async handleImportFile(event) {
                    const file = event?.target?.files?.[0];
                    if (!file) return;
                    this.importUploading = true;
                    try {
                        const fd = new FormData();
                        fd.append('file', file);
                        const res = await this.api(@js(route('epis.import')), {
                            method: 'POST',
                            body: fd
                        });
                        const data = await res.json();
                        if (!res.ok || data.ok === false) {
                            alert(data.message || 'No se pudo importar el Excel.');
                            return;
                        }
                        alert(data.message || 'ImportaciÃ³n completada.');
                        await this.refreshUsers();
                        if (this.selectedUser && data.user && data.user.id === this.selectedUser.id) {
                            await this.refreshAsignaciones();
                        }
                    } finally {
                        this.importUploading = false;
                        if (event?.target) event.target.value = '';
                    }
                },

                normalize(str) {
                    return (str || '')
                        .toString()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/\s+/g, ' ')
                        .trim();
                },

                digits(str) {
                    return (str || '').toString().replace(/\D+/g, '');
                },

                buildHaystack(u) {
                    const fullName = `${u.name || ''} ${u.primer_apellido || ''} ${u.segundo_apellido || ''}`.replace(
                        /\s+/g, ' ').trim();
                    u._full_name = fullName;
                    const parts = [
                        fullName,
                        u.email || '',
                        u.dni || '',
                        u.movil_personal || '',
                        u.empresa?.nombre || '',
                        u.categoria?.nombre || '',
                        this.digits(u.movil_personal || ''),
                    ].join(' | ');
                    u._hay = this.normalize(parts);
                    u._hay_digits = this.digits(parts);
                },

                buildEpiHaystack(e) {
                    const parts = [
                        e.nombre || '',
                        e.codigo || '',
                        e.categoria || '',
                        e.descripcion || '',
                    ].join(' | ');
                    e._hay = this.normalize(parts);
                },

                matches(u, q) {
                    const query = this.normalize(q);
                    if (!query) return true;
                    const tokens = query.split(' ').filter(Boolean);
                    const digitsQuery = this.digits(q);

                    const allTokensMatch = tokens.every(t => u._hay.includes(t));
                    if (allTokensMatch) return true;

                    if (digitsQuery.length >= 2) {
                        return u._hay_digits.includes(digitsQuery);
                    }

                    return false;
                },

                epiMatches(e, q) {
                    const query = this.normalize(q);
                    if (!query) return true;
                    if (!e?._hay) this.buildEpiHaystack(e);
                    const tokens = query.split(' ').filter(Boolean);
                    return tokens.every(t => (e._hay || '').includes(t));
                },

                onAgendaEpiQueryChange() {
                    if (this.agendaEpiDebounceId) clearTimeout(this.agendaEpiDebounceId);
                    this.agendaEpiDebounceId = setTimeout(() => {
                        this.refreshUsers();
                    }, 300);
                },

                onAgendaFiltersChange() {
                    this.refreshUsers();
                },

                resetAgendaFilters() {
                    if (this.agendaEpiDebounceId) {
                        clearTimeout(this.agendaEpiDebounceId);
                        this.agendaEpiDebounceId = null;
                    }
                    this.agendaEpiQuery = '';
                    this.agendaEmpresaId = '';
                    this.agendaCategoriaId = '';
                    this.refreshUsers();
                },

                async refreshUsers() {
                    this.loadingUsers = true;
                    try {
                        const baseUrl = @js(route('epis.api.users'));
                        const url = new URL(baseUrl, window.location.origin);
                        const epi = (this.agendaEpiQuery || '').trim();
                        if (epi) url.searchParams.set('epi', epi);
                        const empresa = (this.agendaEmpresaId || '').toString().trim();
                        if (empresa) url.searchParams.set('empresa_id', empresa);
                        const categoria = (this.agendaCategoriaId || '').toString().trim();
                        if (categoria) url.searchParams.set('categoria_id', categoria);

                        const res = await this.api(url.toString());
                        const data = await res.json();
                        const users = (data.users || []);
                        users.forEach(u => this.buildHaystack(u));
                        this.allUsers = users;
                        this.agendaUsers = users.filter(u => u.tiene_epis && (!epi || !!u.epi_match));

                        this.stats.usuariosConEpis = (data.stats?.usuarios_con_epis) ?? this.agendaUsers.length;
                    } finally {
                        this.loadingUsers = false;
                        this.updateSuggestions();
                    }
                },

                async refreshEpis() {
                    this.loadingEpis = true;
                    try {
                        const res = await this.api(@js(route('epis.api.epis')));
                        const data = await res.json();
                        this.epis = (data.epis || []);
                        this.epis.forEach(e => this.buildEpiHaystack(e));
                        this.catalogoCount = this.epis.length;
                    } finally {
                        this.loadingEpis = false;
                    }
                },

                openSuggestions() {
                    this.suggestionsOpen = true;
                    this.updateSuggestions();
                },

                closeSuggestions() {
                    this.suggestionsOpen = false;
                },

                onQueryChange() {
                    this.openSuggestions();
                    this.updateSuggestions();
                },

                updateSuggestions() {
                    const q = this.query;
                    const filtered = this.allUsers.filter(u => this.matches(u, q));
                    this.suggestions = filtered.slice(0, 10);
                },

                async selectUser(u) {
                    this.query = u._full_name || u.nombre_completo || '';
                    this.closeSuggestions();
                    await this.openUser(u);
                },

                async openUser(u) {
                    this.selectedUser = u;
                    this.modalOpen = true;
                    this.modalTab = 'usuario';
                    this.assignForm = {
                        epi_id: '',
                        cantidad: 1,
                        notas: ''
                    };
                    this.epiAssignQuery = '';
                    this.epiAssignSuggestionsOpen = false;
                    this.expandedEpiId = null;
                    this.closeRecentModal();
                    await this.refreshAsignaciones();
                },

                async openCatalog() {
                    this.modalOpen = true;
                    this.modalTab = 'catalogo';
                    this.showInactiveEpis = false;
                    await this.refreshEpis();
                },

                async switchModalTab(tab) {
                    this.modalTab = tab;
                    if (tab === 'catalogo') await this.refreshEpis();
                    if (tab === 'usuario' && this.selectedUser) await this.refreshAsignaciones();
                },

                closeModal() {
                    this.modalOpen = false;
                },

                async refreshAsignaciones() {
                    if (!this.selectedUser) return;
                    this.loadingAsignaciones = true;
                    try {
                        const url = @js(url('/epis/api/users/__ID__/asignaciones')).replace('__ID__', this.selectedUser.id);
                        const res = await this.api(url);
                        const data = await res.json();
                        this.asignacionesEnPosesion = data.en_posesion || [];
                        this.asignacionesAll = data.asignaciones || [];
                        this.historialAsignaciones = data.historial || [];
                        this.recentAssignments = data.recent || [];
                        this.userTotalEnPosesion = data.total_en_posesion || 0;
                    } finally {
                        this.loadingAsignaciones = false;
                    }
                },

                formatDate(iso) {
                    if (!iso) return '—';
                    const d = new Date(iso);
                    if (Number.isNaN(d.getTime())) return '—';
                    return d.toLocaleDateString('es-ES');
                },

                formatDateTime(iso) {
                    if (!iso) return '—';
                    const d = new Date(iso);
                    if (Number.isNaN(d.getTime())) return '—';
                    return d.toLocaleString('es-ES');
                },

                isoToDateValue(iso) {
                    if (!iso) return '';
                    const d = new Date(iso);
                    if (Number.isNaN(d.getTime())) return '';
                    return d.toISOString().slice(0, 10);
                },

                openFechaModal(a, tipo = 'entrega') {
                    this.editFechaTipo = tipo;
                    this.editFechaAsignacion = a;
                    const iso = tipo === 'entrega' ? (a.entregado_en || a.fecha_asignacion) : a.devuelto_en;
                    this.editFechaAnterior = this.formatDate(iso);
                    this.editFechaNueva = this.isoToDateValue(iso);
                    this.editFechaModalOpen = true;
                },

                closeFechaModal() {
                    this.editFechaModalOpen = false;
                    this.editFechaAsignacion = null;
                    this.editFechaNueva = '';
                    this.editFechaAnterior = '';
                    this.editFechaTipo = 'entrega';
                },

                async saveFechaAsignacion() {
                    if (!this.selectedUser || !this.editFechaAsignacion) return;
                    this.editFechaSaving = true;
                    try {
                        const url = @js(url('/epis/usuarios/__UID__/asignaciones/__AID__/fechas'))
                            .replace('__UID__', this.selectedUser.id)
                            .replace('__AID__', this.editFechaAsignacion.id);
                        const payload = {};
                        if (this.editFechaTipo === 'entrega') {
                            payload.fecha_entrega = this.editFechaNueva || null;
                        } else {
                            payload.fecha_devolucion = this.editFechaNueva || null;
                        }

                        const res = await this.api(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload),
                        });
                        if (!res.ok) throw new Error('No se pudo actualizar la fecha.');
                        await this.refreshAsignaciones();
                        await this.refreshUsers();
                        this.closeFechaModal();
                    } finally {
                        this.editFechaSaving = false;
                    }
                },

                formatMoney(value) {
                    const n = Number(value || 0);
                    return n.toLocaleString('es-ES', {
                        style: 'currency',
                        currency: 'EUR'
                    });
                },

                toggleEpiGroup(epiId) {
                    const id = parseInt(epiId, 10);
                    if (!Number.isFinite(id)) return;
                    const isOpening = this.expandedEpiId !== id;
                    this.expandedEpiId = isOpening ? id : null;
                },

                openRecentModal() {
                    if (!this.selectedUser) return;
                    this.recentModalUser = this.selectedUser;
                    this.recentModalOpen = true;
                    this.loadRecentModalPage(1);
                },

                closeRecentModal() {
                    this.recentModalOpen = false;
                    this.recentModalUser = null;
                    this.recentModalItems = [];
                    this.recentModalLoading = false;
                    this.recentModalPage = 1;
                    this.recentModalLastPage = 1;
                    this.recentModalTotal = 0;
                },

                async openRecentModalForUser(u) {
                    if (!u?.id) return;
                    this.recentModalUser = u;
                    this.recentModalItems = [];
                    this.recentModalOpen = true;
                    await this.loadRecentModalPage(1);
                },

                async loadRecentModalPage(page) {
                    if (!this.recentModalUser?.id) return;
                    const target = Math.max(1, Number(page) || 1);
                    this.recentModalLoading = true;
                    try {
                        const url = @js(url('/epis/api/users/__ID__/movimientos'))
                            .replace('__ID__', this.recentModalUser.id) +
                            `?page=${target}&per=${this.recentModalPer}`;

                        const res = await this.api(url);
                        const data = await res.json();
                        this.recentModalItems = data.items || [];
                        this.recentModalPage = data.meta?.current_page || target;
                        this.recentModalLastPage = data.meta?.last_page || 1;
                        this.recentModalTotal = data.meta?.total || 0;
                    } finally {
                        this.recentModalLoading = false;
                    }
                },

                async openCompras() {
                    this.comprasOpen = true;
                    await this.refreshCompras();
                },

                closeCompras() {
                    this.comprasOpen = false;
                },

                async refreshCompras() {
                    this.comprasLoading = true;
                    try {
                        const url = new URL(@js(route('epis.api.compras')), window.location.origin);
                        if (this.comprasFilterDate) url.searchParams.set('date', this.comprasFilterDate);
                        if (this.comprasFilterEpiId) url.searchParams.set('epi_id', this.comprasFilterEpiId);

                        const res = await this.api(url.toString());
                        const data = await res.json();
                        this.compras = data.compras || [];
                    } finally {
                        this.comprasLoading = false;
                    }
                },

                openComprasFilterEpiSuggestions() {
                    this.comprasFilterEpiSuggestionsOpen = true;
                    this.updateComprasFilterEpiSuggestions();
                },

                closeComprasFilterEpiSuggestions() {
                    this.comprasFilterEpiSuggestionsOpen = false;
                },

                onComprasFilterEpiQueryChange() {
                    this.openComprasFilterEpiSuggestions();
                    this.updateComprasFilterEpiSuggestions();
                },

                updateComprasFilterEpiSuggestions() {
                    const q = this.comprasFilterEpiQuery;
                    const filtered = this.epis
                        .filter(e => this.epiMatches(e, q))
                        .slice(0, 10);
                    this.comprasFilterEpiSuggestions = filtered;
                },

                async selectComprasFilterEpi(e) {
                    this.comprasFilterEpiId = e.id;
                    this.comprasFilterEpiQuery = e.codigo ? `${e.nombre} (${e.codigo})` : e.nombre;
                    this.closeComprasFilterEpiSuggestions();
                    await this.refreshCompras();
                },

                async clearComprasFilterEpi() {
                    this.comprasFilterEpiId = null;
                    this.comprasFilterEpiQuery = '';
                    this.closeComprasFilterEpiSuggestions();
                    await this.refreshCompras();
                },

                openCompraCreate() {
                    this.compraMode = 'create';
                    this.compraId = null;
                    this.compraItems = [];
                    this.compraEstadoComprada = false;
                    this.compraTicketFile = null;
                    this.compraTicketUrl = null;
                    this.compraEpiQuery = '';
                    this.compraEpiSuggestionsOpen = false;
                    this.compraEpiSuggestions = [];
                    this.compraModalOpen = true;
                },

                async openCompraEdit(id) {
                    this.compraMode = 'edit';
                    this.compraId = id;
                    this.compraItems = [];
                    this.compraEstadoComprada = false;
                    this.compraTicketFile = null;
                    this.compraTicketUrl = null;
                    this.compraEpiQuery = '';
                    this.compraEpiSuggestionsOpen = false;
                    this.compraEpiSuggestions = [];
                    this.compraModalOpen = true;

                    const res = await this.api(@js(url('/epis/api/compras/__ID__')).replace('__ID__', id));
                    const data = await res.json();
                    const compra = data.compra;
                    this.compraEstadoComprada = compra?.estado === 'comprada';
                    this.compraTicketUrl = compra?.ticket_url || null;
                    this.compraItems = (compra?.items || []).map(it => ({
                        epi_id: it.epi_id,
                        cantidad: it.cantidad || 1,
                        precio_unitario: it.precio_unitario,
                        epi: it.epi,
                    }));
                },

                closeCompraModal() {
                    this.compraModalOpen = false;
                    this.compraTicketFile = null;
                    this.compraEpiSuggestionsOpen = false;
                },

                async toggleCompraEstado(c) {
                    const next = c.estado === 'comprada' ? 'pendiente' : 'comprada';
                    const url = @js(url('/epis/api/compras/__ID__')).replace('__ID__', c.id);
                    await this.api(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            estado: next
                        }),
                    });
                    await this.refreshCompras();
                },

                get compraTotalProductos() {
                    return this.compraItems.length;
                },

                get compraTotalUnidades() {
                    return this.compraItems.reduce((acc, it) => acc + (Number(it.cantidad) || 0), 0);
                },

                get compraTotalPrecio() {
                    return this.compraItems.reduce((acc, it) => {
                        const qty = Number(it.cantidad) || 0;
                        const price = Number(it.precio_unitario) || 0;
                        return acc + qty * price;
                    }, 0);
                },

                openCompraEpiSuggestions() {
                    this.compraEpiSuggestionsOpen = true;
                    this.updateCompraEpiSuggestions();
                },

                closeCompraEpiSuggestions() {
                    this.compraEpiSuggestionsOpen = false;
                },

                onCompraEpiQueryChange() {
                    this.openCompraEpiSuggestions();
                    this.updateCompraEpiSuggestions();
                },

                updateCompraEpiSuggestions() {
                    const q = this.compraEpiQuery;
                    const filtered = this.epis
                        .filter(e => e.activo)
                        .filter(e => this.epiMatches(e, q))
                        .slice(0, 10);
                    this.compraEpiSuggestions = filtered;
                },

                get userEpiGroupsFiltered() {
                    const q = (this.userEpiFilterQuery || '').trim();
                    if (!q) return this.userEpiGroups;
                    const query = this.normalize(q);
                    const tokens = query.split(' ').filter(Boolean);
                    return this.userEpiGroups.filter(g => {
                        const name = this.normalize(g?.epi?.nombre || '');
                        return tokens.every(t => name.includes(t));
                    });
                },

                addCompraItem(e) {
                    const existing = this.compraItems.find(it => it.epi_id === e.id);
                    if (existing) {
                        existing.cantidad = (Number(existing.cantidad) || 0) + 1;
                    } else {
                        this.compraItems.push({
                            epi_id: e.id,
                            cantidad: 1,
                            precio_unitario: null,
                            epi: e,
                        });
                    }
                    this.compraEpiQuery = '';
                    this.closeCompraEpiSuggestions();
                },

                removeCompraItem(idx) {
                    this.compraItems.splice(idx, 1);
                },

                async saveCompra() {
                    if (this.compraItems.length === 0) return;
                    this.compraSaving = true;
                    try {
                        const fd = new FormData();
                        fd.append('estado', this.compraEstadoComprada ? 'comprada' : 'pendiente');
                        fd.append('items', JSON.stringify(this.compraItems.map(it => ({
                            epi_id: it.epi_id,
                            cantidad: Number(it.cantidad) || 1,
                            precio_unitario: (it.precio_unitario === null || it.precio_unitario ===
                                    '' || Number.isNaN(Number(it.precio_unitario))) ?
                                null : Number(it.precio_unitario),
                        }))));
                        if (this.compraTicketFile) fd.append('ticket', this.compraTicketFile);

                        if (this.compraMode === 'create') {
                            const res = await this.api(@js(route('epis.api.compras.store')), {
                                method: 'POST',
                                body: fd
                            });
                            if (!res.ok) throw new Error('No se pudo crear la compra.');
                        } else {
                            const url = @js(url('/epis/api/compras/__ID__')).replace('__ID__', this.compraId);
                            const res = await this.api(url, {
                                method: 'POST',
                                body: fd
                            });
                            if (!res.ok) throw new Error('No se pudo actualizar la compra.');
                        }

                        await this.refreshCompras();
                        this.closeCompraModal();
                        this.comprasOpen = true;
                    } finally {
                        this.compraSaving = false;
                    }
                },

                get userEpiGroups() {
                    if (!this.asignacionesAll || this.asignacionesAll.length === 0) return [];
                    const map = new Map();
                    for (const a of this.asignacionesAll) {
                        if (!a.epi) continue;
                        const key = a.epi.id;
                        if (!map.has(key)) {
                            map.set(key, {
                                epi: a.epi,
                                asignaciones: [],
                                total_en_posesion: 0,
                                total_devueltos: 0
                            });
                        }
                        const g = map.get(key);
                        g.asignaciones.push(a);
                        if (a.devuelto_en) g.total_devueltos += a.cantidad || 0;
                        else g.total_en_posesion += a.cantidad || 0;
                    }
                    const groups = Array.from(map.values())
                        .filter(g => g.total_en_posesion > 0)
                        .map(g => {
                            g.asignaciones.sort((x, y) => {
                                const xPosesion = x.devuelto_en ? 1 : 0;
                                const yPosesion = y.devuelto_en ? 1 : 0;
                                if (xPosesion !== yPosesion) return xPosesion -
                                    yPosesion; // en posesión primero
                                return (y.fecha_asignacion || '').localeCompare(x.fecha_asignacion || '');
                            });
                            return g;
                        });
                    groups.sort((a, b) => (b.total_en_posesion - a.total_en_posesion) || (a.epi.nombre || '')
                        .localeCompare(b.epi.nombre || ''));
                    return groups;
                },

                openEpiAssignSuggestions() {
                    this.epiAssignSuggestionsOpen = true;
                    this.updateEpiAssignSuggestions();
                },

                closeEpiAssignSuggestions() {
                    this.epiAssignSuggestionsOpen = false;
                },

                onEpiAssignQueryChange() {
                    this.openEpiAssignSuggestions();
                    this.updateEpiAssignSuggestions();
                },

                updateEpiAssignSuggestions() {
                    const q = this.epiAssignQuery;
                    const filtered = this.epis
                        .filter(e => e.activo)
                        .filter(e => this.epiMatches(e, q))
                        .slice(0, 10);
                    this.epiAssignSuggestions = filtered;
                },

                selectEpiToAssign(e) {
                    this.assignForm.epi_id = e.id;
                    this.epiAssignQuery = e.codigo ? `${e.nombre} (${e.codigo})` : e.nombre;
                    this.closeEpiAssignSuggestions();
                },

                async assignEpi() {
                    if (!this.selectedUser) return;
                    this.saving = true;
                    try {
                        const selected = this.epis.find(e => e.id === this.assignForm.epi_id);
                        if (!selected || !selected.activo) {
                            alert('No puedes asignar un EPI inactivo.');
                            return;
                        }

                        const url = @js(url('/epis/usuarios/__ID__/asignaciones')).replace('__ID__', this.selectedUser.id);
                        const res = await this.api(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                epi_id: this.assignForm.epi_id,
                                cantidad: this.assignForm.cantidad,
                                notas: this.assignForm.notas || null,
                            }),
                        });
                        if (!res.ok) throw new Error('No se pudo asignar.');
                        this.assignForm = {
                            epi_id: '',
                            cantidad: 1,
                            notas: ''
                        };
                        this.epiAssignQuery = '';
                        this.closeEpiAssignSuggestions();
                        await this.refreshAsignaciones();
                        await this.refreshUsers();
                    } finally {
                        this.saving = false;
                    }
                },

                async markReturned(a) {
                    if (!this.selectedUser) return;
                    this.saving = true;
                    try {
                        let cantidad = 1;
                        if ((a.cantidad || 0) > 1) {
                            const raw = prompt(`¿Cuántos quieres devolver? (1-${a.cantidad})`, '1');
                            if (raw === null) return;
                            const n = parseInt(raw, 10);
                            if (!Number.isFinite(n) || n < 1 || n > a.cantidad) {
                                alert('Cantidad inválida.');
                                return;
                            }
                            cantidad = n;
                        }

                        const url = @js(url('/epis/usuarios/__UID__/asignaciones/__AID__/devolver'))
                            .replace('__UID__', this.selectedUser.id)
                            .replace('__AID__', a.id);
                        const res = await this.api(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                cantidad
                            }),
                        });
                        if (!res.ok) throw new Error('No se pudo devolver.');
                        await this.refreshAsignaciones();
                        await this.refreshUsers();
                    } finally {
                        this.saving = false;
                    }
                },

                async createEpi() {
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        fd.append('nombre', this.epiCreate.nombre);
                        if (this.epiCreate.codigo) fd.append('codigo', this.epiCreate.codigo);
                        if (this.epiCreate.categoria) fd.append('categoria', this.epiCreate.categoria);
                        if (this.epiCreate.descripcion) fd.append('descripcion', this.epiCreate.descripcion);
                        fd.append('activo', this.epiCreate.activo ? '1' : '0');
                        if (this.epiCreateFile) fd.append('imagen', this.epiCreateFile);

                        const res = await this.api(@js(route('epis.catalogo.store')), {
                            method: 'POST',
                            body: fd
                        });
                        if (!res.ok) throw new Error('No se pudo crear.');
                        this.epiCreate = {
                            nombre: '',
                            codigo: '',
                            categoria: '',
                            descripcion: '',
                            activo: true
                        };
                        this.epiCreateFile = null;
                        await this.refreshEpis();
                    } finally {
                        this.saving = false;
                    }
                },

                async updateEpi(id, form) {
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        fd.append('_method', 'PUT');
                        fd.append('nombre', form.nombre || '');
                        fd.append('codigo', form.codigo || '');
                        fd.append('categoria', form.categoria || '');
                        fd.append('descripcion', form.descripcion || '');
                        fd.append('activo', form.activo ? '1' : '0');
                        if (form._file) fd.append('imagen', form._file);

                        const url = @js(url('/epis/catalogo/__ID__')).replace('__ID__', id);
                        const res = await this.api(url, {
                            method: 'POST',
                            body: fd
                        });
                        if (!res.ok) throw new Error('No se pudo actualizar.');
                        await this.refreshEpis();
                    } finally {
                        this.saving = false;
                    }
                },

                async deleteEpi(e) {
                    const ok = confirm('¿Seguro que quieres eliminar este EPI? Si tiene asignaciones, se desactivará.');
                    if (!ok) return;
                    this.saving = true;
                    try {
                        const url = @js(url('/epis/catalogo/__ID__')).replace('__ID__', e.id);
                        const fd = new FormData();
                        fd.append('_method', 'DELETE');
                        const res = await this.api(url, {
                            method: 'POST',
                            body: fd
                        });
                        if (!res.ok) throw new Error('No se pudo eliminar.');
                        await this.refreshEpis();
                    } finally {
                        this.saving = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>
