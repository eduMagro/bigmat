<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'texto' => 'Enviar',
    'color' => 'blue',
]));

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

foreach (array_filter(([
    'texto' => 'Enviar',
    'color' => 'blue',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<button type="submit" x-bind:disabled="cargando" x-bind:class="{ 'cursor-wait': cargando }"
    class="inline-flex items-center gap-2 rounded-md px-4 py-2 font-semibold text-white shadow
           bg-<?php echo e($color); ?>-600 hover:bg-<?php echo e($color); ?>-700 focus:outline-none focus:ring-2
           focus:ring-<?php echo e($color); ?>-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">

    
    <div x-show="cargando" class="flex space-x-1" aria-hidden="true">
        <span class="h-2 w-2 rounded-full bg-white animate-bounce [animation-delay:-0.3s]"></span>
        <span class="h-2 w-2 rounded-full bg-white animate-bounce [animation-delay:-0.15s]"></span>
        <span class="h-2 w-2 rounded-full bg-white animate-bounce"></span>
    </div>

    <span x-show="!cargando"><?php echo e($texto); ?></span>
    <span x-show="cargando">Cargando…</span>
</button>
<?php /**PATH C:\xampp\htdocs\bigmat\resources\views/components/boton-submit.blade.php ENDPATH**/ ?>