<div class="max-md:hidden" x-data="tablaUsuarios()">
    
    <?php if (isset($component)) { $__componentOriginal1ace7b5c2a7c62c75387dae685ef5483 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1ace7b5c2a7c62c75387dae685ef5483 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabla.filtros-aplicados','data' => ['filtros' => $filtrosActivos]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabla.filtros-aplicados'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['filtros' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filtrosActivos)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1ace7b5c2a7c62c75387dae685ef5483)): ?>
<?php $attributes = $__attributesOriginal1ace7b5c2a7c62c75387dae685ef5483; ?>
<?php unset($__attributesOriginal1ace7b5c2a7c62c75387dae685ef5483); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1ace7b5c2a7c62c75387dae685ef5483)): ?>
<?php $component = $__componentOriginal1ace7b5c2a7c62c75387dae685ef5483; ?>
<?php unset($__componentOriginal1ace7b5c2a7c62c75387dae685ef5483); ?>
<?php endif; ?>

    
    <div class="flex justify-between items-center mb-4 px-4">
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Total: <strong><?php echo e($registrosUsuarios->total()); ?></strong> usuarios</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="limpiarFiltros" class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded">
                Limpiar filtros
            </button>
            <a href="<?php echo e(route('incorporaciones.index')); ?>" class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded">
                + Incorporaciones
            </a>
        </div>
    </div>

    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 text-sm">
            <thead class="bg-gray-100">
                
                <tr>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('id')">
                        ID <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'id'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('nombre_completo')">
                        Nombre <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'nombre_completo'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('email')">
                        Email <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'email'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b">Móvil Personal</th>
                    <th class="px-2 py-2 border-b">Móvil Empresa</th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('numero_corto')">
                        Nº Corp <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'numero_corto'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('dni')">
                        DNI <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'dni'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('empresa')">
                        Empresa <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'empresa'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('rol')">
                        Rol <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'rol'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('categoria')">
                        Categoría <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'categoria'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b cursor-pointer hover:bg-gray-200" wire:click="sortBy('estado')">
                        Estado <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'estado'): ?> <span><?php echo e($order === 'asc' ? '↑' : '↓'); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </th>
                    <th class="px-2 py-2 border-b">Acciones</th>
                </tr>
                
                <tr class="bg-gray-50">
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="user_id" placeholder="ID" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="filtro_name" placeholder="Nombre" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="email" placeholder="Email" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="movil_personal" placeholder="Móvil" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="movil_empresa" placeholder="Móvil" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="numero_corto" placeholder="Nº" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <input type="text" wire:model.live.debounce.300ms="dni" placeholder="DNI" class="w-full text-xs border rounded px-1 py-1">
                    </td>
                    <td class="px-1 py-1 border-b">
                        <select wire:model.live="empresa_id" class="w-full text-xs border rounded px-1 py-1">
                            <option value="">Todas</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $empresas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empresa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($empresa->id); ?>"><?php echo e($empresa->nombre); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </td>
                    <td class="px-1 py-1 border-b">
                        <select wire:model.live="rol" class="w-full text-xs border rounded px-1 py-1">
                            <option value="">Todos</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($r); ?>"><?php echo e(ucfirst($r)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </td>
                    <td class="px-1 py-1 border-b">
                        <select wire:model.live="categoria_id" class="w-full text-xs border rounded px-1 py-1">
                            <option value="">Todas</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->nombre); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </td>
                    <td class="px-1 py-1 border-b">
                        <select wire:model.live="estado" class="w-full text-xs border rounded px-1 py-1">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="baja">Baja</option>
                        </select>
                    </td>
                    <td class="px-1 py-1 border-b"></td>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $registrosUsuarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr wire:key="user-<?php echo e($user->id); ?>"
                        class="hover:bg-blue-50 transition <?php echo e($user->estado === 'baja' ? 'bg-red-50 text-gray-500' : ''); ?>"
                        x-data="{ editando: false, datos: <?php echo \Illuminate\Support\Js::from([
                            'name' => $user->name,
                            'primer_apellido' => $user->primer_apellido,
                            'segundo_apellido' => $user->segundo_apellido,
                            'email' => $user->email,
                            'movil_personal' => $user->movil_personal,
                            'movil_empresa' => $user->movil_empresa,
                            'numero_corto' => $user->numero_corto,
                            'dni' => $user->dni,
                        ])->toHtml() ?> }">
                        <td class="px-2 py-2 border-b text-center"><?php echo e($user->id); ?></td>
                        <td class="px-2 py-2 border-b">
                            <template x-if="!editando">
                                <a href="<?php echo e(route('users.show', $user)); ?>" class="text-blue-600 hover:underline">
                                    <?php echo e($user->nombre_completo); ?>

                                </a>
                            </template>
                            <template x-if="editando">
                                <div class="space-y-1">
                                    <input type="text" x-model="datos.name" placeholder="Nombre" class="w-full text-xs border rounded px-1 py-0.5">
                                    <input type="text" x-model="datos.primer_apellido" placeholder="1er Apellido" class="w-full text-xs border rounded px-1 py-0.5">
                                    <input type="text" x-model="datos.segundo_apellido" placeholder="2º Apellido" class="w-full text-xs border rounded px-1 py-0.5">
                                </div>
                            </template>
                        </td>
                        <td class="px-2 py-2 border-b">
                            <template x-if="!editando">
                                <span><?php echo e($user->email); ?></span>
                            </template>
                            <template x-if="editando">
                                <input type="email" x-model="datos.email" class="w-full text-xs border rounded px-1 py-0.5">
                            </template>
                        </td>
                        <td class="px-2 py-2 border-b">
                            <template x-if="!editando">
                                <span><?php echo e($user->movil_personal); ?></span>
                            </template>
                            <template x-if="editando">
                                <input type="text" x-model="datos.movil_personal" class="w-full text-xs border rounded px-1 py-0.5">
                            </template>
                        </td>
                        <td class="px-2 py-2 border-b">
                            <template x-if="!editando">
                                <span><?php echo e($user->movil_empresa); ?></span>
                            </template>
                            <template x-if="editando">
                                <input type="text" x-model="datos.movil_empresa" class="w-full text-xs border rounded px-1 py-0.5">
                            </template>
                        </td>
                        <td class="px-2 py-2 border-b text-center">
                            <template x-if="!editando">
                                <span><?php echo e($user->numero_corto); ?></span>
                            </template>
                            <template x-if="editando">
                                <input type="text" x-model="datos.numero_corto" class="w-16 text-xs border rounded px-1 py-0.5">
                            </template>
                        </td>
                        <td class="px-2 py-2 border-b">
                            <template x-if="!editando">
                                <span><?php echo e($user->dni); ?></span>
                            </template>
                            <template x-if="editando">
                                <input type="text" x-model="datos.dni" class="w-full text-xs border rounded px-1 py-0.5">
                            </template>
                        </td>
                        <td class="px-2 py-2 border-b"><?php echo e($user->empresa->nombre ?? '-'); ?></td>
                        <td class="px-2 py-2 border-b"><?php echo e(ucfirst($user->rol ?? '-')); ?></td>
                        <td class="px-2 py-2 border-b"><?php echo e($user->categoria->nombre ?? '-'); ?></td>
                        <td class="px-2 py-2 border-b text-center">
                            <span class="px-2 py-0.5 rounded text-xs <?php echo e($user->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo e(ucfirst($user->estado ?? 'activo')); ?>

                            </span>
                        </td>
                        <td class="px-2 py-2 border-b">
                            <div class="flex items-center gap-1">
                                
                                <template x-if="!editando">
                                    <button @click="editando = true" class="p-1 text-blue-600 hover:bg-blue-100 rounded" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                </template>
                                <template x-if="editando">
                                    <div class="flex gap-1">
                                        <button @click="guardarCambios(<?php echo e($user->id); ?>, datos); editando = false" class="p-1 text-green-600 hover:bg-green-100 rounded" title="Guardar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <button @click="editando = false" class="p-1 text-red-600 hover:bg-red-100 rounded" title="Cancelar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                
                                <a href="<?php echo e(route('users.show', $user)); ?>" class="p-1 text-gray-600 hover:bg-gray-100 rounded" title="Ver perfil">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->rol === 'operario'): ?>
                                    <button type="button"
                                            @click="confirmarGenerarTurnos(<?php echo e($user->id); ?>, '<?php echo e($user->nombre_completo); ?>')"
                                            class="p-1 text-purple-600 hover:bg-purple-100 rounded"
                                            title="Generar turnos">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                            No se encontraron usuarios con los filtros aplicados.
                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    
    <?php if (isset($component)) { $__componentOriginala79d17c1e0abc25b92051f17b0601d07 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala79d17c1e0abc25b92051f17b0601d07 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabla.paginacion-livewire','data' => ['paginador' => $registrosUsuarios]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabla.paginacion-livewire'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginador' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($registrosUsuarios)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala79d17c1e0abc25b92051f17b0601d07)): ?>
<?php $attributes = $__attributesOriginala79d17c1e0abc25b92051f17b0601d07; ?>
<?php unset($__attributesOriginala79d17c1e0abc25b92051f17b0601d07); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala79d17c1e0abc25b92051f17b0601d07)): ?>
<?php $component = $__componentOriginala79d17c1e0abc25b92051f17b0601d07; ?>
<?php unset($__componentOriginala79d17c1e0abc25b92051f17b0601d07); ?>
<?php endif; ?>

    
    <script>
        function tablaUsuarios() {
            return {
                async guardarCambios(userId, datos) {
                    try {
                        const response = await fetch(`<?php echo e(url('/users')); ?>/${userId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                _method: 'PUT',
                                name: datos.name,
                                primer_apellido: datos.primer_apellido,
                                segundo_apellido: datos.segundo_apellido,
                                email: datos.email,
                                movil_personal: datos.movil_personal,
                                movil_empresa: datos.movil_empresa,
                                numero_corto: datos.numero_corto,
                                dni: datos.dni
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Usuario actualizado',
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            // Refrescar la tabla
                            Livewire.dispatch('$refresh');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo guardar el usuario.',
                                toast: true,
                                position: 'top-end',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: error.message || 'No se pudo guardar el usuario.',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                },

                confirmarGenerarTurnos(userId, nombreUsuario) {
                    Swal.fire({
                        title: 'Generar turnos',
                        html: `<p class="mb-3">¿Qué tipo de turno desea generar para <strong>${nombreUsuario}</strong> durante el resto del año?</p>`,
                        input: 'select',
                        inputOptions: {
                            'diurno': 'Diurno (rota mañana/tarde los viernes)',
                            'nocturno': 'Nocturno (fijo)',
                            'mañana': 'Mañana (fijo)'
                        },
                        inputPlaceholder: 'Seleccione un tipo de turno',
                        showCancelButton: true,
                        confirmButtonText: 'Siguiente',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#7c3aed',
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Debe seleccionar un tipo de turno';
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            const tipoTurno = result.value;

                            // Si es diurno, preguntar turno inicial
                            if (tipoTurno === 'diurno') {
                                Swal.fire({
                                    title: 'Turno inicial',
                                    html: `<p class="mb-3">¿Con qué turno desea comenzar?</p>`,
                                    input: 'select',
                                    inputOptions: {
                                        'mañana': 'Mañana',
                                        'tarde': 'Tarde'
                                    },
                                    inputPlaceholder: 'Seleccione turno inicial',
                                    showCancelButton: true,
                                    confirmButtonText: 'Generar turnos',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#7c3aed',
                                    inputValidator: (value) => {
                                        if (!value) {
                                            return 'Debe seleccionar un turno inicial';
                                        }
                                    }
                                }).then((resultInicio) => {
                                    if (resultInicio.isConfirmed && resultInicio.value) {
                                        this.enviarFormularioTurnos(userId, tipoTurno, resultInicio.value);
                                    }
                                });
                            } else {
                                // Para nocturno y mañana, enviar directamente
                                this.enviarFormularioTurnos(userId, tipoTurno, null);
                            }
                        }
                    });
                },

                enviarFormularioTurnos(userId, tipoTurno, turnoInicio) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `<?php echo e(url('/usuarios')); ?>/${userId}/generar-turnos`;

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfInput);

                    const tipoTurnoInput = document.createElement('input');
                    tipoTurnoInput.type = 'hidden';
                    tipoTurnoInput.name = 'tipo_turno';
                    tipoTurnoInput.value = tipoTurno;
                    form.appendChild(tipoTurnoInput);

                    if (turnoInicio) {
                        const turnoInicioInput = document.createElement('input');
                        turnoInicioInput.type = 'hidden';
                        turnoInicioInput.name = 'turno_inicio';
                        turnoInicioInput.value = turnoInicio;
                        form.appendChild(turnoInicioInput);
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }
    </script>
</div>
<?php /**PATH C:\xampp\htdocs\bigmat\resources\views/livewire/users-table.blade.php ENDPATH**/ ?>