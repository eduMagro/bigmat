<x-app-layout>
    <x-slot name="title">Documentos</x-slot>

    <x-page-header
        title="Documentos Enviados"
        subtitle="Control de documentos PDF adjuntos en mensajes"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
    />

    <div class="w-full px-2 sm:px-4 md:px-6 py-4">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border min-h-0 h-fit border-blue-200 bg-white p-4 shadow-sm dark:border-blue-900/40 dark:bg-gray-900 overflow-auto mb-4">
            <form id="documentos-alertas-upload-form" method="POST" action="{{ route('documentos-alertas.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 lg:grid-cols-2 h-full">
                @csrf
                <input type="hidden" name="destino_tipo" value="usuario">
                <input type="hidden" name="rol" value="">
                <input type="hidden" name="categoria_id" value="">
                <input type="hidden" name="destinatario_id" value="">

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/40 h-full">
                    <input id="adjuntos-input-hidden" type="file" accept="application/pdf" multiple class="hidden">
                    <div id="dropzone-main" class="relative flex min-h-[18rem] h-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-sky-500 bg-white px-4 py-6 text-center dark:bg-slate-900/40">
                        <span id="preview-empty-state" class="space-y-2">
                            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Arrastra PDF aquí o haz click</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">Estilo WhatsApp: preview grande + miniaturas (máx. 5)</span>
                        </span>
                        <div id="preview-main" class="hidden absolute inset-0 h-full w-full bg-slate-100 dark:bg-slate-900">
                            <iframe id="preview-main-pdf" title="Preview PDF" class="h-full w-full"></iframe>
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-3 text-left">
                                <p id="preview-main-name" class="truncate text-sm font-semibold text-white"></p>
                                <p id="preview-main-size" class="mt-1 text-xs text-slate-200"></p>
                            </div>
                        </div>
                        <div id="dropzone-drag-overlay" class="pointer-events-none absolute inset-0 z-30 hidden items-center justify-center bg-slate-900/35 backdrop-blur-sm">
                            <div class="flex flex-col items-center gap-3 rounded-xl border border-white/30 bg-white/20 px-6 py-5 text-white shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-up-icon lucide-file-up">
                                    <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                                    <path d="M14 2v5a1 1 0 0 0 1 1h5"/>
                                    <path d="M12 12v6"/>
                                    <path d="m15 15-3-3-3 3"/>
                                </svg>
                                <span class="text-sm font-semibold">Soltar archivos para subir</span>
                            </div>
                        </div>
                    </div>
                    <input id="adjuntos-transfer-input" type="file" name="adjuntos[]" multiple class="hidden" required>
                </div>

                <div class="rounded-2xl shadow-lg bg-gradient-to-br from-slate-50 via-white to-slate-100 p-4 dark:from-slate-900/70 dark:via-slate-900/60 dark:to-slate-800/70">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold tracking-tight text-slate-800 dark:text-slate-100">Destinatarios</p>
                            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Selecciona roles/categorías para añadir destinatarios automáticos.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                            Panel inteligente
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="rounded-xl bg-white/90 p-3 ring-1 ring-slate-200/70 dark:bg-slate-900/50 dark:ring-slate-700/70">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Roles</p>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500">Selección masiva</span>
                            </div>
                            <div id="roles-list" class="space-y-2 rounded-lg p-2 max-h-32 overflow-auto">
                                @foreach($rolesDisponibles as $rol)
                                    <label class="flex items-center cursor-pointer hover:bg-slate-50 gap-2 rounded-md bg-white px-2 py-1 text-xs text-slate-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900/70 dark:text-slate-200 dark:ring-slate-700">
                                        <input type="checkbox" class="role-checkbox rounded border-slate-300 text-sky-600 focus:ring-sky-500" value="{{ $rol }}">
                                        <span class="truncate">{{ ucfirst($rol) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-xl bg-white/90 p-3 ring-1 ring-slate-200/70 dark:bg-slate-900/50 dark:ring-slate-700/70">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Categorías</p>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500">Auto-marcado</span>
                            </div>
                            <div id="categorias-list" class="space-y-2 rounded-lg p-2 max-h-32 overflow-auto">
                                @foreach($categoriasDisponibles as $cat)
                                    <label class="flex items-center cursor-pointer hover:bg-slate-50 gap-2 rounded-md bg-white px-2 py-1 text-xs text-slate-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900/70 dark:text-slate-200 dark:ring-slate-700">
                                        <input type="checkbox" class="categoria-checkbox rounded border-slate-300 text-sky-600 focus:ring-sky-500" value="{{ $cat->id }}">
                                        <span class="truncate">{{ $cat->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl bg-white/90 p-3 ring-1 ring-slate-200/70 dark:bg-slate-900/50 dark:ring-slate-700/70">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Buscar usuario</label>
                        <div class="relative">
                            <input id="usuario-search-input" type="text" placeholder="Escribe nombre, rol o categoría..."
                                class="w-full rounded-lg border-0 bg-slate-100 px-3 py-2 text-xs shadow-inner ring-1 ring-slate-200 focus:ring-2 focus:ring-sky-400 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700">
                            <div id="usuario-search-results" class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-auto rounded-lg bg-white p-1 shadow-xl ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700"></div>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl bg-white/90 p-3 ring-1 ring-slate-200/70 dark:bg-slate-900/50 dark:ring-slate-700/70">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Destinatarios seleccionados</p>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">Puedes quitar usuarios individuales</span>
                        </div>
                        <div id="usuarios-receptores" class="min-h-[7rem] max-h-[7rem] overflow-auto rounded-lg bg-slate-50 p-2 ring-1 ring-slate-200/70 dark:bg-slate-900/40 dark:ring-slate-700/70"></div>
                    </div>

                    <div id="user-ids-hidden-container"></div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Mensaje (opcional)</label>
                        <input type="text" name="mensaje" maxlength="2000" placeholder="Contexto del documento..."
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    </div>

                    <div class="mt-2 flex items-center gap-2">
                        <input id="requiere-firma-subida" type="checkbox" name="requiere_firma" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="requiere-firma-subida" class="text-xs text-gray-700 dark:text-gray-200">Requiere firma</label>
                    </div>
                </div>

                <div id="archivos_subidos" class="flex gap-2 flex-wrap"></div>

                <div class="lg:col-span-1 flex justify-end">
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                        Subir y enviar
                    </button>
                </div>
            </form>
        </div>

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Tabla Desktop -->
        <div class="w-full overflow-x-auto bg-white dark:bg-gray-900 shadow-lg rounded-lg">
            <table class="table-global w-full border border-gray-300 dark:border-gray-700 rounded-lg">
                <x-tabla.header>
                    <x-tabla.header-row>
                        @php
                            $sortUrl = fn($campo) => request()->fullUrlWithQuery([
                                'sort' => $campo,
                                'order' => ($sort === $campo && $order === 'asc') ? 'desc' : 'asc',
                            ]);
                            $sortIcon = function($campo) use ($sort, $order) {
                                if ($sort !== $campo) return '<span class="text-blue-300 opacity-40">&#9650;</span><span class="text-blue-300 opacity-40">&#9660;</span>';
                                return $order === 'asc'
                                    ? '<span class="text-white">&#9650;</span><span class="text-blue-300 opacity-50">&#9660;</span>'
                                    : '<span class="text-blue-300 opacity-50">&#9650;</span><span class="text-white">&#9660;</span>';
                            };
                        @endphp

                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 transition-colors select-none"
                            onclick="window.location='{{ $sortUrl('id') }}'">
                            <div class="flex items-center justify-center gap-1">
                                <span>ID</span>
                                <span class="inline-flex flex-col text-xs leading-none">{!! $sortIcon('id') !!}</span>
                            </div>
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 transition-colors select-none"
                            onclick="window.location='{{ $sortUrl('nombre_original') }}'">
                            <div class="flex items-center justify-center gap-1">
                                <span>Documento</span>
                                <span class="inline-flex flex-col text-xs leading-none">{!! $sortIcon('nombre_original') !!}</span>
                            </div>
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600">Emisor</th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600">Destino</th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 transition-colors select-none"
                            onclick="window.location='{{ $sortUrl('requiere_firma') }}'">
                            <div class="flex items-center justify-center gap-1">
                                <span>Req. Firma</span>
                                <span class="inline-flex flex-col text-xs leading-none">{!! $sortIcon('requiere_firma') !!}</span>
                            </div>
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600">Firmas</th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 transition-colors select-none"
                            onclick="window.location='{{ $sortUrl('created_at') }}'">
                            <div class="flex items-center justify-center gap-1">
                                <span>Fecha</span>
                                <span class="inline-flex flex-col text-xs leading-none">{!! $sortIcon('created_at') !!}</span>
                            </div>
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600">Acciones</th>
                    </x-tabla.header-row>

                    {{-- Fila de filtros --}}
                    <x-tabla.filtro-row>
                        <th class="p-1 border dark:border-gray-700">
                            {{-- ID sin filtro --}}
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <form method="GET" class="flex gap-1">
                                @foreach(request()->except(['nombre', 'tipo', 'page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <input type="text" name="nombre" value="{{ request('nombre') }}"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                    placeholder="Buscar nombre..."
                                    onchange="this.form.submit()">
                            </form>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <form method="GET">
                                @foreach(request()->except(['emisor', 'page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <input type="text" name="emisor" value="{{ request('emisor') }}"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                    placeholder="Emisor..."
                                    onchange="this.form.submit()">
                            </form>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <form method="GET">
                                @foreach(request()->except(['destino', 'page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <input type="text" name="destino" value="{{ request('destino') }}"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                    placeholder="Destino..."
                                    onchange="this.form.submit()">
                            </form>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <form method="GET">
                                @foreach(request()->except(['requiere_firma', 'page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <select name="requiere_firma"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                    onchange="this.form.submit()">
                                    <option value="">Todos</option>
                                    <option value="1" @selected(request('requiere_firma') === '1')>Si</option>
                                    <option value="0" @selected(request('requiere_firma') === '0')>No</option>
                                </select>
                            </form>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            {{-- Sin filtro --}}
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <form method="GET">
                                @foreach(request()->except(['fecha', 'page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <input type="date" name="fecha" value="{{ request('fecha') }}"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                    onchange="this.form.submit()">
                            </form>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            @if(request()->hasAny(['nombre', 'tipo', 'requiere_firma', 'emisor', 'destino', 'fecha']))
                                <a href="{{ route('documentos-alertas.index') }}"
                                    class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 font-medium">
                                    Limpiar
                                </a>
                            @endif
                        </th>
                    </x-tabla.filtro-row>
                </x-tabla.header>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($documentos as $doc)
                        @php
                            $emisor = $doc->alerta?->usuario1;
                            $nombreEmisor = $emisor ? ($emisor->nombre_completo ?? $emisor->name) : 'Sistema';
                            $destino = $doc->alerta?->destino ?? $doc->alerta?->destinatario ?? 'Individual';
                            $totalFirmas = $doc->firmas->count();
                            $firmadasCount = $doc->firmas->where('firmado', true)->count();
                            $usuariosSinFirmaAsignada = $doc->usuariosSinFirmaAsignada ?? collect();
                            $faltantesCount = $usuariosSinFirmaAsignada->count();
                        @endphp
                        <x-tabla.row :clickable="false">
                            <td class="p-2 border dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
                                {{ $doc->id }}
                            </td>
                            <td class="p-2 border dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="truncate max-w-[200px] dark:text-gray-200" title="{{ $doc->nombre_original }}">
                                        {{ $doc->nombre_original }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-2 border dark:border-gray-700 text-center dark:text-gray-300">
                                {{ $nombreEmisor }}
                            </td>
                            <td class="p-2 border dark:border-gray-700 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $destino === 'todos' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ ucfirst($destino) }}
                                </span>
                            </td>
                            <td class="p-2 border dark:border-gray-700 text-center">
                                @if($doc->requiere_firma)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        Si
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">No</span>
                                @endif
                            </td>
                            <td class="p-2 border dark:border-gray-700 text-center">
                                @if($doc->requiere_firma && $totalFirmas > 0)
                                    <span class="text-xs font-medium {{ $firmadasCount === $totalFirmas ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $firmadasCount }}/{{ $totalFirmas }}
                                    </span>
                                    @if($firmadasCount === $totalFirmas)
                                        <svg class="w-4 h-4 text-green-500 inline ml-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                @endif
                            </td>
                            <td class="p-2 border dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
                                {{ $doc->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-2 border dark:border-gray-700 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('alertas.adjuntos.ver', $doc->id) }}" target="_blank"
                                        class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                        title="Ver PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('alertas.adjuntos.descargar', $doc->id) }}"
                                        class="p-1.5 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                                        title="Descargar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                    @if($doc->requiere_firma && $totalFirmas > 0)
                                        <button type="button"
                                            onclick="verFirmas({{ $doc->id }})"
                                            class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                            title="Ver firmas">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        </button>
                                    @endif
                                    @if($doc->requiere_firma && $faltantesCount > 0)
                                        <button type="button"
                                            onclick="verFirmas({{ $doc->id }})"
                                            class="p-1.5 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                            title="Usuarios nuevos sin asignar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M5.062 19h13.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.33 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </x-tabla.row>

                        {{-- Fila oculta con detalle de firmas --}}
                        @if($doc->requiere_firma && $totalFirmas > 0)
                            <tr id="firmas-{{ $doc->id }}" class="hidden">
                                <td colspan="8" class="p-0 border dark:border-gray-700">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase mb-2">Estado de firmas</h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                            @foreach($doc->firmas as $firma)
                                                <div class="flex flex-col gap-2 p-2 rounded-lg text-xs
                                                    {{ $firma->firmado ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600' }}">
                                                    <div class="flex items-center gap-2">
                                                        @if($firma->firmado)
                                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @else
                                                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                        <div class="min-w-0">
                                                            <span class="block truncate font-medium {{ $firma->firmado ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                                                                {{ $firma->user?->nombre_completo ?? $firma->user?->name ?? 'Usuario' }}
                                                            </span>
                                                            @if($firma->firmado && $firma->firmado_dia)
                                                                <span class="block text-[10px] text-green-600 dark:text-green-400">
                                                                    {{ $firma->firmado_dia->format('d/m/Y H:i') }}
                                                                </span>
                                                            @else
                                                                <span class="block text-[10px] text-gray-400 dark:text-gray-500">Pendiente</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if($firma->firmado && $firma->firma_ruta)
                                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-1">
                                                            <img src="/alertas/firma/{{ basename($firma->firma_ruta) }}"
                                                                alt="Firma de {{ $firma->user?->nombre_completo ?? 'Usuario' }}"
                                                                class="h-12 w-auto mx-auto object-contain">
                                                        </div>
                                                    @endif
                                                    @if($firma->firmado)
                                                        <form method="POST" action="{{ route('documentos-alertas.firmas.revocar', $firma->id) }}"
                                                            onsubmit="return confirm('Revocar firma y pedir firma de nuevo a este usuario?');">
                                                            @csrf
                                                            <button type="submit"
                                                                class="w-full rounded border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/30">
                                                                Revocar firma
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($faltantesCount > 0)
                                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/40 dark:bg-amber-900/20">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                                                        Usuarios nuevos sin solicitud de firma: {{ $faltantesCount }}
                                                    </p>
                                                    <form method="POST" action="{{ route('documentos-alertas.asignar-faltantes', $doc->id) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-700">
                                                            Enviar a todos los faltantes
                                                        </button>
                                                    </form>
                                                </div>

                                                <form method="POST" action="{{ route('documentos-alertas.asignar-faltantes', $doc->id) }}" class="mt-3">
                                                    @csrf
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                                        @foreach($usuariosSinFirmaAsignada as $usrFaltante)
                                                            <label class="flex items-center gap-2 rounded border border-amber-200 bg-white px-2 py-1.5 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-gray-900 dark:text-amber-200">
                                                                <input type="checkbox" name="user_ids[]" value="{{ $usrFaltante->id }}" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                                                <span class="truncate">{{ $usrFaltante->nombre_completo ?? $usrFaltante->name }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <div class="mt-2 flex justify-end">
                                                        <button type="submit" class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">
                                                            Enviar solo seleccionados
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                No se encontraron documentos
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabla.paginacion :paginador="$documentos" />
    </div>

    @php
        $usuariosPayload = $usuariosActivos->map(function ($u) {
            return [
                'id' => $u->id,
                'nombre' => $u->nombre_completo ?? $u->name,
                'rol' => $u->rol,
                'categoria_id' => $u->categoria_id,
                'categoria' => optional($u->categoria)->nombre,
                'foto' => $u->ruta_imagen ?? null,
            ];
        })->values()->all();
    @endphp

    <script>
        const usuariosPayload = @json($usuariosPayload);
        const fallbackUserSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-icon lucide-user-round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>`;

        function initUploadWhatsAppLike() {
            const MAX_FILES = 5;
            const files = [];
            let currentPreviewUrl = null;

            const dropzone = document.getElementById('dropzone-main');
            const sourceInput = document.getElementById('adjuntos-input-hidden');
            const realInput = document.getElementById('adjuntos-transfer-input');
            const previewMain = document.getElementById('preview-main');
            const previewEmpty = document.getElementById('preview-empty-state');
            const previewMainName = document.getElementById('preview-main-name');
            const previewMainSize = document.getElementById('preview-main-size');
            const previewMainPdf = document.getElementById('preview-main-pdf');
            const previewThumbs = document.getElementById('archivos_subidos');
            const dragOverlay = document.getElementById('dropzone-drag-overlay');
            let dragDepth = 0;
            let globalDragDepth = 0;

            if (!dropzone || !sourceInput || !realInput || !previewMain || !previewMainPdf || !previewThumbs) return;

            function formatBytes(bytes) {
                if (!bytes && bytes !== 0) return '-';
                const units = ['B', 'KB', 'MB', 'GB'];
                let size = bytes;
                let i = 0;
                while (size >= 1024 && i < units.length - 1) {
                    size /= 1024;
                    i++;
                }
                return `${size.toFixed(1)} ${units[i]}`;
            }

            function showSimpleModal(message) {
                let modal = document.getElementById('custom-simple-modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'custom-simple-modal';
                    modal.className = 'fixed inset-0 z-[120] hidden items-center justify-center bg-black/50 p-4';
                    modal.innerHTML = `
                        <div class="w-full max-w-sm rounded-xl bg-white p-4 dark:bg-gray-900">
                            <p id="custom-simple-modal-text" class="text-sm text-gray-800 dark:text-gray-100"></p>
                            <div class="mt-3 flex justify-end">
                                <button type="button" id="custom-simple-modal-ok" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white">Aceptar</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    modal.querySelector('#custom-simple-modal-ok')?.addEventListener('click', () => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    });
                }
                const text = modal.querySelector('#custom-simple-modal-text');
                if (text) text.textContent = message;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function syncRealInput() {
                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                realInput.files = dt.files;
            }

            function refreshDropzoneBorderStyle() {
                if (files.length > 0) {
                    dropzone.classList.remove('border-dashed');
                    dropzone.classList.add('border-solid');
                } else {
                    dropzone.classList.remove('border-solid');
                    dropzone.classList.add('border-dashed');
                }
            }

            function showDragOverlay() {
                dropzone.classList.add('border-sky-600');
                if (dragOverlay) {
                    dragOverlay.classList.remove('hidden');
                    dragOverlay.classList.add('flex');
                    dragOverlay.classList.remove('pointer-events-none');
                    dragOverlay.classList.add('pointer-events-auto');
                }
            }

            function hideDragOverlay() {
                dropzone.classList.remove('border-sky-600');
                if (dragOverlay) {
                    dragOverlay.classList.add('hidden');
                    dragOverlay.classList.remove('flex');
                    dragOverlay.classList.remove('pointer-events-auto');
                    dragOverlay.classList.add('pointer-events-none');
                }
            }

            function isInsideDropzone(x, y) {
                const r = dropzone.getBoundingClientRect();
                return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
            }

            function renderThumbs() {
                previewThumbs.innerHTML = '';
                files.forEach((f, idx) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'h-12 w-full max-w-20 z-[1000000] rounded-lg border border-slate-300 bg-white p-1 text-left text-[10px] hover:border-sky-400 dark:border-slate-700 dark:bg-slate-900';
                    b.innerHTML = `<span class="block truncate font-semibold">${f.name}</span><span class="block text-slate-500">${formatBytes(f.size)}</span>`;
                    b.addEventListener('click', () => setMainPreview(idx));
                    previewThumbs.appendChild(b);
                });

                if (files.length < MAX_FILES) {
                    const add = document.createElement('button');
                    add.type = 'button';
                    add.className = 'h-12 w-full max-w-16 z-[1000000] cursor-pointer rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-lg font-bold text-slate-500 hover:border-sky-400 dark:border-slate-700 dark:bg-slate-800 flex items-center justify-center';
                    add.textContent = '+';
                    add.addEventListener('click', () => sourceInput.click());
                    previewThumbs.appendChild(add);
                }
            }

            function setMainPreview(idx) {
                const file = files[idx];
                if (!file) return;
                previewMainName.textContent = file.name;
                previewMainSize.textContent = formatBytes(file.size);
                if (currentPreviewUrl) {
                    URL.revokeObjectURL(currentPreviewUrl);
                }
                const url = URL.createObjectURL(file);
                currentPreviewUrl = url;
                previewMainPdf.src = `${url}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;
                previewMain.dataset.index = String(idx);
                previewMain.classList.remove('hidden');
                previewEmpty.classList.add('hidden');
            }

            function addFiles(fileList) {
                const incoming = Array.from(fileList || []);
                for (const file of incoming) {
                    if (file.type !== 'application/pdf') continue;
                    const alreadyExists = files.some((existing) =>
                        String(existing.name || '').toLowerCase() === String(file.name || '').toLowerCase()
                    );
                    if (alreadyExists) {
                        showSimpleModal(`Ya existe un archivo con el nombre "${file.name}".`);
                        continue;
                    }
                    if (files.length >= MAX_FILES) {
                        showSimpleModal('No se pueden subir más de 5 archivos.');
                        break;
                    }
                    files.push(file);
                }
                syncRealInput();
                renderThumbs();
                refreshDropzoneBorderStyle();
                if (files.length > 0 && !previewMain.dataset.index) {
                    setMainPreview(0);
                }
                if (files.length === 0) {
                    previewMain.classList.add('hidden');
                    previewEmpty.classList.remove('hidden');
                }
            }

            sourceInput.addEventListener('change', (e) => addFiles(e.target.files));
            refreshDropzoneBorderStyle();
            dropzone.addEventListener('click', (e) => {
                if (files.length === 0) {
                    sourceInput.click();
                } else {
                    e.preventDefault();
                }
            });
            dropzone.addEventListener('dragenter', (e) => {
                e.preventDefault();
                dragDepth += 1;
                showDragOverlay();
            });
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                showDragOverlay();
            });
            dropzone.addEventListener('dragleave', () => {
                dragDepth = Math.max(0, dragDepth - 1);
                if (dragDepth === 0) {
                    hideDragOverlay();
                }
            });
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dragDepth = 0;
                hideDragOverlay();
                addFiles(e.dataTransfer?.files);
            });

            if (dragOverlay) {
                dragOverlay.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    showDragOverlay();
                });
                dragOverlay.addEventListener('drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dragDepth = 0;
                    globalDragDepth = 0;
                    hideDragOverlay();
                    addFiles(e.dataTransfer?.files);
                });
            }

            window.addEventListener('dragenter', (e) => {
                const hasFiles = Array.from(e.dataTransfer?.types || []).includes('Files');
                if (!hasFiles) return;
                globalDragDepth += 1;
                showDragOverlay();
            });

            window.addEventListener('dragleave', (e) => {
                const hasFiles = Array.from(e.dataTransfer?.types || []).includes('Files');
                if (!hasFiles) return;
                globalDragDepth = Math.max(0, globalDragDepth - 1);
                if (globalDragDepth === 0 && dragDepth === 0) {
                    hideDragOverlay();
                }
            });

            document.addEventListener('dragover', (e) => {
                const hasFiles = Array.from(e.dataTransfer?.types || []).includes('Files');
                if (!hasFiles) return;
                if (isInsideDropzone(e.clientX, e.clientY)) {
                    showDragOverlay();
                } else if (dragDepth === 0) {
                    hideDragOverlay();
                }
            });

            document.addEventListener('drop', (e) => {
                if (e.defaultPrevented) {
                    return;
                }
                const hasFiles = Array.from(e.dataTransfer?.types || []).includes('Files');
                if (!hasFiles) return;
                if (!isInsideDropzone(e.clientX, e.clientY)) {
                    if (dragDepth === 0) hideDragOverlay();
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                dragDepth = 0;
                globalDragDepth = 0;
                hideDragOverlay();
                addFiles(e.dataTransfer?.files);
            });
        }

        function initRecipientSmartSelector() {
            const selected = new Set();
            const usersById = new Map();
            usuariosPayload.forEach(function(u) {
                usersById.set(u.id, u);
            });
            const rolesList = document.getElementById('roles-list');
            const catsList = document.getElementById('categorias-list');
            const receptores = document.getElementById('usuarios-receptores');
            const hiddenContainer = document.getElementById('user-ids-hidden-container');
            const userSearchInput = document.getElementById('usuario-search-input');
            const userSearchResults = document.getElementById('usuario-search-results');
            const form = document.getElementById('documentos-alertas-upload-form');
            if (!rolesList || !catsList || !receptores || !hiddenContainer || !userSearchInput || !userSearchResults || !form) return;

            function getUsersByRole(role) {
                return usuariosPayload.filter(u => (u.rol || '') === role);
            }
            function getUsersByCat(catId) {
                return usuariosPayload.filter(u => Number(u.categoria_id || 0) === Number(catId));
            }

            function openConfirm(message, onOk) {
                const ok = confirm(message);
                if (ok) onOk();
            }

            function refreshHiddenInputs() {
                hiddenContainer.innerHTML = '';
                Array.from(selected).forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'user_ids[]';
                    input.value = String(id);
                    hiddenContainer.appendChild(input);
                });
            }

            function refreshRoleCatChecks() {
                rolesList.querySelectorAll('.role-checkbox').forEach(cb => {
                    const users = getUsersByRole(cb.value);
                    cb.checked = users.length > 0 && users.every(u => selected.has(u.id));
                });
                catsList.querySelectorAll('.categoria-checkbox').forEach(cb => {
                    const users = getUsersByCat(cb.value);
                    cb.checked = users.length > 0 && users.every(u => selected.has(u.id));
                });
            }

            function refreshReceivers() {
                receptores.innerHTML = '';
                if (selected.size === 0) {
                    receptores.innerHTML = '<p class="text-xs text-slate-500 dark:text-slate-400">No hay usuarios seleccionados.</p>';
                } else {
                    Array.from(selected)
                        .map(id => usersById.get(id))
                        .filter(Boolean)
                        .sort((a, b) => a.nombre.localeCompare(b.nombre))
                        .forEach(u => {
                            const row = document.createElement('div');
                            row.className = 'mb-1 flex items-center justify-between gap-2 rounded border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800';
                            row.innerHTML = `
                                <div class="flex min-w-0 items-center gap-2">
                                    <div class="h-8 w-8 overflow-hidden rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-500 dark:ring-slate-700 flex items-center justify-center">
                                        ${u.foto ? `<img src="${u.foto}" alt="${u.nombre}" class="h-full w-full object-cover" onerror="this.closest('div').innerHTML='${fallbackUserSvg.replace(/'/g, "\\'")}'` : fallbackUserSvg}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-700 dark:text-slate-100">${u.nombre}</p>
                                        <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">${u.rol || '-'} · ${u.categoria || 'Sin categoría'}</p>
                                    </div>
                                </div>
                            `;
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'rounded bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300';
                            btn.textContent = 'Quitar';
                            btn.addEventListener('click', () => {
                                selected.delete(u.id);
                                refreshRoleCatChecks();
                                refreshReceivers();
                                refreshHiddenInputs();
                            });
                            row.appendChild(btn);
                            receptores.appendChild(row);
                        });
                }
                refreshHiddenInputs();
            }

            function renderSearchResults(items) {
                userSearchResults.innerHTML = '';
                if (!items.length) {
                    userSearchResults.innerHTML = '<p class="px-2 py-2 text-xs text-slate-500 dark:text-slate-400">Sin resultados</p>';
                    userSearchResults.classList.remove('hidden');
                    return;
                }
                items.forEach((u) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left hover:bg-slate-100 dark:hover:bg-slate-800';
                    btn.innerHTML = `
                        <div class="h-8 w-8 overflow-hidden rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-500 dark:ring-slate-700 flex items-center justify-center">
                            ${u.foto ? `<img src="${u.foto}" alt="${u.nombre}" class="h-full w-full object-cover" onerror="this.closest('div').innerHTML='${fallbackUserSvg.replace(/'/g, "\\'")}'` : fallbackUserSvg}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-slate-700 dark:text-slate-100">${u.nombre}</p>
                            <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">${u.rol || '-'} · ${u.categoria || 'Sin categoría'}</p>
                        </div>
                    `;
                    btn.addEventListener('click', () => {
                        selected.add(u.id);
                        userSearchInput.value = '';
                        userSearchResults.classList.add('hidden');
                        refreshRoleCatChecks();
                        refreshReceivers();
                    });
                    userSearchResults.appendChild(btn);
                });
                userSearchResults.classList.remove('hidden');
            }

            rolesList.addEventListener('change', (e) => {
                const target = e.target;
                if (!target.classList.contains('role-checkbox')) return;
                const users = getUsersByRole(target.value);
                if (target.checked) {
                    users.forEach(u => selected.add(u.id));
                } else {
                    openConfirm(`¿Quitar todos los usuarios del rol "${target.value}"?`, () => {
                        users.forEach(u => selected.delete(u.id));
                        refreshRoleCatChecks();
                        refreshReceivers();
                    });
                }
                refreshRoleCatChecks();
                refreshReceivers();
            });

            catsList.addEventListener('change', (e) => {
                const target = e.target;
                if (!target.classList.contains('categoria-checkbox')) return;
                const users = getUsersByCat(target.value);
                if (target.checked) {
                    users.forEach(u => selected.add(u.id));
                } else {
                    openConfirm('¿Quitar todos los usuarios de esta categoría?', () => {
                        users.forEach(u => selected.delete(u.id));
                        refreshRoleCatChecks();
                        refreshReceivers();
                    });
                }
                refreshRoleCatChecks();
                refreshReceivers();
            });

            userSearchInput.addEventListener('input', () => {
                const query = String(userSearchInput.value || '').trim().toLowerCase();
                if (!query) {
                    userSearchResults.classList.add('hidden');
                    return;
                }
                const available = usuariosPayload
                    .filter(u => !selected.has(u.id))
                    .filter(u =>
                        (u.nombre || '').toLowerCase().includes(query) ||
                        (u.rol || '').toLowerCase().includes(query) ||
                        (u.categoria || '').toLowerCase().includes(query)
                    )
                    .slice(0, 20);
                renderSearchResults(available);
            });

            userSearchInput.addEventListener('focus', () => {
                const query = String(userSearchInput.value || '').trim().toLowerCase();
                if (!query) return;
                const available = usuariosPayload
                    .filter(u => !selected.has(u.id))
                    .filter(u =>
                        (u.nombre || '').toLowerCase().includes(query) ||
                        (u.rol || '').toLowerCase().includes(query) ||
                        (u.categoria || '').toLowerCase().includes(query)
                    )
                    .slice(0, 20);
                renderSearchResults(available);
            });

            document.addEventListener('click', (e) => {
                if (!userSearchResults.contains(e.target) && e.target !== userSearchInput) {
                    userSearchResults.classList.add('hidden');
                }
            });

            form.addEventListener('submit', (e) => {
                if (selected.size === 0) {
                    e.preventDefault();
                    alert('Selecciona al menos un usuario receptor.');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initUploadWhatsAppLike();
            initRecipientSmartSelector();
        });

        function verFirmas(docId) {
            const row = document.getElementById('firmas-' + docId);
            if (row) {
                row.classList.toggle('hidden');
            }
        }
    </script>
</x-app-layout>
