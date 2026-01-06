<!-- DEBUG: Verificar sesión -->
<script>
    console.log('🔍 Session check:', {
        error: <?php echo json_encode(session('error'), 15, 512) ?>,
        success: <?php echo json_encode(session('success'), 15, 512) ?>,
        warning: <?php echo json_encode(session('warning'), 15, 512) ?>,
        info: <?php echo json_encode(session('info'), 15, 512) ?>
    });
</script>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('abort')): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: "<?php echo e(session('abort')); ?>",
        }).then(() => {
            window.location.reload(); // Recarga la página tras el mensaje
        });
    </script>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>






<!-- Función para notificar a programadores -->
<script>
    function notificarProgramador(mensaje, asunto = 'Error reportado por usuario') {
        const urlActual = window.location.href;
        const usuario = '<?php echo e(auth()->user()->name ?? 'Usuario desconocido'); ?>';
        const email = '<?php echo e(auth()->user()->email ?? 'Email no disponible'); ?>';

        // ✅ Mensaje completo con contexto mejorado
        const mensajeCompleto = `🔗 URL: ${urlActual}

👤 Usuario: ${usuario} (${email})
📅 Fecha/Hora: ${new Date().toLocaleString('es-ES')}

📋 ${asunto}

📜 Mensaje:
${mensaje}

---
Navegador: ${navigator.userAgent}`;

        fetch("<?php echo e(route('alertas.store')); ?>", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify({
                    mensaje: mensajeCompleto,
                    enviar_a_departamentos: ['Programador']
                })
            })
            .then(async resp => {
                if (!resp.ok) {
                    const texto = await resp.text();
                    throw new Error(`HTTP ${resp.status}: ${texto}`);
                }
                return resp.json();
            })
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Notificación enviada',
                    text: 'Los técnicos han sido notificados y revisarán las advertencias.',
                    confirmButtonColor: '#28a745'
                });
            })
            .catch(err => {
                console.error('⚠️ Error al enviar notificación:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al enviar',
                    text: 'No se pudo enviar la notificación. Por favor contacte directamente con el equipo técnico.',
                    confirmButtonColor: '#d33'
                });
            });
    }
</script>

<script>
    function initAlertsPage() {
        // Prevenir doble inicialización
        if (document.body.dataset.alertsPageInit === 'true') return;

        console.log('🔍 Inicializando sistema de alertas...');

        // Procesar errores de validación
        <?php if($errors->any()): ?>
            let erroresHtml = '';
            let erroresTexto = '';
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                erroresHtml += '<li><?php echo e($error); ?><\/li>';
                erroresTexto += '- <?php echo e($error); ?>\n';
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            Swal.fire({
                icon: 'error',
                title: 'Errores encontrados',
                html: '<ul>' + erroresHtml + '</ul>',
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: "Reportar Error"
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador(erroresTexto);
                }
            });
        <?php endif; ?>

        // Procesar mensaje de éxito
        <?php if(session('success')): ?>
            const mensaje = <?php echo json_encode(session('success'), 15, 512) ?>;
            const esImportacion = <?php echo json_encode(session('import_report', false), 512) ?>;
            const tieneAdvertencias = <?php echo json_encode(session('tiene_advertencias', false), 512) ?>;
            const nombreArchivo = <?php echo json_encode(session('nombre_archivo', null), 512) ?>;

            if (esImportacion) {
                const mensajeHtml = mensaje.replace(/\n/g, '<br>');
                const config = {
                    icon: 'success',
                    html: '<div style="text-align: left; font-family: monospace; white-space: pre-wrap;">' +
                        mensajeHtml + '</div>',
                    confirmButtonColor: '#28a745',
                    width: '650px',
                };

                if (tieneAdvertencias) {
                    config.showCancelButton = true;
                    config.cancelButtonText = '⚠️ Reportar Advertencias';
                    config.confirmButtonText = 'Aceptar';
                    config.cancelButtonColor = '#f59e0b';
                }

                Swal.fire(config).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel && tieneAdvertencias) {
                        const asunto = nombreArchivo ?
                            `Advertencias en importación: ${nombreArchivo}` :
                            'Advertencias en importación de planillas';
                        notificarProgramador(mensaje, asunto);
                    }
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    text: mensaje,
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    console.log('Operación exitosa:', mensaje);
                });
            }
        <?php endif; ?>

        // Procesar mensaje de info
        <?php if(session('info')): ?>
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: <?php echo json_encode(session('info'), 15, 512) ?>,
                confirmButtonColor: '#3B82F6'
            });
        <?php endif; ?>

        // Procesar mensaje de warning
        <?php if(session('warning')): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: <?php echo json_encode(session('warning'), 15, 512) ?>,
                confirmButtonColor: '#FBBF24'
            });
        <?php endif; ?>

        // Procesar múltiples warnings
        <?php if(session('warnings')): ?>
            <?php $__currentLoopData = session('warnings'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warning): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: "<?php echo e($warning); ?>",
                    timer: 5000,
                    showConfirmButton: false
                });
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>

        // Marcar como inicializado
        document.body.dataset.alertsPageInit = 'true';
    }

    // Registrar en el sistema global
    window.pageInitializers = window.pageInitializers || [];
    window.pageInitializers.push(initAlertsPage);

    // Configurar listeners
    document.addEventListener('livewire:navigated', initAlertsPage);
    document.addEventListener('DOMContentLoaded', initAlertsPage);

    // Limpiar flag antes de navegar
    document.addEventListener('livewire:navigating', () => {
        document.body.dataset.alertsPageInit = 'false';
    });
</script>
<?php /**PATH C:\xampp\htdocs\bigmat\resources\views/layouts/alerts.blade.php ENDPATH**/ ?>