<x-app-layout>
    <x-slot name="title">Nómina</x-slot>

    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('empresas.index') }}" wire:navigate class="text-blue-600">
                {{ __('Empresa Información') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('nominas.index') }}" wire:navigate class="text-blue-600">
                {{ __('Tabla Nóminas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Nómina de ') }} <a href="{{ route('users.show', $nomina->empleado_id ?? '#') }}" wire:navigate
                class="text-blue-500 hover:underline">
                {{ $nomina->empleado->name }}
            </a>
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6" style="max-width: 794px;">
        <span>NIF: </span><span> {{ $nomina->empleado->dni ?? 'N/A' }}</span>
        <div class="bg-white border-1 border-gray-800 shadow-md overflow-hidden">

            <table class="table-fixed w-full text-sm text-left border-collapse">

                <!-- 3) Cabecera Empresa -->
                <tr class="border border-gray-400">
                    <td class="p-0">
                        <!-- Encabezado -->
                        <div
                            class="grid grid-cols-12 bg-gray-300 text-xs font-bold uppercase text-center text-black border-b border-gray-400">
                            <div class="p-2 border-r border-gray-400 col-span-3">Empresa</div>
                            <div class="p-2 border-r border-gray-400 col-span-6">Domicilio</div>
                            <div class="p-2 col-span-3">Nº Ins. S.S.</div>
                        </div>

                        <!-- Contenido (editable si es necesario) -->
                        <div class="grid grid-cols-12 text-xs text-gray-800 text-center">
                            <!-- Nombre de la empresa -->
                            <div class="p-2 border-r border-gray-400 col-span-3">
                                {{ $nomina->empleado->empresa->nombre ?? 'N/A' }}
                            </div>

                            <!-- Dirección -->
                            <div class="p-2 border-r border-gray-400 col-span-6">
                                {{ $nomina->empleado->empresa->direccion ?? 'N/A' }}
                            </div>

                            <!-- Nº Inscripción Seguridad Social -->
                            <div class="p-2 col-span-3">
                                {{ $nomina->empleado->empresa->numero_ss ?? 'N/A' }}
                            </div>
                        </div>
                    </td>
                </tr>
                <!-- 4) Cabecera Trabajador -->
                <tr class="border border-gray-400">
                    <td class="p-0">
                        <!-- Encabezado -->
                        <div
                            class="grid grid-cols-12 bg-gray-300 text-xs font-bold uppercase text-center text-black border-b border-gray-400">
                            <div class="p-2 border-r border-gray-400 col-span-3">Trabajador/a</div>
                            <div class="p-2 border-r border-gray-400 col-span-3">Categoría</div>
                            <div class="p-2 border-r border-gray-400 col-span-2">Nº Matric</div>
                            <div class="p-2 border-r border-gray-400 col-span-2">Antigüedad</div>
                            <div class="p-2 col-span-2">D.N.I.</div>
                        </div>

                        <!-- Contenido -->
                        <div class="grid grid-cols-12 text-xs text-gray-800 text-center">
                            <div class="p-2 border-r border-gray-400 col-span-3">
                                {{ $nomina->empleado->apellidos_nombre }}</div>
                            <div class="p-2 border-r border-gray-400 col-span-3">{{ $nomina->categoria->nombre }}</div>
                            <div class="p-2 border-r border-gray-400 col-span-2">{{ $nomina->empleado->id }}</div>
                            <div class="p-2 border-r border-gray-400 col-span-2">
                                {{ \Carbon\Carbon::parse($nomina->empleado->fecha_alta)->translatedFormat('d M Y') }}
                            </div>
                            <div class="p-2 col-span-2">{{ $nomina->empleado->dni }}</div>
                        </div>
                    </td>
                </tr>

                <!-- 5) Cabecera Afiliación -->
                <tr class="border border-gray-400">
                    <td class="p-0">
                        <div
                            class="grid grid-cols-7 bg-gray-300 text-xs font-bold uppercase text-center text-black border-b border-gray-400">
                            <div class="p-2 border-r border-gray-400">Nº Afiliación S.S.</div>
                            <div class="p-2 border-r border-gray-400">Tarifa</div>
                            <div class="p-2 border-r border-gray-400">Cod.CT</div>
                            <div class="p-2 border-r border-gray-400">Sección</div>
                            <div class="p-2 border-r border-gray-400">Nro.</div>
                            <div class="p-2 border-r border-gray-400">Periodo</div>
                            <div class="p-2">Tot. Días</div>
                        </div>

                        <div class="grid grid-cols-7 text-xs text-gray-800 text-center">
                            <div class="p-2 border-r border-gray-400">{{ $nomina->empleado->nss }}</div>
                            <div class="p-2 border-r border-gray-400">8</div>
                            <div class="p-2 border-r border-gray-400">100</div>
                            <div class="p-2 border-r border-gray-400">A</div>
                            <div class="p-2 border-r border-gray-400">{{ $nomina->empleado->id }}</div>
                            <div class="p-2 border-r border-gray-400">
                                MENS
                                {{ \Carbon\Carbon::parse($nomina->fecha)->startOfMonth()->translatedFormat('d M Y') }}
                                a
                                {{ \Carbon\Carbon::parse($nomina->fecha)->endOfMonth()->translatedFormat('d M Y') }}
                            </div>
                            <div class="p-2">{{ $nomina->dias_trabajados }}</div>
                        </div>
                    </td>
                </tr>

                <!-- 6) Tabla Principal (resumen devengos/deducciones) -->
                <tr class="border border-gray-400 align-top">
                    <td class="p-0" style="min-height: 620px;">
                        <table class="w-full text-xs text-center border-collapse min-h-[620px]">
                            <thead class="bg-gray-300 text-black font-bold uppercase">
                                <tr>
                                    <th class="p-2 border border-gray-400">Cuantía</th>
                                    <th class="p-2 border border-gray-400">Precio</th>
                                    <th class="p-2 border border-gray-400" colspan="2">Concepto</th>
                                    <th class="p-2 border border-gray-400">Devengos</th>
                                    <th class="p-2 border border-gray-400">Deducciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-800">
                                <tr>
                                    <td class="border border-gray-400 p-1">{{ $nomina->dias_trabajados }}</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->salario_base / $nomina->dias_trabajados, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Salario Base</td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->salario_base, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->plus_actividad, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Plus Actividad</td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->plus_actividad, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->plus_asistencia, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Plus Asistencia
                                    </td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->plus_asistencia, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->plus_transporte, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Plus Transporte
                                    </td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->plus_transporte, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->plus_dieta, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Plus Dieta</td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->plus_dieta, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->plus_turnicidad, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Plus Turnicidad
                                    </td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->plus_turnicidad, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->plus_productividad, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Plus
                                        Productividad</td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->plus_productividad, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>
                                {{-- … justo después del row de Plus Productividad … --}}
                                <tr>
                                    <td class="border border-gray-400 p-1">{{ $nomina->horas_extra }}</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->valor_hora_extra, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Horas Extra</td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->horas_extra * $nomina->valor_hora_extra, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>

                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1">
                                        {{ number_format($nomina->prorrateo, 3, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">*Prorrata Pagas
                                        Extras</td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->prorrateo, 2, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                </tr>


                                {{-- Deducciones ejemplo --}}
                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1"></td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">TRIBUTACIÓN
                                        I.R.P.F. {{ number_format($nomina->irpf_porcentaje, 2, ',', '.') }} %

                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->irpf_mensual, 2, ',', '.') }} </td>
                                </tr>
                                <tr>
                                    <td class="border border-gray-400 p-1">1</td>
                                    <td class="border border-gray-400 p-1"></td>
                                    <td class="border border-gray-400 p-1 text-left" colspan="2">Cotización S.S.
                                    </td>
                                    <td class="border border-gray-400 p-1"></td>
                                    <td class="border border-gray-400 p-1 text-right">
                                        {{ number_format($nomina->total_deducciones_ss, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!-- 7) Resumen morado -->
                <tr class="border border-gray-400">
                    <td class="p-0">
                        <div
                            class="grid grid-cols-7 bg-gray-100 text-xs font-bold text-center border-b border-gray-400">
                            <div class="p-2 border-r border-gray-400">Rem. Total</div>
                            <div class="p-2 border-r border-gray-400">P.P. Extras</div>
                            <div class="p-2 border-r border-gray-400">Base S.S.</div>
                            <div class="p-2 border-r border-gray-400">Base A.T. y Des.</div>
                            <div class="p-2 border-r border-gray-400">Base I.R.P.F.</div>
                            <div class="p-2 border-r border-gray-400">T. Devengado</div>
                            <div class="p-2">T. a Deducir</div>
                        </div>

                        <div class="grid grid-cols-7 text-xs text-gray-800 text-center">
                            <div class="p-2 border-r border-gray-400">
                                {{ number_format($nomina->total_devengado, 2, ',', '.') }}</div>
                            <div class="p-2 border-r border-gray-400">
                                {{ number_format($nomina->prorrateo, 2, ',', '.') }}</div>
                            <div class="p-2 border-r border-gray-400">
                                {{ number_format($nomina->total_devengado, 2, ',', '.') }}</div>
                            <div class="p-2 border-r border-gray-400">
                                {{ number_format($nomina->total_devengado, 2, ',', '.') }}</div>
                            <div class="p-2 border-r border-gray-400">
                                {{ number_format($nomina->base_irpf_previa, 2, ',', '.') }}</div>
                            <div class="p-2 border-r border-gray-400">
                                {{ number_format($nomina->total_devengado, 2, ',', '.') }}</div>
                            <div class="p-2">
                                {{ number_format($nomina->irpf_mensual + $nomina->total_deducciones_ss, 2, ',', '.') }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 8) Nota percepciones -->
        <tr class="bg-green-50 border-b border-gray-200">
            <td class="text-xs italic text-gray-700 px-4 py-2 text-center border-t border-gray-300 bg-white">
                * Percepciones salariales sujetas a cotización S.S. — Percepciones no salariales excluidas de
                cotización
            </td>
        </tr>
        <!-- BLOQUE RECIBI: FECHA / SELLO / RECIBÍ / LÍQUIDO -->
        <div
            class="bg-white border-1 border-gray-800 mt-6 p-4 grid grid-cols-4 gap-4 text-xs font-semibold text-gray-800">
            <!-- Fecha -->
            <div>
                <span class="block mb-2">FECHA</span>

            </div>
            <!-- Sello empresa -->
            <div class="block mb-2">
                SELLO EMPRESA
            </div>
            <!-- Recibí + Líquido a percibir -->
            <div class="block mb-2">
                <div class="mb-4 mt-2">RECIBÍ</div>
            </div>
            <div class="block mb-2">
                LÍQUIDO A PERCIBIR<br>
                <input type="text" class="w-28 text-right border-0 bg-lime-100 font-mono"
                    value="{{ number_format($nomina->liquido, 2, ',', '.') }}" readonly>
            </div>

        </div>
        <!-- Aportaciones empresariales -->
        <div class="col-span-4 mt-6 bg-white border-1 border-gray-800 bg-white">
            <div
                class="grid grid-cols-4 bg-gray-300 text-xs font-bold text-black text-center uppercase border-b border-gray-500">
                <div class="p-2 border-r border-gray-400">Concepto</div>
                <div class="p-2 border-r border-gray-400">Base</div>
                <div class="p-2 border-r border-gray-400">Tipo %</div>
                <div class="p-2">Aportación</div>
            </div>

            @foreach ($aportacionesEmpresa as $aportacion)
                <div class="grid grid-cols-4 text-xs text-gray-800 text-center border-b border-gray-300">
                    <div class="p-2 border-r border-gray-400">{{ $aportacion->tipo_aportacion }}</div>
                    <div class="p-2 border-r border-gray-400">
                        {{ number_format($nomina->total_devengado, 2, ',', '.') }}
                    </div>
                    <div class="p-2 border-r border-gray-400">
                        {{ number_format($aportacion->porcentaje, 2, ',', '.') }}%
                    </div>
                    <div class="p-2">
                        {{ number_format(($nomina->total_devengado * $aportacion->porcentaje) / 100, 2, ',', '.') }}
                    </div>
                </div>
            @endforeach

            <div class="grid grid-cols-4 text-xs text-right font-semibold text-black bg-yellow-50">
                <div class="col-span-3 p-2 border-t border-gray-400 border-r text-right">Total Aportación Empresa</div>
                <div class="p-2 border-t border-gray-400">
                    {{ number_format($nomina->coste_empresa - $nomina->total_devengado, 2, ',', '.') }}
                </div>
            </div>
        </div>


    </div>
</x-app-layout>
