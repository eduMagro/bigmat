{{-- Usuarios asignados --}}
<div>
    <h4 class="text-md font-semibold text-gray-700 mb-2">Usuarios asignados</h4>

    @if ($departamento->usuarios->isEmpty())
        <p class="text-gray-500 text-sm">Sin usuarios asignados.</p>
    @else
        <ul class="space-y-2">
            @foreach ($departamento->usuarios as $usuario)
                <li class="pb-2 border-b last:border-0">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-800 text-sm sm:text-base min-w-[150px] sm:min-w-[200px]">
                            {{ $usuario->nombre_completo }}
                        </span>
                        <span class="text-xs sm:text-sm text-gray-500">
                            ({{ $usuario->email }}{{ $usuario->pivot->rol_departamental ? ' – ' . $usuario->pivot->rol_departamental : '' }})
                        </span>
                        <label class="flex items-center gap-1 text-xs sm:text-sm text-gray-700 whitespace-nowrap">
                            <input type="checkbox"
                                @change="actualizarPermisos({{ $departamento->id }}, {{ $usuario->id }}, 'ver', $event.target.checked)"
                                {{ $departamento->secciones->every(fn($s) => $s->permisosAcceso->where('user_id', $usuario->id)->first()?->puede_ver) ? 'checked' : '' }}
                                class="rounded">
                            Ver
                        </label>
                        <label class="flex items-center gap-1 text-xs sm:text-sm text-gray-700 whitespace-nowrap">
                            <input type="checkbox"
                                @change="actualizarPermisos({{ $departamento->id }}, {{ $usuario->id }}, 'crear', $event.target.checked)"
                                {{ $departamento->secciones->every(fn($s) => $s->permisosAcceso->where('user_id', $usuario->id)->first()?->puede_crear) ? 'checked' : '' }}
                                class="rounded">
                            Crear
                        </label>
                        <label class="flex items-center gap-1 text-xs sm:text-sm text-gray-700 whitespace-nowrap">
                            <input type="checkbox"
                                @change="actualizarPermisos({{ $departamento->id }}, {{ $usuario->id }}, 'editar', $event.target.checked)"
                                {{ $departamento->secciones->every(fn($s) => $s->permisosAcceso->where('user_id', $usuario->id)->first()?->puede_editar) ? 'checked' : '' }}
                                class="rounded">
                            Editar
                        </label>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

{{-- Secciones asignadas --}}
<div>
    <h4 class="text-md font-semibold text-gray-700 mb-2">Secciones visibles</h4>
    @if ($departamento->secciones->isEmpty())
        <p class="text-gray-500 text-sm">Sin secciones asignadas.</p>
    @else
        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-gray-800">
            @foreach ($departamento->secciones as $seccion)
                <li class="bg-white border rounded px-3 py-2 shadow-sm text-xs sm:text-sm">
                    <div class="font-medium">{{ $seccion->nombre }}</div>
                    <div class="text-gray-500 text-xs truncate">{{ $seccion->ruta }}</div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
