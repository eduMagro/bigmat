<x-app-layout>
    <x-slot name="title">Calendario de Vacaciones</x-slot>

    <div class="w-full max-w-7xl mx-auto py-6 space-y-8" id="contenedorCalendarios">

        {{-- Solicitudes pendientes --}}
        <div>
            <h3 class="text-xl font-semibold text-blue-700 mb-4">Solicitudes pendientes</h3>
            @if ($solicitudesPendientes->isEmpty())
                <p class="text-gray-600">No hay solicitudes pendientes.</p>
            @else
                <x-tabla-solicitudes :solicitudes="$solicitudesPendientes" />
            @endif
        </div>

        {{-- Calendario de vacaciones --}}
        <div class="w-full bg-white rounded-lg shadow-lg p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-blue-700 mb-4">Calendario de Vacaciones</h3>
            <div id="calendario-vacaciones"></div>
        </div>
    </div>

    <!-- FullCalendar y dependencias -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>

    <style>
        /* Estilos para seleccion de rango */
        .fc .bg-select-range {
            background: rgba(99, 102, 241, 0.25) !important;
            border-radius: 4px;
        }

        .fc .bg-select-endpoint {
            background: rgba(99, 102, 241, 0.45) !important;
        }

        .fc .bg-select-endpoint-left {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            border-left: 3px solid rgba(99, 102, 241, 0.8);
            box-shadow: -4px 0 8px rgba(99, 102, 241, 0.4);
        }

        .fc .bg-select-endpoint-right {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: 3px solid rgba(99, 102, 241, 0.8);
            box-shadow: 4px 0 8px rgba(99, 102, 241, 0.4);
        }

        .fc .fc-daygrid-day-bg {
            overflow: visible;
        }

        .fc .bg-select-range,
        .fc .bg-select-endpoint {
            pointer-events: none !important;
        }

        /* Estilos para el selector de usuario en SweetAlert */
        .usuario-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.15s;
        }

        .usuario-item:hover {
            background-color: #f3f4f6;
        }

        .usuario-item.selected {
            background-color: #dbeafe;
            border-color: #3b82f6;
        }

        .usuario-item:last-child {
            border-bottom: none;
        }

        .usuario-nombre {
            font-weight: 500;
            color: #1f2937;
        }

        .usuario-vacaciones {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 600;
        }

        .vacaciones-ok {
            background-color: #d1fae5;
            color: #065f46;
        }

        .vacaciones-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .vacaciones-full {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .lista-usuarios {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 12px;
        }

        .buscador-usuarios {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .buscador-usuarios:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .info-seleccion {
            background: linear-gradient(135deg, #1e3a5f 0%, #111827 100%);
            color: white;
            margin: -20px -20px 20px -20px;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
    </style>

    <script>
        // Cache global de usuarios
        let usuariosCache = null;

        // Funciones AJAX para aprobar/denegar solicitudes
        function aprobarSolicitud(solicitudId, btn) {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!tokenMeta) {
                mostrarError('No se encontró el token CSRF. Recarga la página.');
                return;
            }
            const token = tokenMeta.getAttribute('content');
            const row = btn.closest('tr');

            // Deshabilitar botones mientras se procesa
            const btns = row.querySelectorAll('button');
            btns.forEach(b => b.disabled = true);
            btn.textContent = 'Procesando...';

            fetch(`{{ url('/vacaciones') }}/${solicitudId}/aprobar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch {
                            throw new Error(text.substring(0, 200));
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Eliminar la fila de la tabla
                    row.remove();
                    // Refrescar el calendario
                    refrescarCalendarios();
                    // Verificar si quedan solicitudes
                    verificarTablasVacias();
                    // Mostrar notificacion
                    Swal.fire({
                        icon: 'success',
                        title: 'Aprobada',
                        text: data.message || 'Solicitud aprobada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    mostrarError(data.error || 'No se pudo aprobar la solicitud');
                    btns.forEach(b => b.disabled = false);
                    btn.textContent = 'Aprobar';
                }
            })
            .catch(error => {
                console.error('Error aprobar:', error);
                mostrarError(error.message || 'Error de conexión');
                btns.forEach(b => b.disabled = false);
                btn.textContent = 'Aprobar';
            });
        }

        function denegarSolicitud(solicitudId, btn) {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!tokenMeta) {
                mostrarError('No se encontró el token CSRF. Recarga la página.');
                return;
            }
            const token = tokenMeta.getAttribute('content');
            const row = btn.closest('tr');

            // Deshabilitar botones mientras se procesa
            const btns = row.querySelectorAll('button');
            btns.forEach(b => b.disabled = true);
            btn.textContent = 'Procesando...';

            fetch(`{{ url('/vacaciones') }}/${solicitudId}/denegar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch {
                            throw new Error(text.substring(0, 200));
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Eliminar la fila de la tabla
                    row.remove();
                    // Verificar si quedan solicitudes
                    verificarTablasVacias();
                    // Mostrar notificacion
                    Swal.fire({
                        icon: 'success',
                        title: 'Denegada',
                        text: data.message || 'Solicitud denegada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    mostrarError(data.error || 'No se pudo denegar la solicitud');
                    btns.forEach(b => b.disabled = false);
                    btn.textContent = 'Denegar';
                }
            })
            .catch(error => {
                console.error('Error denegar:', error);
                mostrarError(error.message || 'Error de conexión');
                btns.forEach(b => b.disabled = false);
                btn.textContent = 'Denegar';
            });
        }

        function refrescarCalendarios() {
            // Refrescar el calendario de la pagina si existe
            const calEl = document.getElementById('calendario-vacaciones');
            if (calEl && calEl._calendar) {
                calEl._calendar.refetchEvents();
            }
            // Invalidar cache de usuarios
            usuariosCache = null;
        }

        function verificarTablasVacias() {
            // Buscar todas las tablas de solicitudes
            document.querySelectorAll('table').forEach(tabla => {
                const tbody = tabla.querySelector('tbody');
                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                    // Reemplazar la tabla con mensaje de "no hay solicitudes"
                    const container = tabla.closest('.overflow-x-auto');
                    if (container) {
                        container.innerHTML = '<p class="text-gray-600">No hay solicitudes pendientes.</p>';
                    }
                }
            });
        }

        function inicializarCalendario() {
            const el = document.getElementById('calendario-vacaciones');
            if (!el) return;

            // Destruir calendario existente si existe
            if (el._calendar) {
                el._calendar.destroy();
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Estado de seleccion click-and-click
            const state = {
                startClick: null,
                hoverDayEvs: []
            };

            // Funciones auxiliares para el highlight de seleccion
            function eachDayStr(aStr, bStr) {
                const days = [];
                let a = new Date(aStr),
                    b = new Date(bStr);
                if (a > b)[a, b] = [b, a];
                for (let d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
                    days.push(d.toISOString().split('T')[0]);
                }
                return days;
            }

            function addOneDayStr(d) {
                const x = new Date(d);
                x.setDate(x.getDate() + 1);
                return x.toISOString().split('T')[0];
            }

            function clearTempHighlight() {
                if (!state.hoverDayEvs || !state.hoverDayEvs.length) return;
                calendar.batchRendering(() => state.hoverDayEvs.forEach(ev => ev.remove()));
                state.hoverDayEvs = [];
            }

            function updateTempHighlight(startStr, hoverStr) {
                const forward = startStr <= hoverStr;
                const days = eachDayStr(startStr, hoverStr);
                const first = days[0];
                const last = days[days.length - 1];

                clearTempHighlight();

                calendar.batchRendering(() => {
                    days.forEach(d => {
                        const isFirst = d === first;
                        const isLast = d === last;
                        const classes = [];

                        if (isFirst || isLast) {
                            classes.push('bg-select-endpoint');
                            if (isFirst) classes.push(forward ? 'bg-select-endpoint-left' :
                                'bg-select-endpoint-right');
                            if (isLast) classes.push(forward ? 'bg-select-endpoint-right' :
                                'bg-select-endpoint-left');
                        } else {
                            classes.push('bg-select-range');
                        }

                        const ev = calendar.addEvent({
                            start: d,
                            end: addOneDayStr(d),
                            display: 'background',
                            overlap: true,
                            classNames: classes,
                            __tempHover: true,
                        });
                        state.hoverDayEvs.push(ev);
                    });
                });
            }

            // Funcion para mostrar el SweetAlert de asignacion de vacaciones
            async function mostrarSelectorUsuario(fechaInicio, fechaFin) {
                // Cargar usuarios si no estan en cache
                if (!usuariosCache) {
                    try {
                        const response = await fetch('{{ url("/vacaciones/usuarios-con-vacaciones") }}');
                        usuariosCache = await response.json();
                    } catch (error) {
                        console.error('Error cargando usuarios:', error);
                        mostrarError('No se pudieron cargar los usuarios');
                        return;
                    }
                }

                const usuarios = usuariosCache;
                const esMismoDia = fechaInicio === fechaFin;

                const {
                    value: usuarioSeleccionado,
                    isConfirmed
                } = await Swal.fire({
                    title: null,
                    html: `
                        <div style="text-align: left;">
                            <div class="info-seleccion">
                                <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600;">Asignar Vacaciones</h3>
                                <p style="margin: 0; font-size: 14px; opacity: 0.9;">
                                    ${esMismoDia ? fechaInicio : `${fechaInicio} → ${fechaFin}`}
                                </p>
                            </div>

                            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Buscar empleado
                            </label>
                            <input type="text" id="buscador-usuarios" class="buscador-usuarios" placeholder="Escribe para filtrar..." autocomplete="off">

                            <div id="lista-usuarios" class="lista-usuarios">
                                ${generarListaUsuarios(usuarios)}
                            </div>

                            <input type="hidden" id="usuario-seleccionado-id" value="">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Asignar vacaciones',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#1e3a5f',
                    cancelButtonColor: '#6b7280',
                    width: 450,
                    padding: '20px',
                    preConfirm: () => {
                        const userId = document.getElementById('usuario-seleccionado-id').value;
                        if (!userId) {
                            Swal.showValidationMessage('Debes seleccionar un empleado');
                            return false;
                        }
                        return userId;
                    },
                    didOpen: () => {
                        const buscador = document.getElementById('buscador-usuarios');
                        const listaContainer = document.getElementById('lista-usuarios');

                        // Focus en el buscador
                        setTimeout(() => buscador.focus(), 100);

                        // Filtrar usuarios al escribir
                        buscador.addEventListener('input', (e) => {
                            const filtro = e.target.value.toLowerCase().trim();
                            const usuariosFiltrados = usuarios.filter(u =>
                                u.nombre_completo.toLowerCase().includes(filtro)
                            );
                            listaContainer.innerHTML = generarListaUsuarios(usuariosFiltrados);
                            bindClickUsuarios();
                        });

                        // Bind de clicks en usuarios
                        function bindClickUsuarios() {
                            listaContainer.querySelectorAll('.usuario-item').forEach(item => {
                                item.addEventListener('click', () => {
                                    // Quitar seleccion anterior
                                    listaContainer.querySelectorAll('.usuario-item')
                                        .forEach(i => i.classList.remove('selected'));
                                    // Anadir seleccion
                                    item.classList.add('selected');
                                    document.getElementById('usuario-seleccionado-id')
                                        .value = item.dataset.userId;
                                });
                            });
                        }
                        bindClickUsuarios();
                    }
                });

                if (!isConfirmed || !usuarioSeleccionado) return;

                // Hacer la peticion para asignar vacaciones
                try {
                    const response = await fetch('{{ url("/vacaciones/asignar-directo") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            user_id: usuarioSeleccionado,
                            fecha_inicio: fechaInicio,
                            fecha_fin: fechaFin
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Invalidar cache para refrescar contadores
                        usuariosCache = null;

                        // Refrescar calendario
                        calendar.refetchEvents();

                        Swal.fire({
                            icon: 'success',
                            title: 'Vacaciones asignadas',
                            text: data.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        mostrarError(data.error || 'No se pudieron asignar las vacaciones');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    mostrarError('Error de conexión');
                }
            }

            function generarListaUsuarios(usuarios) {
                if (usuarios.length === 0) {
                    return '<div style="padding: 20px; text-align: center; color: #6b7280;">No se encontraron empleados</div>';
                }

                return usuarios.map(u => {
                    const restantes = u.vacaciones_restantes;
                    let claseVacaciones = 'vacaciones-ok';
                    if (restantes <= 0) {
                        claseVacaciones = 'vacaciones-full';
                    } else if (restantes <= 5) {
                        claseVacaciones = 'vacaciones-warning';
                    }

                    return `
                        <div class="usuario-item" data-user-id="${u.id}">
                            <span class="usuario-nombre">${u.nombre_completo}</span>
                            <span class="usuario-vacaciones ${claseVacaciones}">
                                ${u.vacaciones_usadas}/${u.vacaciones_totales}
                            </span>
                        </div>
                    `;
                }).join('');
            }

            const calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                editable: true,
                firstDay: 1,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                events: function(info, successCallback, failureCallback) {
                    fetch('{{ url("/vacaciones/eventos") }}')
                        .then(r => r.json())
                        .then(data => successCallback(data))
                        .catch(err => {
                            console.warn('Error cargando eventos:', err);
                            successCallback(@json($eventos));
                        });
                },

                // Sistema click-and-click para seleccionar rango
                dateClick: function(info) {
                    const clicked = info.dateStr;

                    if (!state.startClick) {
                        // Primer clic
                        state.startClick = clicked;
                        updateTempHighlight(clicked, clicked);
                        return;
                    }

                    // Segundo clic
                    const startStr = clicked < state.startClick ? clicked : state.startClick;
                    const endStr = clicked < state.startClick ? state.startClick : clicked;

                    clearTempHighlight();
                    state.startClick = null;

                    // Mostrar selector de usuario
                    mostrarSelectorUsuario(startStr, endStr);
                },

                eventDrop: function(info) {
                    const fecha = info.event.startStr;
                    const idUsuario = info.event.extendedProps.user_id;

                    if (!idUsuario) {
                        // Es un festivo, no se puede mover
                        info.revert();
                        return;
                    }

                    fetch('{{ url("/vacaciones/reprogramar") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                user_id: idUsuario,
                                fecha_original: info.oldEvent.startStr,
                                nueva_fecha: fecha
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                mostrarError(data.error || "No se pudo reprogramar");
                                info.revert();
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            mostrarError("Error de conexión");
                            info.revert();
                        });
                },

                eventClick: function(info) {
                    // Si hay seleccion activa, cancelarla
                    if (state.startClick) {
                        clearTempHighlight();
                        state.startClick = null;
                        return;
                    }

                    // Clic izquierdo: ir a ficha de usuario
                    const userId = info.event.extendedProps.user_id;
                    if (userId) {
                        window.location.href = `/users/${userId}`;
                    }
                },

                eventDidMount: function(info) {
                    const userId = info.event.extendedProps.user_id;
                    if (!userId) return; // Es un festivo, no hacer nada

                    // Clic derecho: confirmar eliminar vacaciones
                    info.el.addEventListener('contextmenu', function(ev) {
                        ev.preventDefault();

                        Swal.fire({
                            title: `Eliminar vacaciones de ${info.event.title}?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Si, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then(result => {
                            if (result.isConfirmed) {
                                fetch('{{ url("/vacaciones/eliminar-evento") }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': token
                                        },
                                        body: JSON.stringify({
                                            user_id: userId,
                                            fecha: info.event.startStr
                                        })
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            info.event.remove();
                                            // Invalidar cache
                                            usuariosCache = null;
                                            Swal.fire('Eliminado',
                                                'Vacaciones eliminadas correctamente.',
                                                'success');
                                        } else {
                                            mostrarError(data.error || "No se pudo eliminar");
                                        }
                                    })
                                    .catch(() => {
                                        mostrarError("Error de conexión");
                                    });
                            }
                        });
                    });
                }
            });

            // Bind hover para mostrar preview del rango
            function bindHoverCells() {
                const cells = el.querySelectorAll('.fc-daygrid-day');
                cells.forEach(cell => {
                    cell.addEventListener('mouseenter', () => {
                        if (!state.startClick) return;
                        const day = cell.getAttribute('data-date');
                        if (day) updateTempHighlight(state.startClick, day);
                    });
                });
            }

            calendar.on('datesSet', bindHoverCells);

            // Cancelar seleccion con ESC
            document.addEventListener('keydown', (ev) => {
                if (ev.key === 'Escape' && state.startClick) {
                    state.startClick = null;
                    clearTempHighlight();
                }
            });

            el._calendar = calendar;
            calendar.render();
            bindHoverCells();
        }

        function loadFullCalendarIfNeeded() {
            return new Promise((resolve) => {
                if (typeof FullCalendar !== 'undefined') {
                    resolve();
                    return;
                }

                console.log('Cargando FullCalendar dinamicamente...');

                // Cargar CSS
                if (!document.querySelector('link[href*="fullcalendar"]')) {
                    const css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css';
                    document.head.appendChild(css);
                }

                // Cargar JS principal
                const loadScript = (src) => {
                    return new Promise((res) => {
                        if (document.querySelector(`script[src="${src}"]`)) {
                            res();
                            return;
                        }
                        const script = document.createElement('script');
                        script.src = src;
                        script.onload = res;
                        document.head.appendChild(script);
                    });
                };

                loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js')
                    .then(() => loadScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'))
                    .then(() => {
                        // Esperar un momento para que se inicialice
                        setTimeout(resolve, 50);
                    });
            });
        }

        function initVacacionesPage() {
            // Prevenir doble inicializacion
            if (document.body.dataset.vacacionesPageInit === 'true') return;

            // Verificar que el contenedor exista
            if (!document.getElementById('calendario-vacaciones')) return;

            console.log('Inicializando pagina de vacaciones...');

            // Marcar como inicializado inmediatamente para evitar duplicados
            document.body.dataset.vacacionesPageInit = 'true';

            // Cargar FullCalendar si es necesario y luego inicializar
            loadFullCalendarIfNeeded().then(() => {
                inicializarCalendario();
            });
        }

        // Registrar en el sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initVacacionesPage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initVacacionesPage);
        document.addEventListener('DOMContentLoaded', initVacacionesPage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.vacacionesPageInit = 'false';
        });
    </script>


</x-app-layout>
