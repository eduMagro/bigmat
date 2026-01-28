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
        <div class="px-4 py-2 bg-white border-b flex items-center justify-between">
            <input type="text" id="filtro-eventos" placeholder="Buscar trabajador..."
                   class="w-64 border border-gray-300 rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300 focus:border-blue-500">
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500">Turnos:</span>
                @foreach($turnos as $turno)
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full" style="background: {{ $turno->color ?? '#93C5FD' }}"></span>
                        <span class="text-xs font-medium">{{ $turno->nombre }}</span>
                    </span>
                @endforeach
            </div>
        </div>
        <div class="w-full bg-white">
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

        // Menu para evento (turno asignado)
        function menuEvento(x, y, event, calendar) {
            const p = event.extendedProps || {};
            if (p.es_festivo) return;

            const turnoNombre = p.turno_nombre || event.title;
            const entrada = p.entrada || '--';
            const salida = p.salida || '--';

            const fichaje2Text = (p.entrada2 || p.salida2) ? ` | 2º: ${p.entrada2 || '--'}/${p.salida2 || '--'}` : '';
            const el = abrirMenu(x, y, `
                <div class="ctx-menu-header">
                    <div class="font-semibold">${turnoNombre}</div>
                    <div class="text-xs text-gray-500">${p.hora_inicio || ''} - ${p.hora_fin || ''}</div>
                </div>
                <button class="ctx-menu-item" data-action="ver-perfil">
                    <span>👤</span> Ver perfil
                </button>
                <button class="ctx-menu-item" data-action="editar-fichaje">
                    <span>✏️</span> Fichajes: ${entrada}/${salida}${fichaje2Text}
                </button>
                <button class="ctx-menu-item ctx-menu-danger" data-action="eliminar">
                    <span>🗑️</span> Eliminar registro
                </button>
            `);

            el.querySelector('[data-action="ver-perfil"]').onclick = () => {
                cerrarMenu();
                if (p.user_id) window.location.href = CONFIG.routes.userShow.replace(':id', p.user_id);
            };

            el.querySelector('[data-action="editar-fichaje"]').onclick = async () => {
                cerrarMenu();
                const { value } = await Swal.fire({
                    title: 'Editar fichajes',
                    html: `
                        <div class="text-left space-y-3">
                            <div class="font-medium text-gray-700 mb-2">Primer tramo</div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-sm font-medium mb-1">Entrada</label>
                                <input type="time" id="swal-entrada" value="${p.entrada && p.entrada !== '--' ? p.entrada : ''}" class="w-full border rounded px-3 py-2"></div>
                                <div><label class="block text-sm font-medium mb-1">Salida</label>
                                <input type="time" id="swal-salida" value="${p.salida && p.salida !== '--' ? p.salida : ''}" class="w-full border rounded px-3 py-2"></div>
                            </div>
                            <div class="border-t pt-3 mt-3">
                                <div class="font-medium text-gray-700 mb-2">Segundo tramo (turno partido)</div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="block text-sm font-medium mb-1">Entrada 2</label>
                                    <input type="time" id="swal-entrada2" value="${p.entrada2 || ''}" class="w-full border rounded px-3 py-2"></div>
                                    <div><label class="block text-sm font-medium mb-1">Salida 2</label>
                                    <input type="time" id="swal-salida2" value="${p.salida2 || ''}" class="w-full border rounded px-3 py-2"></div>
                                </div>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => ({
                        entrada: document.getElementById('swal-entrada').value || null,
                        salida: document.getElementById('swal-salida').value || null,
                        entrada2: document.getElementById('swal-entrada2').value || null,
                        salida2: document.getElementById('swal-salida2').value || null
                    })
                });
                if (!value) return;

                try {
                    const res = await fetch(CONFIG.routes.actualizarFichaje, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf },
                        body: JSON.stringify({ asignacion_id: p.asignacion_id, ...value })
                    });
                    if (res.ok) {
                        event.setExtendedProp('entrada', value.entrada || '--');
                        event.setExtendedProp('salida', value.salida || '--');
                        event.setExtendedProp('entrada2', value.entrada2 || null);
                        event.setExtendedProp('salida2', value.salida2 || null);
                        Swal.fire({ icon: 'success', title: 'Fichaje actualizado', timer: 1200, showConfirmButton: false });
                    }
                } catch (e) { console.error(e); }
            };

            el.querySelector('[data-action="eliminar"]').onclick = async () => {
                cerrarMenu();
                const ok = await Swal.fire({
                    icon: 'warning',
                    title: 'Eliminar registro',
                    text: '¿Seguro que quieres eliminar esta asignacion?',
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

        // Copiar registros de un dia a otro
        async function copiarRegistrosDia(fromISO, toISO, calendar) {
            const ok = await Swal.fire({
                icon: 'question',
                title: 'Copiar registros',
                html: `¿Copiar registros de <b>${fromISO}</b> a <b>${toISO}</b>?`,
                showCancelButton: true,
                confirmButtonText: 'Copiar',
                cancelButtonText: 'Cancelar'
            }).then(r => r.isConfirmed);
            if (!ok) return;

            const evs = calendar.getEvents().filter(ev => !ev.extendedProps?.es_festivo);

            const yaExiste = (title, resourceId, fechaISO) => {
                return evs.some(ev => {
                    const rId = ev.getResources?.()[0]?.id ?? ev.extendedProps?.resourceId ?? null;
                    return ev.title === title && String(rId) === String(resourceId) &&
                           (ev.startStr || ev.start?.toISOString()).slice(0, 10) === fechaISO;
                });
            };

            const delOrigen = evs.filter(ev => (ev.startStr || ev.start?.toISOString()).slice(0, 10) === fromISO);

            let creados = 0;
            for (const ev of delOrigen) {
                const res = ev.getResources ? ev.getResources() : [];
                const resourceId = res?.[0]?.id ?? ev.extendedProps?.resourceId ?? null;

                if (yaExiste(ev.title, resourceId, toISO)) continue;

                calendar.addEvent({
                    id: `tmp-copy-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                    title: ev.title,
                    start: toISO,
                    end: toISO,
                    resourceId: resourceId ?? undefined,
                    backgroundColor: ev.backgroundColor,
                    borderColor: ev.borderColor,
                    textColor: ev.textColor,
                    extendedProps: { ...ev.extendedProps, asignacion_id: null }
                });
                creados++;
            }

            Swal.fire({
                icon: 'success',
                title: 'Copiado completado',
                html: `Se han copiado <b>${creados}</b> registros a ${toISO}.`,
                timer: 1400,
                showConfirmButton: false
            });
        }

        // Menu para celda vacia
        function menuCelda(x, y, fechaISO, resourceId, calendar) {
            // resourceId ahora es el user_id (trabajador)
            const trabajador = datosCalendario.recursos.find(r => r.id == resourceId);
            const trabajadorNombre = trabajador?.title || 'Trabajador';

            // Calcular fechas vecinas
            const prevDate = new Date(fechaISO);
            prevDate.setDate(prevDate.getDate() - 1);
            const nextDate = new Date(fechaISO);
            nextDate.setDate(nextDate.getDate() + 1);
            const prevISO = prevDate.toISOString().slice(0, 10);
            const nextISO = nextDate.toISOString().slice(0, 10);

            const el = abrirMenu(x, y, `
                <div class="ctx-menu-header">
                    <div>${trabajadorNombre}</div>
                    <div class="text-xs text-gray-500">${fechaISO}</div>
                </div>
                <button class="ctx-menu-item" data-action="crear-asignacion">
                    <span>➕</span> Asignar turno
                </button>
                <button class="ctx-menu-item" data-action="crear-festivo">
                    <span>📅</span> Crear festivo este dia
                </button>
                <button class="ctx-menu-item" data-action="copiar-anterior">
                    <span>⬅️</span> Copiar del dia anterior (${prevISO})
                </button>
                <button class="ctx-menu-item" data-action="copiar-siguiente">
                    <span>➡️</span> Copiar del dia siguiente (${nextISO})
                </button>
            `);

            el.querySelector('[data-action="crear-asignacion"]').onclick = async () => {
                cerrarMenu();

                if (!resourceId) {
                    Swal.fire({ icon: 'warning', title: 'Sin trabajador', text: 'Debes hacer clic en la fila de un trabajador' });
                    return;
                }

                try {
                    // Obtener turnos disponibles
                    const res = await fetch(CONFIG.routes.datosFormulario);
                    const data = await res.json();

                    if (!data.turnos?.length) {
                        Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'No hay turnos disponibles' });
                        return;
                    }

                    // Crear opciones para el select de turnos
                    const optsTurnos = data.turnos.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');

                    const { value: formValues } = await Swal.fire({
                        title: 'Asignar turno',
                        html: `
                            <div class="text-left space-y-4">
                                <div class="p-3 bg-gray-50 rounded-lg mb-2">
                                    <div class="text-xs text-gray-500">Trabajador</div>
                                    <div class="font-semibold">${trabajadorNombre}</div>
                                    <div class="text-xs text-gray-500 mt-2">Fecha</div>
                                    <div class="font-semibold">${fechaISO}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Turno</label>
                                    <select id="swal-turno" class="w-full border rounded px-3 py-2">
                                        <option value="">-- Seleccionar turno --</option>
                                        ${optsTurnos}
                                    </select>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Asignar',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => {
                            const turnoId = document.getElementById('swal-turno').value;
                            if (!turnoId) {
                                Swal.showValidationMessage('Debes seleccionar un turno');
                                return false;
                            }
                            return { user_id: resourceId, turno_id: turnoId, fecha: fechaISO };
                        }
                    });

                    if (!formValues) return;

                    // Crear la asignacion
                    const createRes = await fetch(CONFIG.routes.crearAsignacion, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf },
                        body: JSON.stringify(formValues)
                    });
                    const createData = await createRes.json();

                    if (createData.success) {
                        const a = createData.asignacion;
                        // Calcular start y end con las horas del turno
                        const startDateTime = a.fecha + 'T' + a.turno_hora_inicio + ':00';
                        let endDateTime;
                        if (a.turno_hora_fin < a.turno_hora_inicio) {
                            // Turno nocturno: termina al dia siguiente
                            const fechaObj = new Date(a.fecha);
                            fechaObj.setDate(fechaObj.getDate() + 1);
                            const fechaSiguiente = fechaObj.toISOString().slice(0, 10);
                            endDateTime = fechaSiguiente + 'T' + a.turno_hora_fin + ':00';
                        } else {
                            endDateTime = a.fecha + 'T' + a.turno_hora_fin + ':00';
                        }

                        calendar.addEvent({
                            id: 'asig-' + a.id,
                            title: a.turno_nombre,
                            start: startDateTime,
                            end: endDateTime,
                            resourceId: a.user_id,
                            backgroundColor: a.color?.bg || '#93C5FD',
                            borderColor: a.color?.border || '#60A5FA',
                            textColor: '#000000',
                            extendedProps: {
                                asignacion_id: a.id,
                                user_id: a.user_id,
                                turno_id: a.turno_id,
                                turno_nombre: a.turno_nombre,
                                estado: 'activo',
                                entrada: '--',
                                salida: '--',
                                categoria: a.categoria,
                                foto: a.foto,
                                hora_inicio: a.turno_hora_inicio,
                                hora_fin: a.turno_hora_fin
                            }
                        });
                        Swal.fire({ icon: 'success', title: 'Asignacion creada', timer: 1200, showConfirmButton: false });
                    } else {
                        mostrarError(createData.message || 'No se pudo crear la asignación');
                    }
                } catch (e) {
                    console.error(e);
                    mostrarError('Error al crear la asignación');
                }
            };

            el.querySelector('[data-action="crear-festivo"]').onclick = async () => {
                cerrarMenu();
                const { value: titulo } = await Swal.fire({
                    title: 'Crear festivo',
                    input: 'text',
                    inputLabel: 'Nombre del festivo',
                    inputPlaceholder: 'Ej: Navidad',
                    showCancelButton: true,
                    confirmButtonText: 'Crear'
                });
                if (!titulo) return;

                try {
                    const res = await fetch(CONFIG.routes.crearFestivo, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf },
                        body: JSON.stringify({ titulo, fecha: fechaISO })
                    });
                    const data = await res.json();
                    if (data.id || data.festivo) {
                        const festivo = data.festivo || data;
                        const resourceIds = datosCalendario.recursos.map(r => r.id);
                        calendar.addEvent({
                            id: 'festivo-' + festivo.id,
                            title: festivo.titulo,
                            start: fechaISO,
                            end: fechaISO,
                            resourceIds,
                            backgroundColor: '#ef4444',
                            borderColor: '#dc2626',
                            textColor: '#fff',
                            classNames: ['evento-festivo'],
                            extendedProps: { es_festivo: true, festivo_id: festivo.id }
                        });
                        Swal.fire({ icon: 'success', title: 'Festivo creado', timer: 1200, showConfirmButton: false });
                    }
                } catch (e) { console.error(e); }
            };

            el.querySelector('[data-action="copiar-anterior"]').onclick = () => {
                cerrarMenu();
                copiarRegistrosDia(prevISO, fechaISO, calendar);
            };

            el.querySelector('[data-action="copiar-siguiente"]').onclick = () => {
                cerrarMenu();
                copiarRegistrosDia(nextISO, fechaISO, calendar);
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
                resourceAreaWidth: '180px',
                resourceAreaColumns: [{ field: 'title', headerContent: 'Trabajadores' }],
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

                    // Foto del trabajador
                    const fotoHtml = p.foto
                        ? `<img src="${p.foto}" alt="" class="w-6 h-6 rounded-full object-cover border border-white/50 flex-shrink-0">`
                        : `<div class="w-6 h-6 rounded-full bg-white/30 flex items-center justify-center text-[10px] font-bold flex-shrink-0">${(arg.event.title || '?')[0].toUpperCase()}</div>`;

                    const estado = p.estado !== 'activo' ? ` <span class="text-[9px] opacity-70">(${p.estado})</span>` : '';

                    // Fichajes - siempre mostrar
                    const entrada1 = p.entrada || '--';
                    const salida1 = p.salida || '--';
                    const fichaje1 = `${entrada1}/${salida1}`;
                    const fichaje2 = (p.entrada2 || p.salida2)
                        ? ` | ${p.entrada2 || '--'}/${p.salida2 || '--'}`
                        : '';

                    return {
                        html: `<div class="flex items-center gap-1.5 px-1 py-0.5 h-full">
                            ${fotoHtml}
                            <div class="flex flex-col justify-center min-w-0 leading-tight">
                                <div class="text-xs font-semibold truncate">${arg.event.title}${estado}</div>
                                <div class="text-[10px] opacity-80 truncate">${fichaje1}${fichaje2}</div>
                            </div>
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

            // Clic derecho en celda vacia - detectar fecha y recurso
            el.addEventListener('contextmenu', (e) => {
                // Verificar que estamos en una celda del timeline
                const lane = e.target.closest('.fc-timeline-lane');
                if (!lane) return;

                e.preventDefault();

                // Obtener el resourceId del lane
                const resourceLane = e.target.closest('[data-resource-id]');
                const resourceId = resourceLane?.dataset?.resourceId || null;

                // Obtener la fecha de la posicion del click
                let fechaISO = null;
                const rect = el.getBoundingClientRect();
                const scrollContainer = el.querySelector('.fc-timeline-body');
                const timelineSlots = el.querySelectorAll('.fc-timeline-slot[data-date]');

                // Buscar el slot que corresponde a la posicion X del click
                for (const slot of timelineSlots) {
                    const slotRect = slot.getBoundingClientRect();
                    if (e.clientX >= slotRect.left && e.clientX <= slotRect.right) {
                        fechaISO = slot.dataset.date;
                        break;
                    }
                }

                // Fallback: usar la fecha actual de la vista si no se encuentra
                if (!fechaISO) {
                    fechaISO = calendar.view?.currentStart?.toISOString().slice(0, 10);
                }

                if (fechaISO) {
                    menuCelda(e.clientX, e.clientY, fechaISO, resourceId, calendar);
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
