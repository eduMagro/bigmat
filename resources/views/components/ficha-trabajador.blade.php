@props(['user', 'resumen', 'solicitudesVacaciones' => collect()])

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<div x-data="{
    mostrarDetalles: false,
    seccionContacto: false,
    seccionLaboral: false,
    seccionDepartamentos: false,
    seccionNomina: false,
    seccionContrato: false,
    seccionJustificante: false
}"
@justificante-guardado-success.window="
    seccionJustificante = false;
    Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: $event.detail[0].mensaje + ' para el ' + $event.detail[0].fecha,
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
">
    <div class="max-w-7xl mx-auto">

        {{-- Header con banner degradado --}}
        <div class="bg-gray-900 dark:bg-gray-950 rounded sm:rounded-3xl shadow-2xl mb-8 overflow-visible relative">
            <div
                class="relative bg-gradient-to-br from-gray-800/50 to-gray-900/50 backdrop-blur-sm rounded-2xl sm:rounded-3xl">
                <div class="absolute inset-0 bg-black/10 rounded-2xl sm:rounded-3xl"></div>
                <div class="relative p-4 sm:p-6">
                    <div
                        class="flex flex-col items-center text-center gap-4 sm:flex-row sm:items-center sm:text-left sm:gap-6">
                        {{-- Avatar --}}
                        <div class="relative z-10 flex-shrink-0">
                            @if ($user->ruta_imagen)
                                <div
                                    class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl ring-4 ring-gray-700 shadow-2xl overflow-hidden bg-white">
                                    <img src="{{ $user->ruta_imagen }}" alt="Foto de perfil"
                                        class="w-full h-full object-cover">
                                </div>
                            @else
                                <div
                                    class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-gray-700 to-gray-800 rounded-2xl flex items-center justify-center text-3xl sm:text-4xl font-bold text-white shadow-2xl ring-4 ring-gray-700">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif

                            {{-- Botón cambiar foto --}}
                            <form method="POST" action="{{ route('usuarios.editarSubirImagen') }}"
                                enctype="multipart/form-data" class="absolute -bottom-1 -right-1 z-20">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <label
                                    class="flex items-center justify-center bg-white rounded-full p-1.5 shadow-lg cursor-pointer hover:bg-gray-100 transition-all hover:scale-110 border-2 border-gray-700 active:scale-95">
                                    <svg class="w-3.5 h-3.5 text-gray-900" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <input type="file" name="imagen" accept="image/*" class="hidden"
                                        onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>

                        {{-- Nombre y categoría --}}
                        <div class="flex-1 w-full sm:w-auto">
                            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-white drop-shadow-lg break-words">
                                {{ $user->nombre_completo }}</h1>
                            <p class="text-xs sm:text-sm text-gray-300 mt-1">{{ $user->categoria->nombre ?? 'N/A' }}</p>
                        </div>

                        {{-- Estadísticas en el header --}}
                        <div class="flex gap-3 sm:gap-4 max-sm:w-full">
                            <div class="text-center px-3 py-1.5 bg-white/10 rounded-lg max-sm:w-full sm:min-w-[6rem]">
                                <p class="text-lg sm:text-xl font-bold text-green-400">
                                    {{ $resumen['diasVacaciones'] }}</p>
                                <p class="text-[10px] text-gray-300">Vacaciones</p>
                            </div>
                            <div class="text-center px-3 py-1.5 bg-white/10 rounded-lg max-sm:w-full sm:min-w-[6rem]">
                                <p class="text-lg sm:text-xl font-bold text-red-400">
                                    {{ $resumen['faltasInjustificadas'] }}</p>
                                <p class="text-[10px] text-gray-300">Injustif.</p>
                            </div>
                            <div class="text-center px-3 py-1.5 bg-white/10 rounded-lg max-sm:w-full sm:min-w-[6rem]">
                                <p class="text-lg sm:text-xl font-bold text-yellow-400">
                                    {{ $resumen['faltasJustificadas'] }}</p>
                                <p class="text-[10px] text-gray-300">Justif.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Botón toggle para mostrar/ocultar detalles --}}
            <div class="relative">
                <button @click="mostrarDetalles = !mostrarDetalles"
                    class="absolute left-1/2 -translate-x-1/2 -bottom-3 z-10 bg-white hover:bg-gray-50 rounded-full shadow-md hover:shadow-lg transition-all duration-300 px-3 py-1 flex items-center gap-1 border border-gray-200">
                    <span class="text-[10px] font-medium text-gray-600"
                        x-text="mostrarDetalles ? 'Ocultar' : 'Ver más'"></span>
                    <svg class="w-2.5 h-2.5 text-gray-500 transition-transform duration-300"
                        :class="{ 'rotate-180': mostrarDetalles }" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Contenido desplegable --}}
        <div x-cloak x-show="mostrarDetalles" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4" class="space-y-2 mt-6">

            {{-- Información de contacto --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <button @click="seccionContacto = !seccionContacto"
                    class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-2">
                        <div class="bg-blue-100 rounded-lg p-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Información de contacto</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': seccionContacto }" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-cloak x-show="seccionContacto" x-collapse>
                    <div class="px-3 pb-3 space-y-2">
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-[10px] text-gray-500">Email</p>
                                <p class="text-xs text-gray-900">{{ $user->email }}</p>
                            </div>
                        </div>
                        @if ($user->movil_empresa)
                            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <div>
                                    <p class="text-[10px] text-gray-500">Teléfono empresa</p>
                                    <p class="text-xs text-gray-900">{{ $user->movil_empresa }}</p>
                                </div>
                            </div>
                        @endif
                        @if ($user->movil_personal)
                            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-lg">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <p class="text-[10px] text-gray-500">Teléfono personal</p>
                                    <p class="text-xs text-gray-900">{{ $user->movil_personal }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Información laboral --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <button @click="seccionLaboral = !seccionLaboral"
                    class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-2">
                        <div class="bg-purple-100 rounded-lg p-1.5">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Información laboral</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': seccionLaboral }" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                        </path>
                    </svg>
                </button>
                <div x-cloak x-show="seccionLaboral" x-collapse>
                    <div class="px-3 pb-3 space-y-2">
                        <div
                            class="p-2 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border-l-3 border-blue-500">
                            <p class="text-[10px] text-gray-500">Empresa</p>
                            <p class="text-xs font-semibold text-gray-900">{{ $user->empresa->nombre ?? 'N/A' }}</p>
                        </div>
                        <div
                            class="p-2 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border-l-3 border-purple-500">
                            <p class="text-[10px] text-gray-500">Categoría</p>
                            <p class="text-xs font-semibold text-gray-900">{{ $user->categoria->nombre ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Departamentos --}}
            @if ($user->rol == 'oficina' && $user->departamentos->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <button @click="seccionDepartamentos = !seccionDepartamentos"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-indigo-100 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">Departamentos</span>
                            <span
                                class="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full">{{ $user->departamentos->count() }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionDepartamentos }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionDepartamentos" x-collapse>
                        <div class="px-3 pb-3 flex flex-wrap gap-1.5">
                            @foreach ($user->departamentos as $dep)
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-gradient-to-r from-blue-500 to-indigo-500 text-white">
                                    {{ $dep->nombre }}
                                    @if ($dep->pivot && $dep->pivot->rol_departamental)
                                        <span class="ml-1 opacity-75">({{ $dep->pivot->rol_departamental }})</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Contratos y Documentos --}}
            @if (auth()->check() && (auth()->user()->rol === 'oficina' || auth()->id() === $user->id))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <button @click="$dispatch('open-docs-modal')"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-blue-100 rounded-lg p-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">Contratos y Documentos</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            @endif

            {{-- Solicitar nómina --}}
            @if (auth()->check() && auth()->id() === $user->id)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <button @click="seccionNomina = !seccionNomina"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-green-100 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">Solicitar Nómina</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionNomina }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionNomina" x-collapse>
                        <div class="px-3 pb-3">
                            <p class="text-xs text-gray-600 mb-3">
                                Selecciona el mes y recibirás tu nómina en: <span
                                    class="font-semibold text-blue-700">{{ $user->email }}</span>
                            </p>

                            @if ($errors->has('mes_anio'))
                                <div class="mb-3 bg-red-50 border-l-4 border-red-500 p-2 rounded-r-lg">
                                    <p class="text-xs text-red-800">{{ $errors->first('mes_anio') }}</p>
                                </div>
                            @endif

                            <form action="{{ route('nominas.crearDescargarMes') }}" method="POST"
                                x-data="{ cargando: false }" @submit="cargando = true" class="flex gap-2">
                                @csrf
                                <input type="month" name="mes_anio" required value="{{ old('mes_anio') }}"
                                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-300 text-xs py-2"
                                    :class="{ 'opacity-50': cargando }">
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors disabled:opacity-50"
                                    :disabled="cargando">
                                    <span x-show="!cargando">Enviar</span>
                                    <span x-show="cargando">...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Descargar Contrato --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <button @click="seccionContrato = !seccionContrato"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-indigo-100 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">Descargar Contrato</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionContrato }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionContrato" x-collapse>
                        <div class="px-3 pb-3">
                            <p class="text-xs text-gray-600 mb-3">
                                Descarga una copia de tu contrato de trabajo.
                            </p>
                            <a href="{{ route('incorporaciones.descargarMiContrato') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descargar PDF
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Solicitudes de Vacaciones Pendientes --}}
            @if ($solicitudesVacaciones->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                    x-data="{ seccionSolicitudes: false }">
                    <button @click="seccionSolicitudes = !seccionSolicitudes"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-amber-100 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">Solicitudes de Vacaciones</span>
                            <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full">{{ $solicitudesVacaciones->count() }} pendientes</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionSolicitudes }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionSolicitudes" x-collapse>
                        <div class="px-3 pb-3 space-y-2">
                            <p class="text-xs text-gray-500 mb-2">Haz clic en una solicitud para eliminarla</p>
                            @foreach ($solicitudesVacaciones as $solicitud)
                                <div class="flex items-center justify-between p-2 bg-amber-50 rounded-lg border border-amber-200 hover:bg-amber-100 cursor-pointer transition-colors group"
                                    onclick="eliminarSolicitudVacaciones({{ $solicitud->id }}, '{{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }}', '{{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}')">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <p class="text-xs font-medium text-gray-900">
                                                {{ \Carbon\Carbon::parse($solicitud->fecha_inicio)->format('d/m/Y') }} -
                                                {{ \Carbon\Carbon::parse($solicitud->fecha_fin)->format('d/m/Y') }}
                                            </p>
                                            <p class="text-[10px] text-gray-500">
                                                Solicitado el {{ $solicitud->created_at->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full">Pendiente</span>
                                        <svg class="w-4 h-4 text-red-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Justificantes - visible para operarios y oficina --}}
            @if (in_array($user->rol, ['operario', 'oficina']))
                @php
                    $esOficinaViendoOtro = Auth::user()->rol === 'oficina' && Auth::id() !== $user->id;
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <button @click="seccionJustificante = !seccionJustificante"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-orange-100 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ $esOficinaViendoOtro ? 'Justificantes' : 'Subir Justificante' }}
                            </span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionJustificante }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionJustificante" x-collapse>
                        <div class="p-3 pt-0">
                            @livewire('subir-justificante', ['userId' => $user->id])
                        </div>
                    </div>
                </div>
            @endif

            {{-- Privacidad y Consentimiento - solo visible para el propio usuario --}}
            @if (auth()->check() && auth()->id() === $user->id)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ seccionPrivacidad: false }">
                    <button @click="seccionPrivacidad = !seccionPrivacidad"
                        class="w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <div class="bg-gray-100 rounded-lg p-1.5">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">Privacidad y Consentimiento</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': seccionPrivacidad }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-cloak x-show="seccionPrivacidad" x-collapse>
                        <div class="px-3 pb-3 space-y-3">
                            {{-- Estado de aceptación --}}
                            @if ($user->fecha_aceptacion_politicas)
                                <div class="flex items-center gap-2 p-2 bg-green-50 rounded-lg border border-green-200">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <p class="text-xs text-green-800">
                                        Politicas aceptadas el <strong>{{ $user->fecha_aceptacion_politicas->format('d/m/Y') }}</strong>
                                    </p>
                                </div>
                            @endif

                            {{-- Enlaces a las políticas --}}
                            <div class="space-y-1">
                                <p class="text-[10px] text-gray-500 uppercase font-medium">Consultar politicas</p>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('politicas.privacidad') }}" target="_blank"
                                        class="text-xs text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Privacidad
                                    </a>
                                    <a href="{{ route('politicas.cookies') }}" target="_blank"
                                        class="text-xs text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Cookies
                                    </a>
                                    <a href="{{ route('politicas.terminos') }}" target="_blank"
                                        class="text-xs text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Terminos
                                    </a>
                                </div>
                            </div>

                            {{-- Botón revocar --}}
                            <div class="pt-2 border-t border-gray-200">
                                <p class="text-[10px] text-gray-500 mb-2">
                                    Puedes retirar tu consentimiento en cualquier momento. Esto cerrara tu sesion y no podras usar la aplicacion hasta volver a aceptar.
                                </p>
                                <button type="button" onclick="confirmarRevocacion()"
                                    class="text-xs text-red-600 hover:text-red-800 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    Revocar consentimiento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function confirmarRevocacion() {
                        Swal.fire({
                            title: 'Revocar consentimiento',
                            html: `
                                <p class="text-sm text-gray-600 mb-3">
                                    Al revocar tu consentimiento:
                                </p>
                                <ul class="text-left text-sm text-gray-600 space-y-1 mb-3">
                                    <li>• Se cerrara tu sesion automaticamente</li>
                                    <li>• No podras acceder a la aplicacion</li>
                                    <li>• Deberas aceptar las politicas de nuevo para volver a entrar</li>
                                </ul>
                                <p class="text-sm font-medium text-red-600">¿Estas seguro de que deseas continuar?</p>
                            `,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#DC2626',
                            cancelButtonColor: '#6B7280',
                            confirmButtonText: 'Si, revocar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Enviar formulario de revocación
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = '{{ route("politicas.revocar") }}';

                                const csrf = document.createElement('input');
                                csrf.type = 'hidden';
                                csrf.name = '_token';
                                csrf.value = '{{ csrf_token() }}';
                                form.appendChild(csrf);

                                document.body.appendChild(form);
                                form.submit();
                            }
                        });
                    }
                </script>
            @endif

        </div>
    </div>
</div>
