<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['paginador', 'perPageOptions' => [10, 25, 50, 100]]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['paginador', 'perPageOptions' => [10, 25, 50, 100]]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div>
    
    <div class="m-4 text-center">
        <div class="inline-flex items-center justify-center gap-2 text-sm">
            <label for="perPageSelect" class="text-gray-600">Mostrar</label>
            <select wire:model.live="perPage"
                    id="perPageSelect"
                    class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $perPageOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($option); ?>"><?php echo e($option); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>
            <span class="text-gray-600">por página</span>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador && $paginador->total() > 0): ?>
        <div class="mt-6 space-y-3 text-center">

            
            <div class="text-sm text-gray-600">
                Mostrando
                <span class="font-semibold"><?php echo e($paginador->firstItem() ?? 0); ?></span>
                a
                <span class="font-semibold"><?php echo e($paginador->lastItem() ?? 0); ?></span>
                de
                <span class="font-semibold"><?php echo e($paginador->total()); ?></span>
                resultados
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->hasPages()): ?>
                <div class="flex justify-center">
                    <nav class="inline-flex flex-wrap gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow-sm">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->onFirstPage()): ?>
                            <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">
                                &laquo;
                            </span>
                        <?php else: ?>
                            <button type="button"
                                    wire:click="previousPage"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                                &laquo;
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php
                            $current = $paginador->currentPage();
                            $last = $paginador->lastPage();
                            $range = 2;
                            $pages = [];

                            // Siempre mostrar la primera página
                            $pages[] = 1;

                            // Páginas alrededor de la actual
                            for ($i = max(2, $current - $range); $i <= min($last - 1, $current + $range); $i++) {
                                $pages[] = $i;
                            }

                            // Siempre mostrar la última página
                            if ($last > 1) {
                                $pages[] = $last;
                            }

                            $pages = array_unique($pages);
                            sort($pages);
                        ?>

                        <?php $prevPage = 0; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($prevPage > 0 && $page > $prevPage + 1): ?>
                                <span class="px-2 text-xs text-gray-400 select-none">&hellip;</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($page == $current): ?>
                                <span class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700">
                                    <?php echo e($page); ?>

                                </span>
                            <?php else: ?>
                                <button type="button"
                                        wire:click="gotoPage(<?php echo e($page); ?>)"
                                        wire:loading.attr="disabled"
                                        class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                                    <?php echo e($page); ?>

                                </button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php $prevPage = $page; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->hasMorePages()): ?>
                            <button type="button"
                                    wire:click="nextPage"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition disabled:opacity-50">
                                &raquo;
                            </button>
                        <?php else: ?>
                            <span class="px-3 py-1 text-xs text-gray-400 cursor-not-allowed">&raquo;</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div wire:loading class="text-center py-2">
        <span class="text-sm text-blue-600 font-medium">Cargando...</span>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\bigmat\resources\views/components/tabla/paginacion-livewire.blade.php ENDPATH**/ ?>