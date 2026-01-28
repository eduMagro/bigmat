<div>
        {{-- Justificantes existentes --}}
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Justificantes subidos (ultimos 90 dias)
            </h4>
            @if (count($justificantesExistentes) > 0)
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach ($justificantesExistentes as $justificante)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $justificante['fecha_formateada'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $justificante['horas_justificadas'] }}h
                                    @if ($justificante['observaciones'])
                                        - {{ Str::limit($justificante['observaciones'], 30) }}
                                    @endif
                                </p>
                            </div>
                            <a href="{{ asset('storage/' . $justificante['ruta']) }}"
                               target="_blank"
                               class="ml-2 p-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg transition"
                               title="Ver justificante">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 rounded-lg border border-dashed border-gray-200 dark:border-gray-600">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-sm">No hay justificantes subidos</p>
                </div>
            @endif
        </div>
        @if (!$soloLectura)
            <hr class="my-4 border-gray-200 dark:border-gray-600">
        @endif

        {{-- Solo mostrar formulario si NO es solo lectura --}}
        @if (!$soloLectura)
            {{-- Mensaje de error --}}
            @if ($error)
                <div class="mb-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ $error }}</p>
                    </div>
                </div>
            @endif

            {{-- Formulario de subida --}}
            <form wire:submit.prevent="guardarJustificante">
                {{-- Selector de archivo --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Archivo del justificante <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="file"
                            wire:model="archivo"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-lg file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-300
                                hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                                cursor-pointer border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg
                                focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if($procesando) disabled @endif>

                        {{-- Indicador de carga --}}
                        <div wire:loading wire:target="archivo" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center rounded-lg">
                            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Procesando OCR...</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Formatos aceptados: PDF, JPG, PNG. Máximo 10MB.</p>
                    @error('archivo') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Resultados del OCR --}}
                @if ($mostrarResultados)
                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Fecha detectada
                        </h4>

                        @if ($fechaDetectada)
                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                <span class="font-medium">Fecha detectada:</span>
                                {{ \Carbon\Carbon::parse($fechaDetectada)->format('d/m/Y') }}
                                @if ($asignacionSeleccionada)
                                    <span class="text-green-600 dark:text-green-400 ml-2">✓ Asignación seleccionada</span>
                                @endif
                            </p>
                        @endif

                        @if ($textoExtraido)
                            <details class="mt-2">
                                <summary class="text-xs text-blue-600 dark:text-blue-400 cursor-pointer hover:text-blue-800 dark:hover:text-blue-300">Ver texto extraído</summary>
                                <pre class="mt-2 p-2 bg-white dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-300 overflow-auto max-h-32 border dark:border-gray-600">{{ $textoExtraido }}</pre>
                            </details>
                        @endif
                    </div>
                @endif

                {{-- Selector de asignación o fecha manual --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        @if (count($asignacionesDisponibles) > 0)
                            Asignación a justificar <span class="text-red-500">*</span>
                            @if ($fechaDetectada && $asignacionSeleccionada)
                                <span class="ml-2 text-xs text-green-600 dark:text-green-400 font-normal">(detectada automáticamente)</span>
                            @endif
                        @else
                            Fecha a justificar <span class="text-red-500">*</span>
                        @endif
                    </label>

                    @if (count($asignacionesDisponibles) > 0)
                        <select wire:model.live="asignacionSeleccionada"
                            class="w-full rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-700 transition text-sm dark:bg-gray-700 dark:text-gray-200
                                {{ $fechaDetectada && $asignacionSeleccionada ? 'border-green-400 bg-green-50 dark:bg-green-900/30 dark:border-green-600' : 'border-gray-300 dark:border-gray-600' }}">
                            <option value="">Selecciona una asignación...</option>
                            @foreach ($asignacionesDisponibles as $asignacion)
                                <option value="{{ $asignacion['id'] }}">
                                    {{ $asignacion['fecha_formateada'] }} - {{ $asignacion['turno'] }} - {{ $asignacion['obra'] }}
                                    @if ($asignacion['estado']) ({{ ucfirst($asignacion['estado']) }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('asignacionSeleccionada') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    @else
                        <input type="date"
                            wire:model.live="fechaManual"
                            max="{{ date('Y-m-d') }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-700 transition text-sm">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Selecciona la fecha del día que quieres justificar.</p>
                        @error('fechaManual') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    @endif
                </div>

                {{-- Horas justificadas --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Horas justificadas <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                        wire:model.live="horasDetectadas"
                        step="0.5"
                        min="0"
                        max="24"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-700 transition text-sm"
                        placeholder="Introduce las horas...">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Indica el número de horas que cubre el justificante (ej: 1.5, 2, 4, 8).</p>
                    @error('horasDetectadas') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Observaciones --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Observaciones (opcional)
                    </label>
                    <textarea wire:model="observaciones"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-700 transition text-sm"
                        placeholder="Añade cualquier observación relevante..."></textarea>
                </div>

                {{-- Botones --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit"
                        class="flex-1 inline-flex justify-center items-center gap-2 rounded-lg px-4 py-2.5 font-semibold text-white shadow-md
                            bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                            disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                        wire:loading.attr="disabled"
                        @if(!$archivo || (!$asignacionSeleccionada && !$fechaManual)) disabled @endif>
                        <svg wire:loading.remove wire:target="guardarJustificante" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg wire:loading wire:target="guardarJustificante" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="guardarJustificante">Guardar Justificante</span>
                        <span wire:loading wire:target="guardarJustificante">Guardando...</span>
                    </button>

                    @if ($mostrarResultados)
                        <button type="button"
                            wire:click="cancelar"
                            class="inline-flex justify-center items-center gap-2 rounded-lg px-4 py-2.5 font-semibold text-gray-700 dark:text-gray-300
                                bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600
                                focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
                                transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    @endif
                </div>
            </form>

        {{-- Info sobre requisitos --}}
        <div class="mt-4 flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400 border-t dark:border-gray-600 pt-4">
            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            @if ($ocrDisponible)
                <p>
                    El sistema detectará automáticamente la fecha del justificante.
                    Las horas justificadas debes introducirlas manualmente.
                </p>
            @else
                <p>
                    Sube el documento, selecciona la fecha correspondiente e introduce las horas justificadas.
                </p>
            @endif
        </div>
        @endif {{-- fin !$soloLectura --}}
</div>
