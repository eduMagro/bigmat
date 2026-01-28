<!-- DEBUG: Verificar sesión -->
<script>
    console.log('🔍 Session check:', {
        error: @json(session('error')),
        success: @json(session('success')),
        warning: @json(session('warning')),
        info: @json(session('info'))
    });
</script>

{{-- Los listeners de alertas ahora están consolidados en initAlertsPage() al final del archivo --}}



<!-- Función para notificar a programadores y administradores -->
<script>
    function notificarProgramador(mensaje, asunto = 'Error reportado por usuario') {
        const urlActual = window.location.href;
        const usuario = '{{ auth()->user()->name ?? 'Usuario desconocido' }}';
        const email = '{{ auth()->user()->email ?? 'Email no disponible' }}';

        // Mensaje completo con contexto mejorado
        const mensajeCompleto = `URL: ${urlActual}

Usuario: ${usuario} (${email})
Fecha/Hora: ${new Date().toLocaleString('es-ES')}

Asunto: ${asunto}

Mensaje:
${mensaje}

---
Navegador: ${navigator.userAgent}`;

        fetch("{{ route('alertas.store') }}", {
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
                    enviar_a_departamentos: ['Programador', 'Administrador']
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

    /**
     * Función global para mostrar errores con botón de reportar
     * Uso: mostrarError('Mensaje de error')
     * Uso: mostrarError('Mensaje', 'Título personalizado')
     * Uso: mostrarError('Mensaje', 'Título', { timer: 5000 })
     */
    window.mostrarError = function(mensaje, titulo = 'Error', opciones = {}) {
        return Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonColor: '#d33',
            showCancelButton: true,
            cancelButtonText: 'Reportar Error',
            cancelButtonColor: '#6c757d',
            ...opciones
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                notificarProgramador(mensaje, titulo);
            }
            return result;
        });
    }

    /**
     * Función global para mostrar éxito
     * Uso: mostrarExito('Operación completada')
     */
    window.mostrarExito = function(mensaje, titulo = null, opciones = {}) {
        return Swal.fire({
            icon: 'success',
            title: titulo,
            text: mensaje,
            confirmButtonColor: '#28a745',
            ...opciones
        });
    }

    /**
     * Función global para mostrar advertencia con botón de reportar
     * Uso: mostrarAdvertencia('Mensaje de advertencia')
     */
    window.mostrarAdvertencia = function(mensaje, titulo = 'Atención', opciones = {}) {
        return Swal.fire({
            icon: 'warning',
            title: titulo,
            text: mensaje,
            confirmButtonColor: '#FBBF24',
            showCancelButton: true,
            cancelButtonText: 'Reportar',
            cancelButtonColor: '#6c757d',
            ...opciones
        }).then((result) => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                notificarProgramador(mensaje, titulo);
            }
            return result;
        });
    }
</script>

<script>
    function initAlertsPage() {
        // Prevenir doble inicialización
        if (document.body.dataset.alertsPageInit === 'true') return;

        console.log('🔍 Inicializando sistema de alertas...');

        // Procesar abort (acceso denegado)
        @if (session('abort'))
            Swal.fire({
                icon: 'error',
                title: 'Acceso denegado',
                text: @json(session('abort')),
            }).then(() => {
                window.location.reload();
            });
        @endif

        // Procesar errores de validación
        @if ($errors->any())
            const erroresArray = @json($errors->all());
            let erroresHtml = erroresArray.map(e => '<li>' + e + '</li>').join('');
            let erroresTexto = erroresArray.map(e => '- ' + e).join('\n');

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
        @endif

        // Procesar mensaje de éxito
        @if (session('success'))
            const mensaje = @json(session('success'));
            const esImportacion = @json(session('import_report', false));
            const tieneAdvertencias = @json(session('tiene_advertencias', false));
            const nombreArchivo = @json(session('nombre_archivo', null));

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
        @endif

        // Procesar mensaje de error
        @if (session('error'))
            const mensajeError = @json(session('error'));
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensajeError,
                confirmButtonColor: '#d33',
                showCancelButton: true,
                cancelButtonText: 'Reportar Error',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    notificarProgramador(mensajeError);
                }
            });
        @endif

        // Procesar mensaje de info
        @if (session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: @json(session('info')),
                confirmButtonColor: '#3B82F6'
            });
        @endif

        // Procesar mensaje de warning
        @if (session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: @json(session('warning')),
                confirmButtonColor: '#FBBF24'
            });
        @endif

        // Procesar múltiples warnings
        @if (session('warnings'))
            const warningsArray = @json(session('warnings'));
            warningsArray.forEach(warning => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: warning,
                    timer: 5000,
                    showConfirmButton: false
                });
            });
        @endif

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
