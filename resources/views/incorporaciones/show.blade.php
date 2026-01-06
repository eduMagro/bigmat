<x-app-layout>
    <x-slot name="title">Incorporación - {{ $incorporacion->name }} {{ $incorporacion->primer_apellido }}</x-slot>

    <div class="w-full max-w-5xl mx-auto py-6 px-4 sm:px-6">
        <!-- Cabecera -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
            <div>
                <a href="{{ route('incorporaciones.index') }}"
                    class="text-blue-600 hover:text-blue-800 flex items-center mb-2">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver al listado
                </a>
                <h1 class="text-2xl font-bold text-gray-800">{{ $incorporacion->name }}
                    {{ $incorporacion->primer_apellido }} {{ $incorporacion->segundo_apellido }}</h1>
                <div class="flex flex-wrap gap-2 mt-2">
                    @php $badge = $incorporacion->estado_badge; @endphp
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        bg-{{ $badge['color'] }}-100 text-{{ $badge['color'] }}-800">
                        {{ $badge['texto'] }}
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $incorporacion->empresa_destino === 'hpr_servicios' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800' }}">
                        {{ $incorporacion->empresa_nombre }}
                    </span>
                    @if ($incorporacion->puesto)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            {{ $incorporacion->puesto }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex gap-2">
                @if ($incorporacion->datos_completados_at)
                    <a href="{{ route('incorporaciones.verDescargarZip', $incorporacion) }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar ZIP
                    </a>
                @endif
                <button onclick="cambiarEstado()"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                    Cambiar estado
                </button>
            </div>
        </div>

        <!-- Sección de Aprobaciones -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Aprobaciones</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Aprobación RRHH -->
                <div
                    class="flex items-center justify-between p-4 rounded-lg border-2 {{ $incorporacion->aprobado_rrhh ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                    <div class="flex items-center">
                        <div class="mr-4">
                            @if ($incorporacion->aprobado_rrhh)
                                <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center cursor-pointer hover:bg-green-600 transition"
                                    onclick="revocarAprobacion('rrhh')">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-full border-4 border-gray-300 bg-white flex items-center justify-center cursor-pointer hover:border-green-400 hover:bg-green-50 transition"
                                    onclick="aprobarIncorporacion('rrhh')">
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Aprobación RRHH</p>
                            @if ($incorporacion->aprobado_rrhh)
                                <p class="text-sm text-green-600">
                                    Aprobado el {{ $incorporacion->aprobado_rrhh_at?->format('d/m/Y H:i') }}
                                    @if ($incorporacion->aprobadorRrhh)
                                        por {{ $incorporacion->aprobadorRrhh->nombre_completo }}
                                    @endif
                                </p>
                            @else
                                <p class="text-sm text-gray-500">Pendiente de aprobación</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Aprobación CEO -->
                <div
                    class="flex items-center justify-between p-4 rounded-lg border-2 {{ $incorporacion->aprobado_ceo ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                    <div class="flex items-center">
                        <div class="mr-4">
                            @if ($incorporacion->aprobado_ceo)
                                <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center cursor-pointer hover:bg-green-600 transition"
                                    onclick="revocarAprobacion('ceo')">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            @else
                                <div class="w-12 h-12 rounded-full border-4 border-gray-300 bg-white flex items-center justify-center cursor-pointer hover:border-green-400 hover:bg-green-50 transition {{ !$incorporacion->aprobado_rrhh ? 'opacity-50 pointer-events-none' : '' }}"
                                    onclick="aprobarIncorporacion('ceo')"
                                    title="{{ !$incorporacion->aprobado_rrhh ? 'Requiere aprobación de RRHH primero' : 'Clic para aprobar' }}">
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Aprobación CEO</p>
                            @if ($incorporacion->aprobado_ceo)
                                <p class="text-sm text-green-600">
                                    Aprobado el {{ $incorporacion->aprobado_ceo_at?->format('d/m/Y H:i') }}
                                    @if ($incorporacion->aprobadorCeo)
                                        por {{ $incorporacion->aprobadorCeo->nombre_completo }}
                                    @endif
                                </p>
                            @elseif(!$incorporacion->aprobado_rrhh)
                                <p class="text-sm text-gray-400">Requiere aprobación de RRHH primero</p>
                            @else
                                <p class="text-sm text-gray-500">Pendiente de aprobación</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Enlace del formulario -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Enlace del formulario</h2>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $incorporacion->url_formulario }}"
                            id="enlaceFormulario"
                            class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-600
                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <button onclick="copiarEnlace(this)"
                            class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 rounded-lg transition"
                            title="Copiar al portapapeles">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    @if ($incorporacion->enlace_enviado_at)
                        <p class="mt-2 text-sm text-green-600">
                            Enlace marcado como enviado el {{ $incorporacion->enlace_enviado_at->format('d/m/Y H:i') }}
                        </p>
                    @else
                        <button onclick="marcarEnviado()" class="mt-2 text-sm text-blue-600 hover:underline">
                            Marcar como enviado
                        </button>
                    @endif
                </div>

                <!-- Datos del candidato -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Datos Personales</h2>
                    @if ($incorporacion->datos_completados_at)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-500">DNI</label>
                                <p class="font-medium">{{ $incorporacion->dni }}</p>
                                {{-- Enlaces para ver/descargar imágenes del DNI --}}
                                <div class="flex gap-3 mt-2 flex-wrap">
                                    @if ($incorporacion->dni_frontal)
                                        <div class="flex items-center gap-1">
                                            <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $incorporacion->dni_frontal]) }}"
                                                target="_blank"
                                                class="text-blue-600 hover:underline text-sm flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Frontal
                                            </a>
                                            <button onclick="abrirModalResubir('dni_frontal', 'DNI Frontal')"
                                                class="text-amber-500 hover:text-amber-700 p-1" title="Resubir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                            </button>
                                            <button
                                                onclick="eliminarArchivoIncorporacion('dni_frontal', 'DNI Frontal')"
                                                class="text-red-500 hover:text-red-700 p-1" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <button onclick="abrirModalResubir('dni_frontal', 'DNI Frontal')"
                                            class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            Subir frontal
                                        </button>
                                    @endif
                                    @if ($incorporacion->dni_trasero)
                                        <div class="flex items-center gap-1">
                                            <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $incorporacion->dni_trasero]) }}"
                                                target="_blank"
                                                class="text-blue-600 hover:underline text-sm flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Trasero
                                            </a>
                                            <button onclick="abrirModalResubir('dni_trasero', 'DNI Trasero')"
                                                class="text-amber-500 hover:text-amber-700 p-1" title="Resubir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                            </button>
                                            <button
                                                onclick="eliminarArchivoIncorporacion('dni_trasero', 'DNI Trasero')"
                                                class="text-red-500 hover:text-red-700 p-1" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @else
                                        <button onclick="abrirModalResubir('dni_trasero', 'DNI Trasero')"
                                            class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            Subir trasero
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">N. Afiliación SS</label>
                                <div class="flex items-center gap-2">
                                    <p class="font-medium" id="texto-afiliacion-ss">
                                        {{ $incorporacion->numero_afiliacion_ss ?? 'No especificado' }}</p>
                                    <button onclick="editarAfiliacionSS()"
                                        class="text-amber-500 hover:text-amber-700 p-1" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Email</label>
                                <p class="font-medium">{{ $incorporacion->email }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Teléfono</label>
                                <p class="font-medium">{{ $incorporacion->telefono }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm text-gray-500">Certificado bancario</label>
                                @if ($incorporacion->certificado_bancario)
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $incorporacion->certificado_bancario]) }}"
                                            target="_blank" class="text-blue-600 hover:underline flex items-center">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Ver certificado
                                        </a>
                                        <button
                                            onclick="abrirModalResubir('certificado_bancario', 'Certificado Bancario')"
                                            class="text-amber-500 hover:text-amber-700 p-1" title="Resubir">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                        </button>
                                        <button
                                            onclick="eliminarArchivoIncorporacion('certificado_bancario', 'Certificado Bancario')"
                                            class="text-red-500 hover:text-red-700 p-1" title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <button onclick="abrirModalResubir('certificado_bancario', 'Certificado Bancario')"
                                        class="text-blue-600 hover:text-blue-800 flex items-center mt-1">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Subir certificado
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-gray-500">
                            Datos completados el {{ $incorporacion->datos_completados_at->format('d/m/Y H:i') }}
                        </p>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>Esperando que el candidato complete el formulario</p>
                        </div>
                    @endif
                </div>

                <!-- Documentos post-incorporación -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Documentos Post-Incorporación</h2>
                        <span class="text-sm text-gray-500">{{ $incorporacion->porcentajeDocumentosPost() }}%
                            completado</span>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="w-full bg-gray-200 rounded-full h-3 mb-6">
                        <div class="bg-green-500 h-3 rounded-full transition-all"
                            style="width: {{ $incorporacion->porcentajeDocumentosPost() }}%"></div>
                    </div>

                    <!-- Checklist -->
                    <div class="space-y-3">
                        @foreach ($documentosPost as $tipo => $item)
                            @if ($item['multiple'] ?? false)
                                {{-- Caso especial: Tipos con múltiples archivos --}}
                                <div class="p-4 border rounded-lg {{ $item['completado'] ? 'bg-green-50 border-green-200' : 'bg-white' }}"
                                    id="doc-{{ $tipo }}">
                                    @if ($tipo === 'contrato_trabajo')
                                        <div class="mb-3 p-3 bg-blue-50/50 rounded border border-blue-100">
                                            <label
                                                class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1 block">Fecha
                                                de Incorporación</label>
                                            <div class="flex gap-2 items-center">
                                                <input type="date"
                                                    value="{{ $incorporacion->fecha_incorporacion ? $incorporacion->fecha_incorporacion->format('Y-m-d') : '' }}"
                                                    onchange="actualizarFechaIncorporacion(this.value)"
                                                    class="text-sm rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 px-3 py-1.5 w-auto">
                                                <span id="fecha-status"
                                                    class="text-xs font-medium text-green-600 hidden transition-opacity duration-300">
                                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Guardado
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            @if ($item['completado'])
                                                <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <div class="w-6 h-6 border-2 border-gray-300 rounded-full mr-3"></div>
                                            @endif
                                            <div>
                                                <p
                                                    class="font-medium {{ $item['completado'] ? 'text-green-800' : 'text-gray-800' }}">
                                                    {{ $item['nombre'] }}</p>
                                                <p class="text-sm text-gray-500">
                                                    @if ($tipo === 'contrato_trabajo')
                                                        {{ $item['total_archivos'] }} archivos
                                                    @else
                                                        {{ $item['total_archivos'] }} de {{ $item['max'] }} archivos
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        @if ($item['puede_anadir'])
                                            <button
                                                onclick="abrirModalSubir('{{ $tipo }}', '{{ $item['nombre'] }}')"
                                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Añadir
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Lista de archivos subidos --}}
                                    @if ($item['documentos']->count() > 0 || $item['formaciones']->count() > 0)
                                        <div class="space-y-2 ml-9">
                                            @foreach ($item['documentos'] as $index => $doc)
                                                <div
                                                    class="flex items-center justify-between py-2 px-3 bg-white rounded border">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-5 h-5 text-red-500" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="text-sm text-gray-700">
                                                            @if ($tipo === 'contrato_trabajo')
                                                                {{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') : 'Sin fecha' }}
                                                            @else
                                                                Archivo {{ $index + 1 }}
                                                            @endif
                                                        </span>
                                                        @if ($doc->notas)
                                                            <span class="text-xs text-gray-400">-
                                                                {{ $doc->notas }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $doc->archivo]) }}"
                                                            target="_blank" class="text-blue-600 hover:text-blue-800"
                                                            title="Ver">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </a>
                                                        <button
                                                            onclick="eliminarDocumentoMultiple({{ $doc->id }}, '{{ $tipo }}')"
                                                            class="text-red-600 hover:text-red-800" title="Eliminar">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @foreach ($item['formaciones'] as $index => $form)
                                                <div
                                                    class="flex items-center justify-between py-2 px-3 bg-white rounded border">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-5 h-5 text-red-500" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span class="text-sm text-gray-700">Archivo
                                                            {{ $item['documentos']->count() + $index + 1 }}</span>
                                                        <span
                                                            class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">Por
                                                            candidato</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $form->archivo]) }}"
                                                            target="_blank" class="text-blue-600 hover:text-blue-800"
                                                            title="Ver">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </a>
                                                        <button
                                                            onclick="eliminarArchivoFormacion({{ $form->id }}, 'Formación del puesto')"
                                                            class="text-red-600 hover:text-red-800" title="Eliminar">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                {{-- Caso normal: documento único --}}
                                <div class="flex items-center justify-between p-4 border rounded-lg {{ $item['completado'] ? 'bg-green-50 border-green-200' : 'bg-white' }}"
                                    id="doc-{{ $tipo }}">
                                    <div class="flex items-center">
                                        @if ($item['completado'])
                                            <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <div class="w-6 h-6 border-2 border-gray-300 rounded-full mr-3"></div>
                                        @endif
                                        <div>
                                            <p
                                                class="font-medium {{ $item['completado'] ? 'text-green-800' : 'text-gray-800' }}">
                                                {{ $item['nombre'] }}</p>
                                            @if (($item['documento'] ?? null) && $item['documento']->notas)
                                                <p class="text-sm text-gray-500">{{ $item['documento']->notas }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($item['documento'] ?? false)
                                            {{-- Documento subido desde post-incorporación --}}
                                            <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $item['documento']->archivo]) }}"
                                                target="_blank" class="text-blue-600 hover:text-blue-800"
                                                title="Ver documento">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <button onclick="eliminarDocumento('{{ $tipo }}')"
                                                class="text-red-600 hover:text-red-800" title="Eliminar">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @elseif($item['formacion'] ?? false)
                                            {{-- Documento subido por el candidato desde formulario público --}}
                                            <span class="text-xs text-gray-500 mr-2">Por candidato</span>
                                            <a href="{{ route('incorporaciones.verArchivo', [$incorporacion, $item['formacion']->archivo]) }}"
                                                target="_blank" class="text-blue-600 hover:text-blue-800"
                                                title="Ver documento">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <button
                                                onclick="eliminarArchivoFormacion({{ $item['formacion']->id }}, '{{ $item['nombre'] }}')"
                                                class="text-red-600 hover:text-red-800" title="Eliminar documento">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <button
                                                onclick="abrirModalSubir('{{ $tipo }}', '{{ $item['nombre'] }}')"
                                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                                                Subir
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Columna lateral -->
            <div class="space-y-6">
                <!-- Asignar Usuario -->
                <div class="bg-white rounded-lg shadow-sm border p-6" x-data="userAssignment()">
                    <h3 class="font-semibold text-gray-800 mb-4">Usuario Asignado</h3>

                    <template x-if="selectedUser">
                        <div
                            class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <template x-if="selectedUser.imagen_url">
                                    <img :src="selectedUser.imagen_url"
                                        class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                </template>
                                <template x-if="!selectedUser.imagen_url">
                                    <div
                                        class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="w-6 h-6">
                                            <path fill-rule="evenodd"
                                                d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </template>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate"
                                        x-text="selectedUser.nombre_completo"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="selectedUser.email"></p>
                                </div>
                            </div>
                            <button @click="removeUser()" class="ml-2 text-red-500 hover:text-red-700 flex-shrink-0"
                                title="Desvincular">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>

                    <template x-if="!selectedUser">
                        <div class="relative">
                            <label class="block text-sm text-gray-500 mb-1">Buscar usuario existente</label>
                            <input type="text" x-model="query" @input.debounce.300ms="search"
                                placeholder="Nombre, DNI o email..."
                                class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">

                            <div x-show="suggestions.length > 0" @click.away="suggestions = []"
                                class="absolute z-10 w-full bg-white shadow-lg rounded-lg mt-1 border border-gray-200 max-h-60 overflow-y-auto">
                                <template x-for="user in suggestions" :key="user.id">
                                    <div @click="selectUser(user)"
                                        class="p-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2">
                                        <template x-if="user.imagen_url">
                                            <img :src="user.imagen_url"
                                                class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                        </template>
                                        <template x-if="!user.imagen_url">
                                            <div
                                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0 text-gray-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                    fill="currentColor" class="w-5 h-5">
                                                    <path fill-rule="evenodd"
                                                        d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </template>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate"
                                                x-text="user.nombre_completo"></p>
                                            <p class="text-xs text-gray-500" x-text="user.dni"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Vincula esta incorporación a un usuario del sistema.
                            </p>
                        </div>
                    </template>
                </div>
                <!-- Info rápida -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Información</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Creada por</dt>
                            <dd class="font-medium">{{ $incorporacion->creador?->nombre_completo ?? 'Sistema' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Fecha de creación</dt>
                            <dd class="font-medium">{{ $incorporacion->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </dd>
                        </div>
                        @if ($incorporacion->datos_completados_at)
                            <div>
                                <dt class="text-gray-500">Datos completados</dt>
                                <dd class="font-medium">
                                    {{ $incorporacion->datos_completados_at->format('d/m/Y H:i') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Historial -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Historial</h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @forelse($incorporacion->logs->sortByDesc('created_at') as $log)
                            <div class="border-l-2 border-gray-200 pl-4 pb-4">
                                <p class="text-sm font-medium text-gray-800">{{ $log->accion_texto }}</p>
                                @if ($log->descripcion)
                                    <p class="text-sm text-gray-500">{{ $log->descripcion }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $log->created_at?->format('d/m/Y H:i') ?? '' }}
                                    @if ($log->usuario)
                                        por {{ $log->usuario->nombre_completo }}
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Sin actividad registrada</p>
                        @endforelse
                    </div>
                </div>

                <!-- Acciones peligrosas -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h3 class="font-semibold text-red-800 mb-2">Zona peligrosa</h3>
                    <form id="formEliminarIncorporacion" method="POST"
                        action="{{ route('incorporaciones.destroy', $incorporacion) }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmarEliminarIncorporacion()"
                            class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition">
                            Eliminar incorporación
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal subir documento post-incorporación -->
    <div id="modalSubir"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4" id="modalTitulo">Subir documento</h3>
            <form id="formSubir" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="tipo" id="modalTipo">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Archivo</label>
                    <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-700
                        file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                        file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100 file:cursor-pointer
                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                        transition-all duration-200">
                    <p class="text-xs text-gray-500 mt-1.5">PDF, JPG o PNG. Máximo 10MB.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                    <textarea name="notas" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-700
                        placeholder-gray-400 resize-none
                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                        transition-all duration-200"
                        placeholder="Observaciones sobre el documento..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="cerrarModal()"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Subir documento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal resubir documento candidato (DNI, certificado bancario) -->
    <div id="modalResubir"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4" id="modalResubirTitulo">Resubir documento</h3>
            <form id="formResubir" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="campo" id="modalResubirCampo">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo archivo</label>
                    <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-gray-700
                        file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                        file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700
                        hover:file:bg-amber-100 file:cursor-pointer
                        focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500
                        transition-all duration-200">
                    <p class="text-xs text-gray-500 mt-1.5">PDF, JPG o PNG. Máximo 5MB. El archivo anterior será
                        reemplazado.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="cerrarModalResubir()"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">
                        Reemplazar documento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        @php
            $selectedUser = $incorporacion->usuario
                ? [
                    'id' => $incorporacion->usuario->id,
                    'nombre_completo' => $incorporacion->usuario->nombre_completo,
                    'email' => $incorporacion->usuario->email,
                    'dni' => $incorporacion->usuario->dni,
                    'imagen_url' => $incorporacion->usuario->ruta_imagen,
                ]
                : null;
        @endphp

        function userAssignment() {
            return {
                query: '',
                suggestions: [],
                selectedUser: @json($selectedUser),

                async search() {
                    if (this.query.length < 2) {
                        this.suggestions = [];
                        return;
                    }
                    try {
                        const res = await fetch(`{{ route('incorporaciones.buscarUsuarios') }}?q=${this.query}`);
                        const data = await res.json();
                        this.suggestions = data.users;
                    } catch (e) {
                        console.error("Error buscando usuarios", e);
                    }
                },

                async selectUser(user) {
                    this.updateUser(user.id, user);
                },

                async removeUser() {
                    Swal.fire({
                        title: '¿Desvincular usuario?',
                        text: "El usuario ya no estará asociado a esta incorporación.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, desvincular'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.updateUser(null, null);
                        }
                    })
                },

                async updateUser(id, userObj) {
                    try {
                        const res = await fetch(
                            '{{ route('incorporaciones.editarActualizarCampo', $incorporacion) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    campo: 'user_id',
                                    valor: id
                                })
                            });
                        const data = await res.json();
                        if (data.success) {
                            this.selectedUser = userObj;
                            this.suggestions = [];
                            this.query = '';
                            Swal.fire({
                                icon: 'success',
                                title: id ? 'Usuario vinculado' : 'Usuario desvinculado',
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Error al actualizar', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Error de conexión', 'error');
                    }
                }
            }
        }

        function copiarEnlace(btn) {
            const input = document.getElementById('enlaceFormulario');

            // Copiar al portapapeles
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');

            // Cambiar icono a check temporalmente
            const originalHTML = btn.innerHTML;
            btn.innerHTML =
                '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            btn.classList.add('bg-green-100');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('bg-green-100');
            }, 1500);
        }

        function marcarEnviado() {
            fetch('{{ route('incorporaciones.editarMarcarEnviado', $incorporacion) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(r => r.json())
                .then(data => {

                    if (data.success) {
                        location.reload();
                    }
                });
        }

        function actualizarFechaIncorporacion(fecha) {
            const status = document.getElementById('fecha-status');
            status.classList.add('hidden');

            fetch('{{ route('incorporaciones.updateFecha', $incorporacion) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        fecha_incorporacion: fecha
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        status.classList.remove('hidden');
                        setTimeout(() => status.classList.add('hidden'), 2000);
                    } else {
                        Swal.fire('Error', 'No se pudo actualizar la fecha', 'error');
                    }
                });
        }

        function abrirModalSubir(tipo, nombre) {
            document.getElementById('modalTipo').value = tipo;
            document.getElementById('modalTitulo').textContent = 'Subir: ' + nombre;
            document.getElementById('modalSubir').classList.remove('hidden');
        }

        function cerrarModal() {
            document.getElementById('modalSubir').classList.add('hidden');
            document.getElementById('formSubir').reset();
        }

        document.getElementById('formSubir').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('{{ route('incorporaciones.crearSubirDocumento', $incorporacion) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Documento subido',
                            text: 'El documento se ha subido correctamente.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al subir el documento'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al subir el documento'
                    });
                });
        });

        function eliminarDocumento(tipo) {
            Swal.fire({
                title: '¿Eliminar documento?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ url('incorporaciones/' . $incorporacion->id . '/documento') }}/' +
                            tipo, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                }
                            })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: 'El documento ha sido eliminado.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            }
                        });
                }
            });
        }

        function eliminarDocumentoMultiple(documentoId, tipo) {
            Swal.fire({
                title: '¿Eliminar documento?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ url('incorporaciones/' . $incorporacion->id . '/documento') }}/' + tipo +
                            '?documento_id=' + documentoId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                }
                            })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: 'El documento ha sido eliminado.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            }
                        });
                }
            });
        }

        function cambiarEstado() {
            const actual = '{{ $incorporacion->estado }}';

            Swal.fire({
                title: 'Cambiar estado',
                input: 'select',
                inputOptions: {
                    'pendiente': 'Pendiente',
                    'datos_recibidos': 'Datos recibidos',
                    'en_proceso': 'En proceso',
                    'completada': 'Completada',
                    'cancelada': 'Cancelada'
                },
                inputValue: actual,
                showCancelButton: true,
                confirmButtonText: 'Cambiar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes seleccionar un estado';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('incorporaciones.editarCambiarEstado', $incorporacion) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                estado: result.value
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Estado actualizado',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            }
                        });
                }
            });
        }

        function confirmarEliminarIncorporacion() {
            Swal.fire({
                title: '¿Eliminar incorporación?',
                text: 'Esta acción no se puede deshacer. Se eliminarán todos los documentos asociados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formEliminarIncorporacion').submit();
                }
            });
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalResubir();
            }
        });

        // Funciones para resubir documentos del candidato
        function abrirModalResubir(campo, nombre) {
            document.getElementById('modalResubirCampo').value = campo;
            document.getElementById('modalResubirTitulo').textContent = 'Resubir: ' + nombre;
            document.getElementById('modalResubir').classList.remove('hidden');
        }

        function cerrarModalResubir() {
            document.getElementById('modalResubir').classList.add('hidden');
            document.getElementById('formResubir').reset();
        }

        document.getElementById('formResubir').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('{{ route('incorporaciones.editarResubirArchivo', $incorporacion) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Documento actualizado',
                            text: data.message ||
                                'El documento se ha reemplazado correctamente.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al subir el documento'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al subir el documento'
                    });
                });
        });

        function editarAfiliacionSS() {
            const valorActual = '{{ $incorporacion->numero_afiliacion_ss ?? '' }}';

            Swal.fire({
                title: 'Editar N. Afiliación SS',
                input: 'text',
                inputValue: valorActual,
                inputPlaceholder: '123456789012',
                inputAttributes: {
                    maxlength: 12,
                    pattern: '[0-9]{12}'
                },
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes introducir el número de afiliación';
                    }
                    if (!/^[0-9]{12}$/.test(value)) {
                        return 'El número debe tener exactamente 12 dígitos';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('incorporaciones.editarActualizarCampo', $incorporacion) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                campo: 'numero_afiliacion_ss',
                                valor: result.value
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('texto-afiliacion-ss').textContent = result
                                    .value;
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualizado',
                                    text: 'Número de afiliación actualizado correctamente.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al actualizar'
                                });
                            }
                        });
                }
            });
        }

        function aprobarIncorporacion(tipo) {
            const titulo = tipo === 'rrhh' ? 'RRHH' : 'CEO';
            Swal.fire({
                title: `¿Aprobar incorporación como ${titulo}?`,
                text: tipo === 'rrhh' ?
                    'Esto indica que el trabajador ha pasado la entrevista y se propone al CEO.' :
                    'Esto confirma la aprobación final de la incorporación.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, aprobar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = tipo === 'rrhh' ?
                        '{{ route('incorporaciones.editarAprobarRrhh', $incorporacion) }}' :
                        '{{ route('incorporaciones.editarAprobarCeo', $incorporacion) }}';

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Aprobado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al aprobar'
                                });
                            }
                        });
                }
            });
        }

        function revocarAprobacion(tipo) {
            const titulo = tipo === 'rrhh' ? 'RRHH' : 'CEO';
            Swal.fire({
                title: `¿Revocar aprobación ${titulo}?`,
                text: 'La aprobación será eliminada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, revocar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = tipo === 'rrhh' ?
                        '{{ route('incorporaciones.editarRevocarRrhh', $incorporacion) }}' :
                        '{{ route('incorporaciones.editarRevocarCeo', $incorporacion) }}';

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Revocado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al revocar'
                                });
                            }
                        });
                }
            });
        }

        function eliminarArchivoIncorporacion(tipo, nombre) {
            Swal.fire({
                title: `¿Eliminar ${nombre}?`,
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('incorporaciones.eliminarArchivo', $incorporacion) }}', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                tipo: tipo
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al eliminar'
                                });
                            }
                        });
                }
            });
        }

        function eliminarArchivoFormacion(formacionId, nombre) {
            Swal.fire({
                title: `¿Eliminar ${nombre}?`,
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('incorporaciones.eliminarArchivo', $incorporacion) }}', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                tipo: 'formacion',
                                formacion_id: formacionId
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al eliminar'
                                });
                            }
                        });
                }
            });
        }
    </script>
</x-app-layout>
