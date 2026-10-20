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

            /* Movil: toolbar del calendario y leyenda compactas */
            @media (max-width: 900px) {
                .fc .fc-toolbar { flex-wrap: wrap; gap: 6px; padding: 0.5rem; border-radius: 0; }
                .fc .fc-toolbar-title { font-size: 0.95rem !important; }
                .fc .fc-button { padding: 0.3rem 0.6rem; font-size: 0.75rem; }
                #calendario-toolbar { padding: 0.375rem 0.75rem; }
                #leyenda-turnos { gap: 0.5rem 0.625rem; }
            }

            /* Movil en vertical: aviso de girar el dispositivo (la planificacion solo se usa apaisada) */
            #aviso-girar { display: none; }
            @media screen and (max-width: 767px) and (orientation: portrait) {
                #calendario-container { display: none; }
                #aviso-girar {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 1rem;
                    min-height: calc(100vh - 120px);
                    padding: 2rem;
                    text-align: center;
                }
                #aviso-girar svg { animation: girar-telefono 2s ease-in-out infinite; }
            }
            @keyframes girar-telefono {
                0%, 20% { transform: rotate(0deg); }
                60%, 100% { transform: rotate(-90deg); }
            }
        </style>
    @endpush

    <div id="aviso-girar" class="text-gray-700 dark:text-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <rect x="7" y="2.5" width="10" height="19" rx="2" />
            <line x1="10.5" y1="18.5" x2="13.5" y2="18.5" stroke-linecap="round" />
        </svg>
        <div class="text-lg font-semibold">Gira el dispositivo</div>
        <div class="text-sm text-gray-500 dark:text-gray-400">La planificación se ve mejor en horizontal.<br>Pon el móvil apaisado para continuar.</div>
    </div>

    <div class="py-2" id="calendario-container">
        <div class="px-4 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2" id="calendario-toolbar">
            <input type="text" id="filtro-eventos" placeholder="Buscar trabajador..."
                   class="w-full sm:w-64 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 text-sm focus:ring focus:ring-blue-300 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 min-w-0" id="leyenda-turnos">
                <span class="text-xs text-gray-600 dark:text-gray-400">Turnos:</span>
                @foreach($turnos as $turno)
                    <span class="flex items-center gap-1 flex-shrink-0">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background: {{ $turno->color ?? '#93C5FD' }}"></span>
                        <span class="text-xs font-medium whitespace-nowrap">{{ $turno->nombre }}</span>
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
                'compensacionSaldo' => route('compensaciones.saldo'),
                'compensacionAsignarDirecto' => route('compensaciones.asignarDirecto'),
                'compensacionBase' => url('/compensaciones-horas'),
            ],
        ];
    @endphp

    @push('scripts')
    <script data-navigate-reload>
    (function() {
        const CONFIG = @json($config);
        let menuActual = null;
        let datosCalendario = null; // Se carga via AJAX
        let ultimoClickCelda = null; // Para detectar doble clic en celda vacia

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

        // Menu para evento (turno asignado): compensación de horas y eliminar
        function menuEvento(x, y, event, calendar) {
            const p = event.extendedProps || {};
            if (p.es_festivo) return;

            const turnoNombre = p.turno_nombre || event.title;
            const fechaEvento = event.start
                ? `${event.start.getFullYear()}-${String(event.start.getMonth() + 1).padStart(2, '0')}-${String(event.start.getDate()).padStart(2, '0')}`
                : null;
            const tieneComp = Boolean(p.compensacion_id);
            const compPendiente = tieneComp && p.compensacion_estado === 'pendiente';

            const el = abrirMenu(x, y, `
                <div class="ctx-menu-header">
                    <div class="font-semibold">${turnoNombre}</div>
                    <div class="text-xs text-gray-500">${p.hora_inicio || ''} - ${p.hora_fin || ''}</div>
                    ${tieneComp ? `<div class="text-xs font-semibold ${compPendiente ? 'text-amber-600' : (Number(p.compensacion_minutos) < 0 ? 'text-red-600' : 'text-emerald-600')}">${p.compensacion_label}${compPendiente ? ' · solicitud del trabajador' : ''}</div>` : ''}
                </div>
                ${compPendiente ? `<button class="ctx-menu-item" data-action="aprobar-comp"><span>✅</span> Aprobar solicitud</button>
                <button class="ctx-menu-item ctx-menu-danger" data-action="denegar-comp"><span>🚫</span> Denegar solicitud</button>` : ''}
                <button class="ctx-menu-item" data-action="compensacion">
                    <span>⏱️</span> ${tieneComp ? (compPendiente ? 'Aprobar con otras horas…' : 'Cambiar horas (extra / compensación)') : 'Horas extra / compensación…'}
                </button>
                ${tieneComp && !compPendiente ? `<button class="ctx-menu-item ctx-menu-danger" data-action="quitar-comp"><span>✖️</span> Quitar ${Number(p.compensacion_minutos) > 0 ? 'horas extra' : 'compensación'}</button>` : ''}
                ${p.asignacion_id ? `<button class="ctx-menu-item ctx-menu-danger" data-action="eliminar"><span>🗑️</span> Eliminar turno</button>` : ''}
            `);

            el.querySelectorAll('[data-action="aprobar-comp"],[data-action="denegar-comp"]').forEach((btn) => {
                btn.onclick = async () => {
                    cerrarMenu();
                    await resolverSolicitudPlanif(p.compensacion_id, btn.dataset.action === 'aprobar-comp' ? 'aprobar' : 'denegar', { label: p.compensacion_label, fecha: fechaEvento, userId: p.user_id });
                };
            });

            el.querySelector('[data-action="compensacion"]').onclick = async () => {
                cerrarMenu();
                if (!fechaEvento) return;
                await asignarCompensacionPlanif({ userId: p.user_id, nombre: event.getResources?.()[0]?.title || '', fecha: fechaEvento });
            };

            const btnQuitar = el.querySelector('[data-action="quitar-comp"]');
            if (btnQuitar) btnQuitar.onclick = async () => {
                cerrarMenu();
                const ok = await Swal.fire({
                    icon: 'warning',
                    title: Number(p.compensacion_minutos) > 0 ? 'Quitar horas extra' : 'Quitar compensación',
                    text: `¿Quitar "${p.compensacion_label}" de este día? Dejará de contar en la bolsa del trabajador.`,
                    showCancelButton: true,
                    confirmButtonText: 'Quitar',
                    confirmButtonColor: '#b91c1c'
                }).then(r => r.isConfirmed);
                if (!ok) return;
                try {
                    const res = await fetch(`${CONFIG.routes.compensacionBase}/${p.compensacion_id}`, {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf, Accept: 'application/json' }
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || data.success === false) throw new Error(data.error || `HTTP ${res.status}`);
                    Swal.fire({ icon: 'success', title: 'Quitado', timer: 1300, showConfirmButton: false });
                    inicializarCalendario();
                } catch (e) {
                    Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'No se pudo quitar la compensación' });
                }
            };

            const btnEliminar = el.querySelector('[data-action="eliminar"]');
            if (btnEliminar) btnEliminar.onclick = async () => {
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

        // ===== Solicitudes de horas pendientes (extras / compensaciones) =====
        async function resolverSolicitudPlanif(id, accion, { label = '', fecha = null, userId = null } = {}) {
            // Al aprobar horas extra se muestra el fichaje del día para comprobarlo
            let fichajeHtml = '';
            if (accion === 'aprobar' && fecha && userId) {
                try {
                    const r = await fetch(`${CONFIG.routes.compensacionSaldo}?user_id=${userId}&fecha=${fecha}`, { headers: { Accept: 'application/json' } });
                    const d = r.ok ? await r.json() : {};
                    const f = d.fichaje;
                    if (f) {
                        const partes = [];
                        if (f.turno) partes.push(`Turno <strong>${f.turno}</strong>${f.horario ? ` (${f.horario})` : ''}`);
                        partes.push(f.fichaje ? `Fichaje <strong>${f.fichaje}</strong>` : '<strong>Sin fichaje</strong>');
                        let sug = '';
                        if (f.abierto) sug = 'Fichaje sin cerrar.';
                        else if (f.puntual === false) sug = 'Entró tarde: según las reglas no genera horas extra.';
                        else if (Number(f.extra_min) > 0) sug = `Según el fichaje: <strong>${f.extra_txt}</strong> extra${f.cumple_minimo === false ? ` (por debajo del mínimo de ${f.minimo_min} min)` : ''}.`;
                        else if (Number(f.horas_fichadas) > 0) sug = 'Según el fichaje no hay tiempo extra.';
                        fichajeHtml = `<div class="bg-gray-50 rounded-lg p-2 mt-2 text-xs text-gray-600 text-left"><div>${partes.join(' · ')}</div>${sug ? `<div class="mt-0.5">${sug}</div>` : ''}</div>`;
                    } else if (d.hoy && fecha <= d.hoy) {
                        fichajeHtml = '<div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mt-2 text-xs text-amber-800 text-left">Ese día no tiene turno asignado.</div>';
                    }
                    const saldoTxt = d.saldo_min !== undefined ? `<div class="text-xs text-gray-500 mt-2">Bolsa actual: <strong>${fmtSaldoComp(d.saldo_min)}</strong></div>` : '';
                    fichajeHtml += saldoTxt;
                } catch (e) { /* sin detalle */ }
            }
            const ok = await Swal.fire({
                icon: accion === 'aprobar' ? 'question' : 'warning',
                title: accion === 'aprobar' ? 'Aprobar solicitud' : 'Denegar solicitud',
                html: `<div class="text-sm">${label ? `<strong>${label}</strong>` : ''}${fecha ? ` · ${new Date(fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })}` : ''}${fichajeHtml}<p class="text-xs text-gray-500 mt-2">Se avisará al trabajador.</p></div>`,
                showCancelButton: true,
                confirmButtonText: accion === 'aprobar' ? 'Aprobar' : 'Denegar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: accion === 'aprobar' ? '#059669' : '#b91c1c'
            }).then(r => r.isConfirmed);
            if (!ok) return false;
            try {
                const res = await fetch(`${CONFIG.routes.compensacionBase}/${id}/${accion}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf, Accept: 'application/json' }
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.success === false) throw new Error(data.error || `HTTP ${res.status}`);
                Swal.fire({ icon: 'success', title: accion === 'aprobar' ? 'Aprobada' : 'Denegada', text: data.message || '', timer: 1500, showConfirmButton: false });
                inicializarCalendario();
                return true;
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'No se pudo procesar', text: e.message });
                return false;
            }
        }

        // Lista de solicitudes pendientes de un trabajador (desde la etiqueta de la fila)
        async function listarSolicitudesPlanif(resource) {
            const props = resource.extendedProps || {};
            const lista = props.solicitudes_pendientes || [];
            if (!lista.length) return;
            const filas = lista.map((c) => `
                <div class="flex items-center justify-between gap-2 p-2 rounded-lg border ${c.minutos > 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-teal-50 border-teal-200'}">
                    <div class="text-left">
                        <div class="text-sm font-semibold">${new Date(c.fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' })} · <span class="${claseSaldoComp(c.minutos)}">${c.label}</span></div>
                        ${c.comentario ? `<div class="text-xs text-gray-500 italic">${String(c.comentario).replace(/</g, '&lt;')}</div>` : ''}
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button type="button" class="sol-btn px-2 py-1 rounded bg-green-600 hover:bg-green-700 text-white text-xs" data-id="${c.id}" data-accion="aprobar" data-fecha="${c.fecha}" data-label="${c.label}">Aprobar</button>
                        <button type="button" class="sol-btn px-2 py-1 rounded bg-red-600 hover:bg-red-700 text-white text-xs" data-id="${c.id}" data-accion="denegar" data-fecha="${c.fecha}" data-label="${c.label}">Denegar</button>
                    </div>
                </div>`).join('');
            let elegido = null;
            await Swal.fire({
                title: `<span style="font-size:1.1rem">Solicitudes de ${resource.title}</span>`,
                html: `<div class="flex flex-col gap-2">${filas}</div><p class="text-xs text-gray-400 mt-3">Bolsa actual: <strong>${fmtSaldoComp(props.bolsa)}</strong>. Las horas solo cuentan en la bolsa al aprobarse.</p>`,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                didOpen: () => {
                    Swal.getPopup().querySelectorAll('.sol-btn').forEach((b) => b.addEventListener('click', () => { elegido = { ...b.dataset }; Swal.close(); }));
                }
            });
            if (elegido) {
                await resolverSolicitudPlanif(elegido.id, elegido.accion, { label: elegido.label, fecha: elegido.fecha, userId: resource.id });
            }
        }

        // ===== Compensación de horas desde la planificación (oficina) =====
        // Minutos -> "+1 h 05 min" / "−45 min" / "+3 h"
        const fmtSaldoComp = (v, signo = true) => {
            const n = Math.round(Number(v) || 0);
            const abs = Math.abs(n);
            const h = Math.floor(abs / 60);
            const m = abs % 60;
            const txt = abs === 0 ? '0 h' : (h > 0 ? (m > 0 ? `${h} h ${String(m).padStart(2, '0')} min` : `${h} h`) : `${m} min`);
            return (signo ? (n > 0 ? '+' : (n < 0 ? '−' : '')) : '') + txt;
        };
        const claseSaldoComp = (v) => (Number(v) > 0 ? 'text-emerald-600' : (Number(v) < 0 ? 'text-red-600' : 'text-gray-500'));

        // Modal con stepper (+/-) y saldo en vivo; devuelve { horas, comentario } o null
        async function abrirModalCompensacionPlanif({ userId, nombre, fecha }) {
            let saldo;
            try {
                const r = await fetch(`${CONFIG.routes.compensacionSaldo}?user_id=${userId}&fecha=${fecha}`, { headers: { Accept: 'application/json' } });
                if (!r.ok) throw new Error(`No se pudo consultar la bolsa (HTTP ${r.status})`);
                saldo = await r.json();
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.message });
                return null;
            }
            const paso = Number(saldo.paso) || 5;
            const max = Number(saldo.minutos_max) || 720;
            const saldoActual = Number(saldo.saldo_min) || 0;
            const maxPedir = Math.max(0, Math.floor(saldoActual / paso) * paso);
            const hoyStr = saldo.hoy || new Date().toISOString().slice(0, 10);
            // Horas extra solo de días ya trabajados; compensación solo de hoy en adelante y con saldo
            const puedeExtra = fecha <= hoyStr;
            const puedeComp = fecha >= hoyStr && maxPedir > 0;
            if (!puedeExtra && !puedeComp) {
                Swal.fire({ icon: 'info', title: 'No disponible', text: fecha > hoyStr ? `Sin saldo en la bolsa (${fmtSaldoComp(saldoActual)}): no se puede compensar un día futuro.` : 'Nada que asignar ese día.' });
                return null;
            }
            const fichaje = saldo.fichaje || null;
            const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;');
            // Una línea de contexto para días ya trabajados
            let fichajeHtml = '';
            if (puedeExtra) {
                let texto = 'Sin turno ese día';
                if (fichaje) {
                    const partes = [];
                    if (fichaje.horario) partes.push(`Turno ${esc(fichaje.horario)}`);
                    partes.push(fichaje.fichaje ? `Fichaje ${esc(fichaje.fichaje)}` : 'Sin fichaje');
                    if (fichaje.abierto) partes.push('fichaje sin cerrar');
                    else if (fichaje.puntual === false) partes.push('entró tarde: sin extras');
                    else if (Number(fichaje.extra_min) > 0) partes.push(`según fichaje <strong>${esc(fichaje.extra_txt)}</strong> extra`);
                    texto = partes.join(' · ');
                }
                fichajeHtml = `<p class="text-xs text-gray-600 mb-3">${texto}</p>`;
            }
            const sugerida = Math.min(max, Math.max(0, Number(fichaje?.extra_min) || 0));
            const fechaFmt = new Date(fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });

            const { value, isConfirmed } = await Swal.fire({
                title: '<span style="font-size:1.1rem">Horas extra / compensación</span>',
                html: `
                    <div class="text-left text-sm">
                        <p class="text-gray-600 mb-2">${nombre ? `<strong>${esc(nombre)}</strong> · ` : ''}${fechaFmt}</p>
                        ${fichajeHtml}
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" class="pc-paso w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold leading-none" data-delta="-60" title="Una hora menos">−1h</button>
                            <button type="button" class="pc-paso w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold leading-none" data-delta="-${paso}" title="${paso} minutos menos">−${paso}</button>
                            <div class="w-36 text-center">
                                <div id="pc-horas" class="text-2xl font-bold leading-tight"></div>
                                <span id="pc-tipo" class="inline-block rounded-full px-2 text-[11px] font-semibold"></span>
                            </div>
                            <button type="button" class="pc-paso w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold leading-none" data-delta="${paso}" title="${paso} minutos más">+${paso}</button>
                            <button type="button" class="pc-paso w-10 h-10 rounded-full bg-gray-200 hover:bg-gray-300 text-xs font-bold leading-none" data-delta="60" title="Una hora más">+1h</button>
                        </div>
                        <p id="pc-bolsa" class="mt-1 mb-3 text-center text-xs text-gray-600"></p>
                        <textarea id="pc-comentario" rows="1" maxlength="500" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm" placeholder="Comentario (opcional)"></textarea>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Asignar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d9488',
                focusConfirm: false,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    let horas = (puedeExtra && (!puedeComp || fecha < hoyStr)) ? (sugerida > 0 ? sugerida : 60) : -Math.min(60, maxPedir); // minutos con signo
                    const txt = popup.querySelector('#pc-horas');
                    const tipo = popup.querySelector('#pc-tipo');
                    const bolsa = popup.querySelector('#pc-bolsa');
                    const minimo = puedeComp ? -Math.min(max, maxPedir) : paso;
                    const maximo = puedeExtra ? max : -paso;
                    const pintar = () => {
                        txt.textContent = fmtSaldoComp(horas);
                        txt.className = 'text-2xl font-bold leading-tight ' + claseSaldoComp(horas);
                        tipo.textContent = horas < 0 ? 'Compensación' : 'Horas extra';
                        tipo.className = 'inline-block rounded-full px-2 text-[11px] font-semibold ' + (horas < 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700');
                        const resultante = saldoActual + horas;
                        bolsa.innerHTML = `Bolsa al guardar: <strong class="${claseSaldoComp(resultante)}">${fmtSaldoComp(resultante)}</strong>`;
                        popup.querySelectorAll('.pc-paso').forEach((b) => {
                            const apagado = Number(b.dataset.delta) > 0 ? horas >= maximo : horas <= minimo;
                            b.disabled = apagado;
                            b.classList.toggle('opacity-40', apagado);
                            b.classList.toggle('cursor-not-allowed', apagado);
                        });
                        popup.dataset.horas = String(horas);
                    };
                    const mover = (delta) => {
                        let n = horas + delta;
                        if (n === 0 || Math.sign(n) !== Math.sign(horas)) {
                            if (delta > 0) n = puedeExtra ? paso : -paso;
                            else n = puedeComp ? -paso : paso;
                        }
                        horas = Math.max(minimo, Math.min(maximo, n));
                        pintar();
                    };
                    popup.querySelectorAll('.pc-paso').forEach((b) => b.addEventListener('click', () => mover(Number(b.dataset.delta))));
                    pintar();
                },
                preConfirm: () => {
                    const popup = Swal.getPopup();
                    const horas = Number(popup.dataset.horas);
                    if (!horas || horas % paso !== 0) { Swal.showValidationMessage(`Indica el tiempo en pasos de ${paso} minutos.`); return false; }
                    if (horas > 0 && !puedeExtra) { Swal.showValidationMessage('Las horas extra solo se reconocen de días ya trabajados.'); return false; }
                    if (horas < 0 && (!puedeComp || saldoActual + horas < 0)) { Swal.showValidationMessage('La bolsa no puede quedar en negativo.'); return false; }
                    return { minutos: horas, comentario: (popup.querySelector('#pc-comentario')?.value || '').trim() };
                }
            });
            return isConfirmed ? value : null;
        }

        async function asignarCompensacionPlanif({ userId, nombre, fecha }) {
            const datos = await abrirModalCompensacionPlanif({ userId, nombre, fecha });
            if (!datos) return false;
            try {
                const res = await fetch(CONFIG.routes.compensacionAsignarDirecto, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ user_id: userId, fecha, minutos: datos.minutos, comentario: datos.comentario })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error(data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : `HTTP ${res.status}`));
                Swal.fire({ icon: 'success', title: 'Asignado', text: data.message || '', timer: 1500, showConfirmButton: false });
                inicializarCalendario();
                return true;
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'No se pudo asignar', text: e.message });
                return false;
            }
        }

        // Comprueba si un trabajador ya tiene una asignacion (turno o estado) en una fecha
        function tieneAsignacionEseDia(resourceId, fecha, calendar) {
            return calendar.getEvents().some((e) => {
                const p = e.extendedProps || {};
                if (p.es_festivo) return false;
                if (!p.asignacion_id) return false; // compensación suelta: no bloquea crear el turno
                if (String(p.user_id) !== String(resourceId)) return false;
                if (!e.start) return false;
                const d = e.start;
                const ymd = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                return ymd === fecha;
            });
        }

        // Modal para crear una asignacion (doble clic en celda vacia)
        async function abrirModalCrearTurno(resource, fecha, calendar) {
            // Evitar el error del backend cuando se hace doble clic en la zona vacia
            // de una celda que ya tiene asignacion ese dia
            if (tieneAsignacionEseDia(resource.id, fecha, calendar)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Ya tiene una asignacion',
                    text: 'Este trabajador ya tiene un turno o estado asignado ese dia. Borralo (clic derecho) antes de crear otro.',
                    timer: 2600,
                    showConfirmButton: false
                });
                return;
            }

            const turnos = (datosCalendario && datosCalendario.turnos) ? datosCalendario.turnos : [];
            if (!turnos.length) {
                Swal.fire({ icon: 'info', title: 'No hay turnos', text: 'No hay turnos activos para asignar.' });
                return;
            }

            const fechaFmt = new Date(fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
            const opciones = turnos.map((t) => {
                const ini = (t.hora_inicio || '').slice(0, 5);
                const fin = (t.hora_fin || '').slice(0, 5);
                return `<button type="button" class="turno-opt" data-id="${t.id}"
                            style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;cursor:pointer;text-align:left;width:100%">
                            <span style="width:14px;height:14px;border-radius:50%;background:${t.color || '#93C5FD'};flex:0 0 auto"></span>
                            <span style="font-weight:600">${t.nombre}</span>
                            <span style="margin-left:auto;color:#6b7280;font-size:12px">${ini} - ${fin}</span>
                        </button>`;
            }).join('');

            let turnoSeleccionado = null;
            let pedirCompensacion = false;
            await Swal.fire({
                title: 'Asignar turno',
                html: `<div style="font-size:13px;color:#6b7280;margin-bottom:10px">${resource.title} &middot; ${fechaFmt}</div>
                       <div style="display:flex;flex-direction:column;gap:8px">${opciones}</div>
                       <div style="border-top:1px solid #e5e7eb;margin-top:12px;padding-top:10px">
                           <button type="button" id="opt-compensacion"
                               style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #99f6e4;border-radius:8px;background:#f0fdfa;cursor:pointer;text-align:left;width:100%">
                               <span style="width:14px;height:14px;border-radius:50%;background:#0d9488;flex:0 0 auto"></span>
                               <span style="font-weight:600;color:#115e59">Horas extra / compensación (sin turno)</span>
                               <span style="margin-left:auto;color:#6b7280;font-size:12px">extra / día libre con bolsa</span>
                           </button>
                       </div>`,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    Swal.getPopup().querySelectorAll('.turno-opt').forEach((btn) => {
                        btn.addEventListener('mouseenter', () => { btn.style.background = '#f3f4f6'; });
                        btn.addEventListener('mouseleave', () => { btn.style.background = '#fff'; });
                        btn.addEventListener('click', () => { turnoSeleccionado = btn.dataset.id; Swal.close(); });
                    });
                    Swal.getPopup().querySelector('#opt-compensacion')?.addEventListener('click', () => { pedirCompensacion = true; Swal.close(); });
                }
            });

            if (pedirCompensacion) {
                await asignarCompensacionPlanif({ userId: resource.id, nombre: resource.title, fecha });
                return;
            }

            if (!turnoSeleccionado) return; // cancelado

            try {
                const res = await fetch(CONFIG.routes.crearAsignacion, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CONFIG.csrf },
                    body: JSON.stringify({ user_id: resource.id, turno_id: turnoSeleccionado, fecha })
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    Swal.fire({ icon: 'error', title: 'No se pudo crear', text: data.message || 'Error al crear la asignacion' });
                    return;
                }

                const a = data.asignacion;
                const start = `${a.fecha}T${a.turno_hora_inicio}:00`;
                let end;
                if (a.turno_hora_fin < a.turno_hora_inicio) {
                    // Turno nocturno: termina al dia siguiente
                    const d = new Date(a.fecha + 'T00:00:00');
                    d.setDate(d.getDate() + 1);
                    end = `${d.toISOString().slice(0, 10)}T${a.turno_hora_fin}:00`;
                } else {
                    end = `${a.fecha}T${a.turno_hora_fin}:00`;
                }

                calendar.addEvent({
                    id: 'asig-' + a.id,
                    title: a.turno_nombre,
                    start,
                    end,
                    resourceId: a.user_id,
                    backgroundColor: a.color.bg,
                    borderColor: a.color.border,
                    textColor: '#000000',
                    extendedProps: {
                        asignacion_id: a.id,
                        user_id: a.user_id,
                        turno_id: a.turno_id,
                        turno_nombre: a.turno_nombre,
                        estado: 'activo',
                        entrada: null, salida: null, entrada2: null, salida2: null,
                        foto: a.foto,
                        categoria: a.categoria,
                        hora_inicio: a.turno_hora_inicio,
                        hora_fin: a.turno_hora_fin,
                    }
                });

                Swal.fire({ icon: 'success', title: 'Turno asignado', timer: 1200, showConfirmButton: false });
            } catch (e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo crear la asignacion' });
            }
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
                resourceAreaWidth: window.matchMedia('(max-width: 767px)').matches ? '140px' : '210px',
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

                        // Horas reales fichadas: semana / mes
                        const fmtH = (v) => {
                            const n = Number(v) || 0;
                            return (Number.isInteger(n) ? n.toString() : n.toFixed(2).replace('.', ',').replace(/,?0+$/, ''));
                        };
                        const horas = document.createElement('div');
                        horas.className = 'text-[10px] text-gray-600 dark:text-gray-300 font-medium mt-0.5';
                        horas.innerHTML = `<span title="Horas fichadas esta semana (previstas: ${fmtH(props.previstas_semana)}h)">S: ${fmtH(props.horas_semana)}h</span>` +
                            ` · <span title="Horas fichadas este mes">M: ${fmtH(props.horas_mes)}h</span>`;
                        texto.appendChild(horas);

                        // Bolsa de horas: extras aprobadas (+) menos compensaciones aprobadas (-)
                        const fmtSaldo = (v) => {
                            const n = Number(v) || 0;
                            return (n > 0 ? '+' : (n < 0 ? '-' : '')) + fmtH(Math.abs(n)) + 'h';
                        };
                        const bolsaN = Number(props.bolsa) || 0; // minutos
                        const bolsa = document.createElement('div');
                        bolsa.className = 'text-[10px] font-semibold mt-0.5 ' +
                            (bolsaN > 0 ? 'text-emerald-600 dark:text-emerald-400' : (bolsaN < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-gray-500'));
                        const saldoSemanaN = Number(props.saldo_semana) || 0;
                        bolsa.title = `Bolsa de horas: ` +
                            `${fmtSaldoComp(props.bolsa_extra, false)} extra aprobadas, ${fmtSaldoComp(props.bolsa_devueltas, false)} compensadas. ` +
                            `Fichaje vs turno esta semana (orientativo): ${fmtSaldo(saldoSemanaN)}`;
                        bolsa.innerHTML = `Bolsa: ${fmtSaldoComp(bolsaN)}` +
                            (saldoSemanaN !== 0 ? ` <span class="font-normal opacity-75">(sem. ${fmtSaldo(saldoSemanaN)})</span>` : '');
                        texto.appendChild(bolsa);

                        // Solicitudes de horas pendientes de aprobar (extras / compensaciones)
                        const pendientes = props.solicitudes_pendientes || [];
                        if (pendientes.length) {
                            const sol = document.createElement('button');
                            sol.type = 'button';
                            sol.className = 'mt-0.5 inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/50 dark:text-amber-200';
                            sol.textContent = `⏳ ${pendientes.length} solicitud${pendientes.length === 1 ? '' : 'es'} de horas`;
                            sol.title = pendientes.map((c) => `${c.fecha}: ${c.label}`).join('\n') + '\nPulsa para aprobar o denegar';
                            sol.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); listarSolicitudesPlanif(arg.resource); });
                            texto.appendChild(sol);
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
                    // Doble clic en celda vacia -> abrir modal para crear turno
                    if (!info.resource) return;
                    const fecha = info.dateStr.slice(0, 10);
                    const key = info.resource.id + '|' + fecha;
                    const ahora = Date.now();
                    if (ultimoClickCelda && ultimoClickCelda.key === key && (ahora - ultimoClickCelda.t) < 400) {
                        ultimoClickCelda = null;
                        abrirModalCrearTurno(info.resource, fecha, calendar);
                    } else {
                        ultimoClickCelda = { key, t: ahora };
                    }
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

                    // Fichajes reales debajo del turno (badge gris para distinguirlos del turno)
                    const badgeFichaje = 'inline-flex items-center gap-0.5 bg-gray-200 text-gray-700 rounded px-1 mt-0.5 text-[10px] leading-tight font-medium';
                    const iconoReloj = `<svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>`;
                    let fichajesHtml = '';
                    if (p.entrada || p.salida) {
                        fichajesHtml += `<span class="${badgeFichaje}">${iconoReloj}${p.entrada || '--'} / ${p.salida || '--'}</span>`;
                    }
                    if (p.entrada2 || p.salida2) {
                        fichajesHtml += `<span class="${badgeFichaje}">${iconoReloj}${p.entrada2 || '--'} / ${p.salida2 || '--'}</span>`;
                    }
                    if (p.compensacion_label) {
                        const compNeg = Number(p.compensacion_minutos) < 0;
                        const compPend = p.compensacion_estado === 'pendiente';
                        const claseComp = compPend ? 'bg-amber-100 text-amber-800 border border-amber-300' : (compNeg ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700');
                        fichajesHtml += `<span class="inline-flex items-center rounded px-1 mt-0.5 text-[10px] leading-tight font-semibold ${claseComp}" title="${compPend ? 'Solicitud del trabajador pendiente de aprobar (clic derecho para aprobar o denegar)' : 'Horas aprobadas que cuentan en la bolsa'}">${compPend ? '⏳ ' : ''}${p.compensacion_label}</span>`;
                    }

                    return {
                        html: `<div class="flex flex-col items-center justify-center h-full px-1 text-center overflow-hidden">
                            <div class="text-xs font-semibold leading-tight">${p.turno_nombre ? p.turno_nombre + ' · ' : ''}${horaInicio} - ${horaFin}</div>
                            ${fichajesHtml}
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

            // Recalcular tamano al girar el movil (la rotacion CSS cambia las
            // dimensiones del contenedor sin que FullCalendar se entere solo)
            window.addEventListener('orientationchange', () => setTimeout(() => calendar.updateSize(), 300));
            window.matchMedia('(orientation: portrait)').addEventListener('change', () => setTimeout(() => calendar.updateSize(), 300));

            // Filtro de busqueda: oculta las filas de trabajadores que no encajan.
            // Se cambia la lista de recursos de una vez (un solo repintado); los
            // eventos de los trabajadores ocultos desaparecen con su fila.
            const filtro = document.getElementById('filtro-eventos');
            if (filtro) {
                const normalizar = (t) => (t || '').toString().toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                const todosRecursos = datosCalendario.recursos || [];
                let temporizadorFiltro = null;
                const aplicarFiltro = () => {
                    const texto = normalizar(filtro.value).trim();
                    const visibles = !texto ? todosRecursos : todosRecursos.filter(r =>
                        normalizar(r.title).includes(texto) || normalizar(r.categoria).includes(texto));
                    calendar.setOption('resources', visibles);
                };
                filtro.addEventListener('input', () => {
                    clearTimeout(temporizadorFiltro);
                    temporizadorFiltro = setTimeout(aplicarFiltro, 200);
                });
                if (filtro.value) aplicarFiltro();
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
