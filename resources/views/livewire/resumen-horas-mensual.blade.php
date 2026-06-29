<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
    @php
        $fmtH = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    @endphp

    {{-- Cabecera con selector de mes/año --}}
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2">
            <div class="bg-indigo-100 dark:bg-indigo-900/50 rounded-lg p-1.5">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Histórico de horas por trabajador
            </h3>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <input type="text" wire:model.live.debounce.400ms="buscar" placeholder="Buscar trabajador..."
                class="w-44 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring focus:ring-blue-300 focus:border-blue-500">

            <button type="button" wire:click="mesAnterior" title="Mes anterior"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>

            <select wire:model.live="mes"
                class="border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring focus:ring-blue-300 focus:border-blue-500">
                @foreach ($meses as $num => $nombre)
                    <option value="{{ $num }}">{{ $nombre }}</option>
                @endforeach
            </select>

            <select wire:model.live="anio"
                class="border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 focus:ring focus:ring-blue-300 focus:border-blue-500">
                @foreach ($anios as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>

            <button type="button" wire:click="mesSiguiente" title="Mes siguiente"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto" wire:loading.class="opacity-50">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold">Trabajador</th>
                    <th class="px-4 py-2 text-right font-semibold">Horas</th>
                    <th class="px-4 py-2 text-center font-semibold">Días trab.</th>
                    <th class="px-4 py-2 text-center font-semibold">Sin fichaje</th>
                    <th class="px-4 py-2 text-center font-semibold">Vacac.</th>
                    <th class="px-4 py-2 text-center font-semibold">Baja</th>
                    <th class="px-4 py-2 text-center font-semibold">Justif.</th>
                    <th class="px-4 py-2 text-center font-semibold">Injustif.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($filas as $f)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <td class="px-4 py-2">
                            <a href="{{ route('users.show', $f['id']) }}" class="flex items-center gap-2 group">
                                @if ($f['foto'])
                                    <img src="{{ $f['foto'] }}" alt="" class="w-7 h-7 rounded-full object-cover border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                @else
                                    <span class="w-7 h-7 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 flex-shrink-0">{{ strtoupper(substr($f['nombre'], 0, 1)) }}</span>
                                @endif
                                <span class="leading-tight">
                                    <span class="block text-blue-700 dark:text-blue-400 group-hover:underline font-medium">{{ $f['nombre'] }}</span>
                                    @if ($f['categoria'])
                                        <span class="block text-[10px] text-gray-500 dark:text-gray-400">{{ $f['categoria'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ $fmtH($f['horas']) }} h</td>
                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $f['dias_trabajados'] }}</td>
                        <td class="px-4 py-2 text-center">
                            @if ($f['dias_incompletos'] > 0)
                                <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $f['dias_incompletos'] }}</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $f['vacaciones'] ?: '—' }}</td>
                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $f['baja'] ?: '—' }}</td>
                        <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $f['justificada'] ?: '—' }}</td>
                        <td class="px-4 py-2 text-center">
                            @if ($f['injustificada'] > 0)
                                <span class="text-red-600 dark:text-red-400 font-medium">{{ $f['injustificada'] }}</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400 italic">
                            No hay trabajadores que mostrar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($filas->isNotEmpty())
                <tfoot class="bg-gray-50 dark:bg-gray-900/40 font-semibold text-gray-800 dark:text-gray-100">
                    <tr>
                        <td class="px-4 py-2 text-right">Total</td>
                        <td class="px-4 py-2 text-right">{{ $fmtH($totalHoras) }} h</td>
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
