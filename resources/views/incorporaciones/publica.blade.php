<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Incorporación - {{ $incorporacion->empresa_nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .file-input-wrapper {
            position: relative;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* Estilos mejorados para inputs */
        .input-styled {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #374151;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        .input-styled::placeholder {
            color: #9ca3af;
        }

        .input-styled:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .input-styled:hover:not(:focus) {
            border-color: #9ca3af;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto py-8 px-4">
        <!-- Cabecera -->
        <div class="text-center mb-8">
            <img src="{{ asset('imagenes/logoBigmat.png') }}" alt="Bigmat" class="h-20 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Formulario de Incorporación</h1>
            <p class="text-gray-600 mt-2">{{ $incorporacion->empresa_nombre }}</p>
            @if ($incorporacion->puesto)
                <p class="text-sm text-gray-500">Puesto: {{ $incorporacion->puesto }}</p>
            @endif
        </div>

        <!-- Mensajes -->
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulario -->
        <form method="POST" action="{{ route('incorporacion.publica.store', $incorporacion->token) }}"
            enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Sección: Documento de Identidad -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span
                        class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full mr-3 text-sm font-bold">1</span>
                    Documento de Identidad
                </h2>

                <p class="text-sm text-gray-600 mb-4">
                    Sube fotos claras de tu DNI o NIE e introduce los datos manualmente.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <!-- DNI Frontal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            DNI - Parte Frontal <span class="text-red-500">*</span>
                        </label>
                        @if (isset($archivosTmp['dni_frontal']))
                            <div class="bg-green-50 border border-green-300 rounded-lg p-3 mb-2">
                                <div class="flex items-center text-green-700">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm font-medium">Archivo guardado:
                                        {{ $archivosTmp['dni_frontal']['nombre_original'] }}</span>
                                </div>
                                <p class="text-xs text-green-600 mt-1">Puedes subir otro archivo para reemplazarlo</p>
                            </div>
                        @endif
                        <div class="file-input-wrapper">
                            <div class="border-2 border-dashed {{ isset($archivosTmp['dni_frontal']) ? 'border-green-300 bg-green-50' : 'border-gray-300' }} rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer"
                                id="dropzone-dni-frontal">
                                <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-sm text-gray-600" id="text-dni-frontal">
                                    {{ isset($archivosTmp['dni_frontal']) ? 'Cambiar archivo' : 'Toca para subir archivo' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Foto o PDF del frente del DNI</p>
                            </div>
                            <input type="file" name="dni_frontal" accept="image/*,.pdf" capture="environment"
                                {{ isset($archivosTmp['dni_frontal']) ? '' : 'required' }}
                                onchange="updateFileName(this, 'text-dni-frontal'); previewDniImage(this, 'preview-dni-frontal')">
                        </div>
                        <div id="preview-dni-frontal" class="mt-2 hidden">
                            <img src="" alt="Preview DNI frontal" class="max-h-32 rounded border">
                        </div>
                    </div>

                    <!-- DNI Trasero -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            DNI - Parte Trasera <span class="text-red-500">*</span>
                        </label>
                        @if (isset($archivosTmp['dni_trasero']))
                            <div class="bg-green-50 border border-green-300 rounded-lg p-3 mb-2">
                                <div class="flex items-center text-green-700">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm font-medium">Archivo guardado:
                                        {{ $archivosTmp['dni_trasero']['nombre_original'] }}</span>
                                </div>
                                <p class="text-xs text-green-600 mt-1">Puedes subir otro archivo para reemplazarlo</p>
                            </div>
                        @endif
                        <div class="file-input-wrapper">
                            <div class="border-2 border-dashed {{ isset($archivosTmp['dni_trasero']) ? 'border-green-300 bg-green-50' : 'border-gray-300' }} rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer"
                                id="dropzone-dni-trasero">
                                <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <p class="text-sm text-gray-600" id="text-dni-trasero">
                                    {{ isset($archivosTmp['dni_trasero']) ? 'Cambiar archivo' : 'Toca para subir archivo' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Foto o PDF del reverso del DNI</p>
                            </div>
                            <input type="file" name="dni_trasero" accept="image/*,.pdf" capture="environment"
                                {{ isset($archivosTmp['dni_trasero']) ? '' : 'required' }}
                                onchange="updateFileName(this, 'text-dni-trasero'); previewDniImage(this, 'preview-dni-trasero')">
                        </div>
                        <div id="preview-dni-trasero" class="mt-2 hidden">
                            <img src="" alt="Preview DNI trasero" class="max-h-32 rounded border">
                        </div>
                    </div>
                </div>

                <!-- Campo DNI/NIE y datos personales -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3">
                        <span class="font-medium">Introduce tus datos</span> tal como aparecen en tu DNI/NIE:
                    </p>

                    <div class="mb-4">
                        <label for="dni" class="block text-sm font-medium text-gray-700 mb-1">
                            DNI/NIE <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="dni" name="dni"
                            value="{{ old('dni', $incorporacion->dni) }}" placeholder="12345678A o X1234567A"
                            maxlength="9" required class="input-styled uppercase w-full sm:w-64">
                        <p class="text-xs text-gray-500 mt-1">DNI: 8 números + letra | NIE: X/Y/Z + 7 números + letra
                        </p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="nombre_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nombre_dni" name="nombre_dni"
                                value="{{ old('nombre_dni', $incorporacion->name) }}" placeholder="Ej: María Carmen"
                                class="input-styled" required>
                        </div>
                        <div>
                            <label for="primer_apellido_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                Primer Apellido <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="primer_apellido_dni" name="primer_apellido_dni"
                                value="{{ old('primer_apellido_dni', $incorporacion->primer_apellido) }}"
                                placeholder="Ej: García" class="input-styled" required>
                        </div>
                        <div>
                            <label for="segundo_apellido_dni" class="block text-sm font-medium text-gray-700 mb-1">
                                Segundo Apellido
                            </label>
                            <input type="text" id="segundo_apellido_dni" name="segundo_apellido_dni"
                                value="{{ old('segundo_apellido_dni', $incorporacion->segundo_apellido) }}"
                                placeholder="Ej: López" class="input-styled">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <svg class="w-4 h-4 inline-block text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Estos datos deben coincidir exactamente con los de tu DNI/NIE
                    </p>
                </div>
            </div>

            <!-- Sección: Datos Personales -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span
                        class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full mr-3 text-sm font-bold">2</span>
                    Datos Personales
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <!-- Número afiliación -->
                    <div>
                        <label for="numero_afiliacion_ss" class="block text-sm font-medium text-gray-700 mb-1.5">
                            N. Afiliación Seg. Social <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="numero_afiliacion_ss" name="numero_afiliacion_ss"
                            value="{{ old('numero_afiliacion_ss') }}" placeholder="123456789012" maxlength="12"
                            class="input-styled" required>
                        <p class="text-xs text-gray-500 mt-1.5">12 dígitos (tarjeta sanitaria antigua)</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                            value="{{ old('email', $incorporacion->email_provisional) }}" placeholder="tu@email.com"
                            class="input-styled" required>
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" id="telefono" name="telefono"
                            value="{{ old('telefono', $incorporacion->telefono_provisional) }}"
                            placeholder="612345678" maxlength="9" class="input-styled" required>
                    </div>

                    <!-- Certificado bancario -->
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Certificado titularidad cuenta bancaria <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-amber-600 mb-2">
                            Debe ser el certificado oficial del banco, no una captura de pantalla. Es posible que el
                            banco cobre por esta gestión.
                        </p>
                        <!-- Preview del ejemplo -->
                        <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg cursor-pointer hover:bg-blue-100 transition"
                            onclick="mostrarEjemploCertificado()">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-24 h-32 bg-white border border-blue-200 rounded shadow-sm overflow-hidden flex-shrink-0">
                                    <img src="{{ asset('documentos/ejemplo_certificado_titularidad.jpg') }}"
                                        alt="Ejemplo certificado" class="w-full h-full object-cover object-top">
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-700">Ver ejemplo de certificado válido</p>
                                    <p class="text-xs text-blue-600">Toca aquí para ver cómo debe ser el documento</p>
                                </div>
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                        </div>
                        @if (isset($archivosTmp['certificado_bancario']))
                            <div class="bg-green-50 border border-green-300 rounded-lg p-3 mb-2">
                                <div class="flex items-center text-green-700">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm font-medium">Archivo guardado:
                                        {{ $archivosTmp['certificado_bancario']['nombre_original'] }}</span>
                                </div>
                                <p class="text-xs text-green-600 mt-1">Puedes subir otro archivo para reemplazarlo</p>
                            </div>
                        @endif
                        <div class="file-input-wrapper">
                            <div class="border-2 border-dashed {{ isset($archivosTmp['certificado_bancario']) ? 'border-green-300 bg-green-50' : 'border-gray-300' }} rounded-lg p-4 text-center hover:border-blue-400 transition cursor-pointer"
                                id="dropzone-bancario">
                                <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-sm text-gray-600" id="text-bancario">
                                    {{ isset($archivosTmp['certificado_bancario']) ? 'Cambiar archivo' : 'Haz clic o arrastra el archivo aquí' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">PDF, JPG o PNG (máx. 15MB)</p>
                            </div>
                            <input type="file" name="certificado_bancario" accept=".pdf,.jpg,.jpeg,.png"
                                {{ isset($archivosTmp['certificado_bancario']) ? '' : 'required' }}
                                onchange="updateFileName(this, 'text-bancario')">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección: Formación -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span
                        class="flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full mr-3 text-sm font-bold">3</span>
                    Documentación de Formación
                </h2>

                <p class="text-sm text-gray-600 mb-4">
                    Si has realizado cursos o tienes certificados de formación relacionados con tu puesto,
                    adjúntalos a continuación:
                </p>

                <div class="space-y-4">
                    <!-- Otros cursos -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Otros cursos (opcional)
                        </label>
                        <p class="text-xs text-gray-500 mb-2">Puente grúa, aparatos elevadores, espacios
                            confinados, carretilla...</p>
                        <div id="otros-cursos-container" class="space-y-3">
                            <div class="flex gap-2 items-start otro-curso">
                                <input type="text" name="formacion_otros_nombres[]"
                                    placeholder="Nombre del curso" class="input-styled flex-1">
                                <div class="file-input-wrapper flex-1">
                                    <div
                                        class="border border-gray-300 rounded-lg p-2.5 text-center cursor-pointer text-sm hover:border-gray-400 transition">
                                        <span class="text-gray-500" id="text-otro-0">Seleccionar...</span>
                                    </div>
                                    <input type="file" name="formacion_otros[]" accept=".pdf,.jpg,.jpeg,.png"
                                        onchange="updateFileName(this, 'text-otro-0')">
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="agregarOtroCurso()"
                            class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                            + Añadir otro curso
                        </button>
                    </div>
                </div>
            </div>

            <!-- Aviso RGPD -->
            <div class="bg-gray-50 border rounded-lg p-4 text-sm text-gray-600">
                <p>
                    Al enviar este formulario, aceptas que tus datos personales sean tratados para gestionar tu proceso
                    de incorporación
                    de acuerdo con nuestra política de privacidad. Los documentos enviados serán almacenados de forma
                    segura.
                </p>
            </div>

            <!-- Botón enviar -->
            <button type="submit"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition text-lg">
                Enviar documentación
            </button>
        </form>

        <!-- Footer -->
        <p class="text-center text-sm text-gray-500 mt-8">
            {{ $incorporacion->empresa_nombre }} &middot; Formulario de incorporación
        </p>
    </div>

    <!-- Modal ejemplo certificado -->
    <div id="modal-ejemplo-certificado"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800">Ejemplo de Certificado de Titularidad Bancaria</h3>
                <button type="button" onclick="cerrarModalCertificado()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-4 flex justify-center">
                <img src="{{ asset('documentos/ejemplo_certificado_titularidad.jpg') }}"
                    alt="Ejemplo de certificado de titularidad bancaria"
                    class="max-w-full max-h-[60vh] object-contain border rounded shadow">
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-end">
                <button type="button" onclick="cerrarModalCertificado()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    Entendido
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal certificado ejemplo
        function mostrarEjemploCertificado() {
            document.getElementById('modal-ejemplo-certificado').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalCertificado() {
            document.getElementById('modal-ejemplo-certificado').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalCertificado();
            }
        });

        // Cerrar modal al clicar fuera
        document.getElementById('modal-ejemplo-certificado').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalCertificado();
            }
        });

        function updateFileName(input, textId) {
            const text = document.getElementById(textId);
            if (input.files.length > 0) {
                text.textContent = input.files[0].name;
                text.classList.add('text-green-600', 'font-medium');
                text.classList.remove('text-gray-600');
            }
        }

        // Preview de imágenes del DNI
        function previewDniImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const img = preview.querySelector('img');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.classList.add('hidden');
            }
        }

        let otrosCursosCount = 1;

        function agregarOtroCurso() {
            const container = document.getElementById('otros-cursos-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2 items-start otro-curso';
            div.innerHTML = `
                <input type="text" name="formacion_otros_nombres[]" placeholder="Nombre del curso"
                    class="input-styled flex-1">
                <div class="file-input-wrapper flex-1">
                    <div class="border border-gray-300 rounded-lg p-2.5 text-center cursor-pointer text-sm hover:border-gray-400 transition">
                        <span class="text-gray-500" id="text-otro-${otrosCursosCount}">Seleccionar...</span>
                    </div>
                    <input type="file" name="formacion_otros[]" accept=".pdf,.jpg,.jpeg,.png"
                        onchange="updateFileName(this, 'text-otro-${otrosCursosCount}')">
                </div>
                <button type="button" onclick="this.closest('.otro-curso').remove()" class="text-red-500 hover:text-red-700 p-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            container.appendChild(div);
            otrosCursosCount++;
        }

        // Convertir DNI manual a mayúsculas (si se usa el campo manual)
        const dniInput = document.getElementById('dni');
        if (dniInput) {
            dniInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    </script>
</body>

</html>
