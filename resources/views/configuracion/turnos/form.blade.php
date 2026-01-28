<!-- Formulario compartido para crear/editar turnos -->
<div class="space-y-6">
    <!-- Nombre del turno -->
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del turno</label>
        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $turno->nombre ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            required maxlength="50" placeholder="ej: Mañana, Tarde, Noche">
        @error('nombre')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Checkbox turno partido -->
    <div class="flex items-center">
        <input type="checkbox" name="es_partido" id="es_partido" value="1"
            {{ old('es_partido', $turno->es_partido ?? false) ? 'checked' : '' }}
            class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 focus:ring-blue-500"
            onchange="toggleSegundo()">
        <label for="es_partido" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
            Turno partido (dos segmentos horarios)
        </label>
    </div>

    <!-- Primer segmento -->
    <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Primer segmento</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Hora inicio -->
            <div>
                <label for="hora_inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hora de inicio</label>
                <input type="time" name="hora_inicio" id="hora_inicio"
                    value="{{ old('hora_inicio', isset($turno) ? substr($turno->hora_inicio, 0, 5) : '') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required>
                @error('hora_inicio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Hora fin -->
            <div>
                <label for="hora_fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hora de fin</label>
                <input type="time" name="hora_fin" id="hora_fin"
                    value="{{ old('hora_fin', isset($turno) ? substr($turno->hora_fin, 0, 5) : '') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required>
                @error('hora_fin')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Offset de días primer segmento -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Offset inicio -->
            <div>
                <label for="offset_dias_inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Offset día de inicio
                </label>
                <select name="offset_dias_inicio" id="offset_dias_inicio"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="-1" {{ old('offset_dias_inicio', $turno->offset_dias_inicio ?? 0) == -1 ? 'selected' : '' }}>
                        Día anterior (-1)
                    </option>
                    <option value="0" {{ old('offset_dias_inicio', $turno->offset_dias_inicio ?? 0) == 0 ? 'selected' : '' }}>
                        Mismo día (0)
                    </option>
                    <option value="1" {{ old('offset_dias_inicio', $turno->offset_dias_inicio ?? 0) == 1 ? 'selected' : '' }}>
                        Día siguiente (+1)
                    </option>
                </select>
                @error('offset_dias_inicio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Offset fin -->
            <div>
                <label for="offset_dias_fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Offset día de fin
                </label>
                <select name="offset_dias_fin" id="offset_dias_fin"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="-1" {{ old('offset_dias_fin', $turno->offset_dias_fin ?? 0) == -1 ? 'selected' : '' }}>
                        Día anterior (-1)
                    </option>
                    <option value="0" {{ old('offset_dias_fin', $turno->offset_dias_fin ?? 0) == 0 ? 'selected' : '' }}>
                        Mismo día (0)
                    </option>
                    <option value="1" {{ old('offset_dias_fin', $turno->offset_dias_fin ?? 0) == 1 ? 'selected' : '' }}>
                        Día siguiente (+1)
                    </option>
                </select>
                @error('offset_dias_fin')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Segundo segmento (turno partido) -->
    <div id="segundo_segmento" class="bg-green-50 dark:bg-green-900/30 p-4 rounded-lg" style="display: none;">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Segundo segmento</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Hora inicio 2 -->
            <div>
                <label for="hora_inicio2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hora de inicio</label>
                <input type="time" name="hora_inicio2" id="hora_inicio2"
                    value="{{ old('hora_inicio2', isset($turno) && $turno->hora_inicio2 ? substr($turno->hora_inicio2, 0, 5) : '') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('hora_inicio2')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Hora fin 2 -->
            <div>
                <label for="hora_fin2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hora de fin</label>
                <input type="time" name="hora_fin2" id="hora_fin2"
                    value="{{ old('hora_fin2', isset($turno) && $turno->hora_fin2 ? substr($turno->hora_fin2, 0, 5) : '') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('hora_fin2')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Offset de días segundo segmento -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Offset inicio 2 -->
            <div>
                <label for="offset_dias_inicio2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Offset día de inicio
                </label>
                <select name="offset_dias_inicio2" id="offset_dias_inicio2"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="-1" {{ old('offset_dias_inicio2', $turno->offset_dias_inicio2 ?? 0) == -1 ? 'selected' : '' }}>
                        Día anterior (-1)
                    </option>
                    <option value="0" {{ old('offset_dias_inicio2', $turno->offset_dias_inicio2 ?? 0) == 0 ? 'selected' : '' }}>
                        Mismo día (0)
                    </option>
                    <option value="1" {{ old('offset_dias_inicio2', $turno->offset_dias_inicio2 ?? 0) == 1 ? 'selected' : '' }}>
                        Día siguiente (+1)
                    </option>
                </select>
                @error('offset_dias_inicio2')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Offset fin 2 -->
            <div>
                <label for="offset_dias_fin2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Offset día de fin
                </label>
                <select name="offset_dias_fin2" id="offset_dias_fin2"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="-1" {{ old('offset_dias_fin2', $turno->offset_dias_fin2 ?? 0) == -1 ? 'selected' : '' }}>
                        Día anterior (-1)
                    </option>
                    <option value="0" {{ old('offset_dias_fin2', $turno->offset_dias_fin2 ?? 0) == 0 ? 'selected' : '' }}>
                        Mismo día (0)
                    </option>
                    <option value="1" {{ old('offset_dias_fin2', $turno->offset_dias_fin2 ?? 0) == 1 ? 'selected' : '' }}>
                        Día siguiente (+1)
                    </option>
                </select>
                @error('offset_dias_fin2')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Orden -->
    <div>
        <label for="orden" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden de visualización</label>
        <input type="number" name="orden" id="orden" min="0"
            value="{{ old('orden', $turno->orden ?? 999) }}"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="1, 2, 3...">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Números más bajos aparecen primero</p>
        @error('orden')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Colores -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Color de fondo -->
        <div>
            <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color de fondo</label>
            <div class="flex gap-2 mt-1">
                <input type="color" name="color" id="color"
                    value="{{ old('color', $turno->color ?? '#3b82f6') }}"
                    class="h-10 w-20 rounded border-gray-300 dark:border-gray-600 cursor-pointer">
                <input type="text" id="color_input"
                    value="{{ old('color', $turno->color ?? '#3b82f6') }}"
                    class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="#3b82f6" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
            </div>
            @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Color de texto -->
        <div>
            <label for="color_texto" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color de texto</label>
            <div class="flex gap-2 mt-1">
                <input type="color" name="color_texto" id="color_texto"
                    value="{{ old('color_texto', $turno->color_texto ?? '#ffffff') }}"
                    class="h-10 w-20 rounded border-gray-300 dark:border-gray-600 cursor-pointer">
                <input type="text" id="color_texto_input"
                    value="{{ old('color_texto', $turno->color_texto ?? '#ffffff') }}"
                    class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="#ffffff" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
            </div>
            @error('color_texto')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Vista previa -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vista previa</label>
        <div id="color_preview" class="inline-block px-4 py-2 rounded-md font-medium text-sm"
            style="background-color: {{ old('color', $turno->color ?? '#3b82f6') }}; color: {{ old('color_texto', $turno->color_texto ?? '#ffffff') }};">
            {{ old('nombre', $turno->nombre ?? 'Nombre del turno') }}
        </div>
    </div>

    <!-- Estado activo -->
    <div class="flex items-center">
        <input type="checkbox" name="activo" id="activo" value="1"
            {{ old('activo', $turno->activo ?? true) ? 'checked' : '' }}
            class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-blue-600 focus:ring-blue-500">
        <label for="activo" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
            Turno activo (solo turnos activos se usan en el calendario)
        </label>
    </div>
</div>

<!-- Script para sincronizar color picker y toggle turno partido -->
<script>
    function toggleSegundo() {
        const checkbox = document.getElementById('es_partido');
        const segundoSegmento = document.getElementById('segundo_segmento');
        if (checkbox && segundoSegmento) {
            segundoSegmento.style.display = checkbox.checked ? 'block' : 'none';
        }
    }

    function updatePreview() {
        const preview = document.getElementById('color_preview');
        const colorPicker = document.getElementById('color');
        const colorTextoPicker = document.getElementById('color_texto');
        const nombreInput = document.getElementById('nombre');

        if (preview && colorPicker && colorTextoPicker) {
            preview.style.backgroundColor = colorPicker.value;
            preview.style.color = colorTextoPicker.value;
        }
        if (preview && nombreInput) {
            preview.textContent = nombreInput.value || 'Nombre del turno';
        }
    }

    function syncColorInputs(pickerId, textId) {
        const picker = document.getElementById(pickerId);
        const text = document.getElementById(textId);

        if (picker && text) {
            picker.addEventListener('input', function() {
                text.value = this.value;
                updatePreview();
            });

            text.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    picker.value = this.value;
                    updatePreview();
                }
            });
        }
    }

    function initTurnoForm() {
        // Mostrar/ocultar segundo segmento al cargar
        toggleSegundo();

        // Sincronizar color de fondo
        syncColorInputs('color', 'color_input');

        // Sincronizar color de texto
        syncColorInputs('color_texto', 'color_texto_input');

        // Actualizar preview cuando cambia el nombre
        const nombreInput = document.getElementById('nombre');
        if (nombreInput) {
            nombreInput.addEventListener('input', updatePreview);
        }

        // Actualizar preview inicial
        updatePreview();
    }

    // Ejecutar en carga inicial
    document.addEventListener('DOMContentLoaded', initTurnoForm);

    // Ejecutar con navegación Livewire (wire:navigate)
    document.addEventListener('livewire:navigated', initTurnoForm);

    // Ejecutar inmediatamente si el DOM ya está cargado
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initTurnoForm, 0);
    }
</script>
