<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['paginador', 'perPageName' => 'per_page']));

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

foreach (array_filter((['paginador', 'perPageName' => 'per_page']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="m-4 text-center">
    <form method="GET" id="perPageForm" class="inline-flex items-center justify-center gap-2 text-sm">
        <label for="perPage" class="text-gray-600">Mostrar</label>
        <select name="<?php echo e($perPageName); ?>" id="perPage" class="border border-gray-300 rounded px-2 py-1 text-sm" onchange="document.getElementById('perPageForm').submit()">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [10, 25, 50, 100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($option); ?>" <?php if(request($perPageName, $paginador->perPage()) == $option): echo 'selected'; endif; ?>>
                    <?php echo e($option); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>
        <span class="text-gray-600">por página</span>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = request()->except($perPageName, $paginador->getPageName()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </form>
</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->hasPages()): ?>
    <div class="mt-6 space-y-3 text-center">
        <div class="text-sm text-gray-600">
            Mostrando
            <span class="font-semibold"><?php echo e($paginador->firstItem()); ?></span>
            a
            <span class="font-semibold"><?php echo e($paginador->lastItem()); ?></span>
            de
            <span class="font-semibold"><?php echo e($paginador->total()); ?></span>
            resultados
        </div>

        <div class="flex justify-center">
            <div class="inline-flex items-center gap-1 bg-white px-2 py-1 mb-6 rounded-md shadow-sm">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->currentPage() > 2): ?>
                    <a href="<?php echo e($paginador->url(1)); ?>" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition" title="Primera pagina">
                        &laquo;&laquo;
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->onFirstPage()): ?>
                    <span class="px-2 py-1 text-xs text-gray-400 cursor-not-allowed">&laquo;</span>
                <?php else: ?>
                    <a href="<?php echo e($paginador->previousPageUrl()); ?>" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">
                        &laquo;
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php
                    $current = $paginador->currentPage();
                    $last = $paginador->lastPage();
                    $start = max($current - 1, 1);
                    $end = min($current + 1, $last);
                ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start > 1): ?>
                    <a href="<?php echo e($paginador->url(1)); ?>" class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">1</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start > 2): ?>
                        <span class="px-1 text-xs text-gray-400 select-none">&hellip;</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($page = $start; $page <= $end; $page++): ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($page == $current): ?>
                        <span class="px-2.5 py-1 text-xs font-bold bg-blue-600 text-white rounded shadow border border-blue-700"><?php echo e($page); ?></span>
                    <?php else: ?>
                        <a href="<?php echo e($paginador->url($page)); ?>" class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition"><?php echo e($page); ?></a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($end < $paginador->lastPage()): ?>
                    <?php if($end < $paginador->lastPage() - 1): ?>
                        <span class="px-1 text-xs text-gray-400 select-none">&hellip;</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <a href="<?php echo e($paginador->url($paginador->lastPage())); ?>" class="px-2.5 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition"><?php echo e($paginador->lastPage()); ?></a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginador->hasMorePages()): ?>
                    <a href="<?php echo e($paginador->nextPageUrl()); ?>" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition">&raquo;</a>
                <?php else: ?>
                    <span class="px-2 py-1 text-xs text-gray-400 cursor-not-allowed">&raquo;</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if($paginador->currentPage() < $paginador->lastPage() - 1): ?>
                    <a href="<?php echo e($paginador->url($paginador->lastPage())); ?>" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 rounded transition" title="Ultima pagina">
                        &raquo;&raquo;
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\xampp\htdocs\bigmat\resources\views/components/tabla/paginacion.blade.php ENDPATH**/ ?>