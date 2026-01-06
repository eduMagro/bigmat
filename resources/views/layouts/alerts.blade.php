<!-- DEBUG: Verificar sesión -->
<script>
    console.log('🔍 Session check:', {
        error: @json(session('error')),
        success: @json(session('success')),
        warning: @json(session('warning')),
        info: @json(session('info'))
    });
</script>

@if (session('abort'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: "{{ session('abort') }}",
        }).then(() => {
            window.location.reload(); // Recarga la página tras el mensaje
        });
    </script>
@endif


{{-- Los listeners de alertas ahora están consolidados en initAlertsPage() al final del archivo --}}



<!-- Función para notificar a programadores -->
<script>
    function notificarProgramador(mensaje, asunto = 'Error reportado por usuario') {
        const urlActual = window.location.href;
        const usuario = '{{ auth()->user()->name ?? 'Usuario desconocido' }}';
        const email = '{{ auth()->user()->email ?? 'Email no disponible' }}';

        // ✅ Mensaje completo con contexto mejorado
        const mensajeCompleto = `🔗 URL: ${urlActual}

👤 Usuario: ${usuario} (${email})
📅 Fecha/Hora: ${new Date().toLocaleString('es-ES')}

📋 ${asunto}

📜 Mensaje:
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
        @if ($errors->any())
            let erroresHtml = '';
            let erroresTexto = '';
            @foreach ($errors->all() as $error)
                erroresHtml += '<li>{{ $error }}<\/li>';
                erroresTexto += '- {{ $error }}\n';
            @endforeach

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
            @foreach (session('warnings') as $warning)
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: "{{ $warning }}",
                    timer: 5000,
                    showConfirmButton: false
                });
            @endforeach
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
