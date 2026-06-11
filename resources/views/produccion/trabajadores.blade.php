<x-app-layout>
    <x-slot name="title">Planificacion Trabajadores</x-slot>

    @push('calendar')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
        <script src="https://unpkg.com/@popperjs/core@2"></script>
        <script src="https://unpkg.com/tippy.js@6"></script>
        <style>
            .fc { width: 100% !important; font-family: 'Inter', system-ui, sans-serif; }
            .fc .fc-toolbar { padding: 1rem; background: #111827; border-radius: 12px 12px 0 0; margin-bottom: 0 !important; }
            .fc .fc-toolbar-title { color: white !important; font-weight: 700; font-size: 1.25rem; text-transform: capitalize; }
            .fc .fc-button { background: rgba(255,255,255,0.1) !important; border: 1px solid rgba(255,255,255,0.2) !important; color: white !important; font-weight: 500; padding: 0.5rem 1rem; border-radius: 8px !important; }
            .fc .fc-button:hover { background: rgba(255,255,255,0.2) !important; }
            .fc .fc-button-active { background: #3b82f6 !important; border-color: #3b82f6 !important; }
            .fc .fc-view-harness { border-radius: 0 0 12px 12px; overflow: hidden; border: 1px solid #e2e8f0; border-top: none; }
            .fc .fc-resource-area { background: #f8fafc; }
            .fc .fc-datagrid-cell-frame { padding: 0.5rem; }
            .fc .fc-timeline-lane:hover { background-color: rgba(59, 130, 246, 0.05); }
            .fc .fc-day-today { background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%) !important; }
            .fc .fc-event { border-radius: 6px; border: none !important; padding: 2px 4px; font-size: 0.75rem; font-weight: 500; margin: 1px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: grab; min-height: 32px; }
            .fc .fc-event:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.15); z-index: 10; }
            .fc .fc-event:active { cursor: grabbing; }
            .fc .fc-event.fc-event-dragging { opacity: 0.7; box-shadow: 0 8px 16px rgba(0,0,0,0.2); }
            .fc .fc-timeline-slot-label { font-weight: 600; color: #475569; text-transform: capitalize; font-size: 0.8rem; }

            /* Menu contextual */
            .ctx-menu { position: fixed; z-index: 9999; min-width: 220px; background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-radius: 8px; overflow: hidden; }
            .ctx-menu-header { padding: 10px 12px; font-size: 13px; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
            .ctx-menu-item { display: flex; align-items: center; gap: 8px; padding: 10px 12px; font-size: 14px; background: white; border: none; width: 100%; text-align: left; cursor: pointer; }
            .ctx-menu-item:hover { background: #f3f4f6; }
            .ctx-menu-danger { color: #b91c1c; }
            .ctx-menu-danger:hover { background: #fee2e2; }

            /* Tooltip */
            .tippy-box[data-theme~="worker"] { background: white; color: #1f2937; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
            .tippy-box[data-theme~="worker"] .tippy-content { padding: 0; }
        </style>
    @endpush

    <div class="py-2" id="calendario-container">
        <div class="px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <input type="text" id="filtro-eventos" placeholder="Buscar trabajador..."
                   class="w-64 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-600 dark:text-gray-400">Turnos:</span>
                @foreach($turnos as $turno)
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full" style="background: {{ $turno->color ?? '#93C5FD' }}"></span>
                        <span class="text-xs font-medium">{{ $turno->nombre }}</span>
                    </span>
                @endforeach
            </div>
        </div>
        <div class="w-full bg-white dark:bg-gray-800">
            <div id="calendario" class="w-full" style="height: calc(100vh - 140px);"></div>
        </div>
    </div>

    @php
        $config = [
            'csrf' => csrf_token(),
            'routes' => [
                'userShow' => route('users.show', ['user' => ':id']),
                'eliminarAsignacion' => route('planificacion.eliminarAsignacion'),
                'actualizarFichaje' => route('planificacion.actualizarFichaje'),
                'crearFestivo' => route('festivos.store'),
                'crearAsignacion' => route('planificacion.crearAsignacion'),
                'moverAsignacion' => route('planificacion.moverAsignacion'),
                'datosFormulario' => route('planificacion.datosFormulario'),
                'datosCalendario' => route('planificacion.datosCalendario'),
            ],
        ];
    @endphp

    @push('scripts')
    <script data-navigate-reload>
    (function() {
        const CONFIG = @json($config);
        let menuActual = null;
        let datosCalendario = null; // Se carga via AJAX

        function cerrarMenu() {
            if (menuActual) { menuActual.remove(); menuActual = null; }
            document.removeEventListener('click', cerrarMenu);
        }

        function abrirMenu(x, y, html) {
            cerrarMenu();
            const el = document.createElement('div');
            el.className = 'ctx-menu';
            el.style.top = y + 'px';
            el.style.left = x + 'px';
            el.innerHTML = html;
            document.body.appendChild(el);
            menuActual = el;
            setTimeout(() => document.addEventListener('click', cerrarMenu), 0);
            return el;
        }

        // Menu para evento (turno asignado) - solo eliminar
        function menuEvento(x, y, event, calendar) {
            const p = event.extendedProps || {};
            if (p.es_festivo) return;

            const turnoNombre = p.turno_nombre || event.title;

            const el = abrirMenu(x, y, `
                <div class="ctx-menu-header">
                    <div class="font-semibold">${turnoNombre}</div>
                    <div class="text-xs text-gray-500">${p.hora_inicio || ''} - ${p.hora_fin || ''}</div>
                </div>
                <button class="ctx-menu-item ctx-menu-danger" data-action="eliminar">
                    <span>🗑️</span> Eliminar turno
                </button>
            `);

            el.querySelector('[data-action="eliminar"]').onclick = async () => {
                cerrarMenu();
                const ok = await Swal.fire({
                    icon: 'warning',
                    title: 'Eliminar turno',
                    text: '¿Seguro que quieres eliminar esta asignación?',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    confirmButtonColor: '#b91c1c'
                }).then(r => r.isConfirmed);
                if (!ok) return;

                try {
                    const res = await fetch(CONFIG.routes.eliminarAsignacion, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf },
                        body: JSON.stringify({ asignacion_id: p.asignacion_id })
                    });
                    if (res.ok) {
                        event.remove();
                        Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1200, showConfirmButton: false });
                    }
                } catch (e) { console.error(e); }
            };
        }

        async function inicializarCalendario() {
            const el = document.getElementById('calendario');
            if (!el || typeof FullCalendar === 'undefined') return;

            if (window.calendarioPlanif) { try { window.calendarioPlanif.destroy(); } catch(e){} }

            // Cargar datos frescos via AJAX
            try {
                const response = await fetch(CONFIG.routes.datosCalendario);
                datosCalendario = await response.json();
            } catch (e) {
                console.error('Error cargando datos del calendario:', e);
                return;
            }

            // Ordenar turnos por hora de inicio para vista diaria
            const turnosOrdenados = [...datosCalendario.turnos].sort((a, b) => {
                const horaA = a.hora_inicio || '00:00';
                const horaB = b.hora_inicio || '00:00';
                return horaA.localeCompare(horaB);
            });

            // Crear mapa de horas a turnos para labels
            const horasATurno = {};
            turnosOrdenados.forEach(t => {
                if (t.hora_inicio) {
                    const hora = t.hora_inicio.substring(0, 5);
                    horasATurno[hora] = t;
                }
            });

            // Encontrar el turno que corresponde a una hora dada
            function getTurnoParaHora(horaStr) {
                for (const turno of turnosOrdenados) {
                    const inicio = turno.hora_inicio?.substring(0, 5) || '00:00';
                    const fin = turno.hora_fin?.substring(0, 5) || '23:59';

                    // Caso normal: inicio < fin
                    if (inicio <= fin) {
                        if (horaStr >= inicio && horaStr < fin) return turno;
                    } else {
                        // Turno nocturno: cruza medianoche (ej: 22:00 - 06:00)
                        if (horaStr >= inicio || horaStr < fin) return turno;
                    }
                }
                return turnosOrdenados[0] || null;
            }

            // Calcular hora de inicio del primer turno
            const primeraHora = turnosOrdenados[0]?.hora_inicio?.substring(0, 5) || '06:00';

            // Calcular duracion promedio de turnos
            const numTurnos = datosCalendario.turnos.length || 3;
            const horasPorSlot = Math.max(1, Math.floor(24 / numTurnos));
            const slotDurationDay = `${String(horasPorSlot).padStart(2, '0')}:00:00`;

            const calendar = new FullCalendar.Calendar(el, {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'es',
                initialView: localStorage.getItem('vistaPlanif') || 'resourceTimelineWeek',
                firstDay: 1,
                height: 'auto',
                editable: true,
                eventResourceEditable: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineWeek'
                },
                buttonText: { today: 'Hoy', week: 'Semana', day: 'Dia' },
                datesSet(info) { localStorage.setItem('vistaPlanif', info.view.type); },
                slotMinWidth: 30,
                views: {
                    resourceTimelineDay: {
                        slotDuration: '01:00:00',
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
                        slotLabelInterval: '01:00:00',
                        slotMinWidth: 25,
                        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                        slotLabelContent: function(arg) {
                            const hora = arg.text;
                            const turno = getTurnoParaHora(hora);
                            // Solo mostrar label completo en la hora de inicio del turno
                            if (turno && turno.hora_inicio?.substring(0,5) === hora) {
                                return {
                                    html: `<div class="text-center">
                                        <div class="font-bold text-[10px]" style="color: ${turno.color}">${turno.nombre}</div>
                                        <div class="text-[9px] text-gray-500">${turno.hora_inicio?.substring(0,5)}-${turno.hora_fin?.substring(0,5)}</div>
                                    </div>`
                                };
                            }
                            // Para otras horas, solo mostrar la hora corta
                            return { html: `<span class="text-[10px] text-gray-400">${hora.substring(0,2)}</span>` };
                        }
                    },
                    resourceTimelineWeek: { slotDuration: { days: 1 }, slotLabelFormat: { weekday: 'short', day: 'numeric' } }
                },
                resources: datosCalendario.recursos,
                resourceOrder: 'orden',
                resourceAreaWidth: '210px',
                resourceAreaColumns: [{
                    field: 'title',
                    headerContent: 'Trabajadores',
                    cellContent(arg) {
                        const props = arg.resource.extendedProps || {};
                        const a = document.createElement('a');
                        a.href = CONFIG.routes.userShow.replace(':id', arg.resource.id);
                        a.title = 'Ver ficha de ' + arg.resource.title;
                        a.className = 'flex items-center gap-2 group cursor-pointer';

                        let avatar;
                        if (props.foto) {
                            avatar = document.createElement('img');
                            avatar.src = props.foto;
                            avatar.alt = '';
                            avatar.loading = 'lazy';
                            avatar.className = 'w-7 h-7 rounded-full object-cover border border-gray-200 flex-shrink-0';
                        } else {
                            avatar = document.createElement('div');
                            avatar.className = 'w-7 h-7 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-xs font-bold flex-shrink-0';
                            avatar.textContent = (arg.resource.title || '?').charAt(0).toUpperCase();
                        }

                        const texto = document.createElement('div');
                        texto.className = 'min-w-0 leading-tight';
                        const nombre = document.createElement('div');
                        nombre.className = 'text-blue-700 dark:text-blue-400 group-hover:underline font-medium text-sm truncate';
                        nombre.textContent = arg.resource.title;
                        texto.appendChild(nombre);
                        if (props.categoria) {
                            const cat = document.createElement('div');
                            cat.className = 'text-[10px] text-gray-500 truncate';
                            cat.textContent = props.categoria;
                            texto.appendChild(cat);
                        }

                        a.appendChild(avatar);
                        a.appendChild(texto);
                        return { domNodes: [a] };
                    }
                }],
                events: datosCalendario.eventos,
                eventDidMount(info) {
                    const p = info.event.extendedProps || {};
                    if (p.es_festivo) return;

                    if (typeof tippy !== 'undefined') {
                        const fichaje2 = (p.entrada2 || p.salida2)
                            ? `<div class="text-sm"><b>Fichaje 2:</b> ${p.entrada2 || '--'} / ${p.salida2 || '--'}</div>`
                            : '';
                        const fotoHtml = p.foto
                            ? `<img src="${p.foto}" alt="" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">`
                            : `<div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center text-gray-500 text-lg font-bold">${(p.turno_nombre || '?')[0].toUpperCase()}</div>`;
                        tippy(info.el, {
                            content: `<div class="p-3">
                                <div class="flex items-center gap-3 mb-2">
                                    ${fotoHtml}
                                    <div>
                                        <div class="font-bold">${p.turno_nombre || ''}</div>
                                        <div class="text-xs text-gray-500">${p.hora_inicio || ''} - ${p.hora_fin || ''}</div>
                                    </div>
                                </div>
                                <div class="text-sm"><b>Fichaje:</b> ${p.entrada || '--'} / ${p.salida || '--'}</div>
                                ${fichaje2}
                            </div>`,
                            allowHTML: true,
                            theme: 'worker',
                            placement: 'top'
                        });
                    }

                    // Clic derecho en evento
                    info.el.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        menuEvento(e.clientX, e.clientY, info.event, calendar);
                    });
                },
                eventClick(info) {
                    const p = info.event.extendedProps || {};
                    if (!p.es_festivo && p.user_id) {
                        window.location.href = CONFIG.routes.userShow.replace(':id', p.user_id);
                    }
                },
                dateClick(info) {
                    // Clic izquierdo en celda vacia - no hacer nada
                },
                eventContent(arg) {
                    const p = arg.event.extendedProps || {};
                    if (p.es_festivo) {
                        return { html: `<div class="px-2 py-1 text-xs font-bold">${arg.event.title}</div>` };
                    }

                    // Estado especial (vacaciones, baja, etc.)
                    if (p.estado && p.estado !== 'activo') {
                        return {
                            html: `<div class="flex items-center justify-center h-full px-2">
                                <span class="text-xs font-semibold">${p.entrada || p.estado}</span>
                            </div>`
                        };
                    }

                    // Horas del turno establecidas
                    const horaInicio = p.hora_inicio || '--';
                    const horaFin = p.hora_fin || '--';

                    return {
                        html: `<div class="flex items-center justify-center h-full px-1">
                            <div class="text-xs font-semibold">${horaInicio} - ${horaFin}</div>
                        </div>`
                    };
                },
                async eventDrop(info) {
                    const p = info.event.extendedProps || {};

                    // No permitir mover festivos
                    if (p.es_festivo) {
                        info.revert();
                        return;
                    }

                    // No permitir cambiar de trabajador (fila)
                    if (info.newResource && info.newResource.id != p.user_id) {
                        info.revert();
                        Swal.fire({
                            icon: 'warning',
                            title: 'No permitido',
                            text: 'No puedes mover una asignacion a otro trabajador',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        return;
                    }

                    // Obtener nueva fecha
                    const nuevaFecha = info.event.start.toISOString().slice(0, 10);

                    // Determinar turno basado en la hora (en vista diaria)
                    let nuevoTurnoId = null;
                    if (info.view.type === 'resourceTimelineDay') {
                        const horaStart = info.event.start.toTimeString().slice(0, 5);
                        const turnoEncontrado = getTurnoParaHora(horaStart);
                        if (turnoEncontrado) {
                            nuevoTurnoId = turnoEncontrado.id;
                        }
                    }

                    try {
                        const res = await fetch(CONFIG.routes.moverAsignacion, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf },
                            body: JSON.stringify({
                                asignacion_id: p.asignacion_id,
                                fecha: nuevaFecha,
                                turno_id: nuevoTurnoId
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            // Actualizar extendedProps
                            if (nuevoTurnoId) {
                                info.event.setExtendedProp('turno_id', data.asignacion.turno_id);
                                info.event.setExtendedProp('turno_nombre', data.asignacion.turno_nombre);
                                info.event.setExtendedProp('hora_inicio', data.asignacion.turno_hora_inicio);
                                info.event.setExtendedProp('hora_fin', data.asignacion.turno_hora_fin);
                                // Actualizar colores y titulo
                                info.event.setProp('backgroundColor', data.asignacion.color.bg);
                                info.event.setProp('borderColor', data.asignacion.color.border);
                                info.event.setProp('title', data.asignacion.turno_nombre);
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Asignacion movida',
                                timer: 1200,
                                showConfirmButton: false
                            });
                        } else {
                            info.revert();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo mover la asignacion'
                            });
                        }
                    } catch (e) {
                        console.error(e);
                        info.revert();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al mover la asignacion'
                        });
                    }
                },
                eventResize(info) {
                    // No permitir redimensionar, revertir
                    info.revert();
                }
            });

            calendar.render();
            window.calendarioPlanif = calendar;

            // Filtro de busqueda
            const filtro = document.getElementById('filtro-eventos');
            if (filtro) {
                filtro.addEventListener('input', function() {
                    const texto = this.value.toLowerCase();
                    calendar.getEvents().forEach(ev => {
                        const titulo = (ev.title || '').toLowerCase();
                        const cat = (ev.extendedProps?.categoria || '').toLowerCase();
                        const visible = !texto || titulo.includes(texto) || cat.includes(texto);
                        ev.setProp('display', visible ? 'auto' : 'none');
                    });
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarCalendario);
        } else {
            inicializarCalendario();
        }
        document.addEventListener('livewire:navigated', inicializarCalendario);
    })();
    </script>
    @endpush
</x-app-layout>
