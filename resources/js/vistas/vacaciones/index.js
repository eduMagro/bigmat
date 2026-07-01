// Calendario de Vacaciones (index) — migrado de <script> por CDN a bundle de Vite.
// Se carga como módulo (entrada de Vite en el <head>), por lo que sus listeners
// quedan registrados ANTES de que Livewire dispare `livewire:navigated`. Esto
// arregla el problema de que el calendario no renderizaba en la primera carga
// (con navegación SPA de Livewire, `DOMContentLoaded` no vuelve a dispararse y el
// listener inline se registraba demasiado tarde para el evento de navegación).

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

// --- Configuración inyectada por el Blade (URLs + eventos de respaldo) ---
function leerConfig() {
    const el = document.getElementById('vacaciones-config');
    if (!el) return null;
    try {
        return JSON.parse(el.textContent);
    } catch (e) {
        console.warn('No se pudo leer la configuración de vacaciones:', e);
        return null;
    }
}

function mostrarError(mensaje) {
    if (typeof window.mostrarError === 'function') {
        window.mostrarError(mensaje);
    } else if (window.Swal) {
        window.Swal.fire({ title: 'Error', text: mensaje, icon: 'error' });
    } else {
        alert(mensaje);
    }
}

// Cache global de usuarios (compartida entre la tabla y el calendario)
let usuariosCache = null;

// Preguntar a qué año imputar las vacaciones (con sobrantes del año anterior)
async function elegirAnioVacacional(userId, cfg) {
    const Swal = window.Swal;
    const anioActual = new Date().getFullYear();
    try {
        const resp = await fetch(`${cfg.urls.sobrantes}/${userId}`, {
            headers: { Accept: 'application/json' },
        });
        const sobrantes = await resp.json();

        if (sobrantes && sobrantes.sobrantes > 0) {
            const result = await Swal.fire({
                title: 'Vacaciones del año anterior',
                html: `<p class="mb-3">Este trabajador tiene <b>${sobrantes.sobrantes} días</b> sin disfrutar de <b>${sobrantes.anio_anterior}</b>.</p><p>¿A qué año quieres imputar estas vacaciones?</p>`,
                icon: 'question',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: `Año actual (${anioActual})`,
                denyButtonText: `Año anterior (${sobrantes.anio_anterior})`,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563EB',
                denyButtonColor: '#6B7280',
            });

            if (result.isDismissed) {
                return { anioVacacional: null, sobrantesAnterior: null, cancelled: true };
            }
            if (result.isDenied) {
                return { anioVacacional: sobrantes.anio_anterior, sobrantesAnterior: sobrantes.sobrantes, cancelled: false };
            }
            return { anioVacacional: anioActual, sobrantesAnterior: null, cancelled: false };
        }

        // Sin sobrantes → imputa al año actual sin preguntar
        return { anioVacacional: anioActual, sobrantesAnterior: null, cancelled: false };
    } catch (error) {
        console.warn('No se pudieron consultar sobrantes:', error);
        return { anioVacacional: anioActual, sobrantesAnterior: null, cancelled: false };
    }
}

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
}

// --- Aprobar / denegar solicitudes (invocadas desde la tabla vía onclick) ---
async function aprobarSolicitud(solicitudId, btn) {
    const cfg = leerConfig();
    const token = csrfToken();
    if (!token) {
        mostrarError('No se encontró el token CSRF. Recarga la página.');
        return;
    }
    const row = btn.closest('tr');
    const userId = row?.dataset?.userId;

    // Preguntar año de imputación antes de procesar
    const eleccion = await elegirAnioVacacional(userId, cfg);
    if (eleccion.cancelled) return;

    const btns = row.querySelectorAll('button');
    btns.forEach((b) => (b.disabled = true));
    btn.textContent = 'Procesando...';

    fetch(`${cfg.urls.vacaciones}/${solicitudId}/aprobar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
        },
        body: JSON.stringify({
            anio_vacacional: eleccion.anioVacacional,
            sobrantes_anterior: eleccion.sobrantesAnterior,
        }),
    })
        .then((response) => {
            if (!response.ok) {
                return response.text().then((text) => {
                    try {
                        return JSON.parse(text);
                    } catch {
                        throw new Error(text.substring(0, 200));
                    }
                });
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                row.remove();
                refrescarCalendarios();
                verificarTablasVacias();
                window.Swal.fire({
                    icon: 'success',
                    title: 'Aprobada',
                    text: data.message || 'Solicitud aprobada correctamente',
                    timer: 2000,
                    showConfirmButton: false,
                });
            } else {
                mostrarError(data.error || 'No se pudo aprobar la solicitud');
                btns.forEach((b) => (b.disabled = false));
                btn.textContent = 'Aprobar';
            }
        })
        .catch((error) => {
            console.error('Error aprobar:', error);
            mostrarError(error.message || 'Error de conexión');
            btns.forEach((b) => (b.disabled = false));
            btn.textContent = 'Aprobar';
        });
}

function denegarSolicitud(solicitudId, btn) {
    const cfg = leerConfig();
    const token = csrfToken();
    if (!token) {
        mostrarError('No se encontró el token CSRF. Recarga la página.');
        return;
    }
    const row = btn.closest('tr');

    const btns = row.querySelectorAll('button');
    btns.forEach((b) => (b.disabled = true));
    btn.textContent = 'Procesando...';

    fetch(`${cfg.urls.vacaciones}/${solicitudId}/denegar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) {
                return response.text().then((text) => {
                    try {
                        return JSON.parse(text);
                    } catch {
                        throw new Error(text.substring(0, 200));
                    }
                });
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                row.remove();
                verificarTablasVacias();
                window.Swal.fire({
                    icon: 'success',
                    title: 'Denegada',
                    text: data.message || 'Solicitud denegada correctamente',
                    timer: 2000,
                    showConfirmButton: false,
                });
            } else {
                mostrarError(data.error || 'No se pudo denegar la solicitud');
                btns.forEach((b) => (b.disabled = false));
                btn.textContent = 'Denegar';
            }
        })
        .catch((error) => {
            console.error('Error denegar:', error);
            mostrarError(error.message || 'Error de conexión');
            btns.forEach((b) => (b.disabled = false));
            btn.textContent = 'Denegar';
        });
}

function refrescarCalendarios() {
    const calEl = document.getElementById('calendario-vacaciones');
    if (calEl && calEl._calendar) {
        calEl._calendar.refetchEvents();
    }
    usuariosCache = null;
}

function verificarTablasVacias() {
    document.querySelectorAll('table').forEach((tabla) => {
        const tbody = tabla.querySelector('tbody');
        if (tbody && tbody.querySelectorAll('tr').length === 0) {
            const container = tabla.closest('.overflow-x-auto');
            if (container) {
                container.innerHTML = '<p class="text-gray-600 dark:text-gray-400">No hay solicitudes pendientes.</p>';
            }
        }
    });
}

function inicializarCalendario(cfg) {
    const el = document.getElementById('calendario-vacaciones');
    if (!el) return;

    // Destruir calendario existente si existe
    if (el._calendar) {
        el._calendar.destroy();
    }

    const token = csrfToken();

    // Estado de selección click-and-click
    const state = { startClick: null, hoverDayEvs: [] };

    function eachDayStr(aStr, bStr) {
        const days = [];
        let a = new Date(aStr),
            b = new Date(bStr);
        if (a > b) [a, b] = [b, a];
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
        calendar.batchRendering(() => state.hoverDayEvs.forEach((ev) => ev.remove()));
        state.hoverDayEvs = [];
    }

    function updateTempHighlight(startStr, hoverStr) {
        const forward = startStr <= hoverStr;
        const days = eachDayStr(startStr, hoverStr);
        const first = days[0];
        const last = days[days.length - 1];

        clearTempHighlight();

        calendar.batchRendering(() => {
            days.forEach((d) => {
                const isFirst = d === first;
                const isLast = d === last;
                const classes = [];

                if (isFirst || isLast) {
                    classes.push('bg-select-endpoint');
                    if (isFirst) classes.push(forward ? 'bg-select-endpoint-left' : 'bg-select-endpoint-right');
                    if (isLast) classes.push(forward ? 'bg-select-endpoint-right' : 'bg-select-endpoint-left');
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

    function generarListaUsuarios(usuarios) {
        if (usuarios.length === 0) {
            return '<div style="padding: 20px; text-align: center;" class="text-gray-600 dark:text-gray-400">No se encontraron empleados</div>';
        }

        return usuarios
            .map((u) => {
                const restantes = u.vacaciones_restantes;
                let claseVacaciones = 'vacaciones-ok';
                if (restantes <= 0) {
                    claseVacaciones = 'vacaciones-full';
                } else if (restantes <= 5) {
                    claseVacaciones = 'vacaciones-warning';
                }

                return `
                    <div class="usuario-item" data-user-id="${u.id}">
                        <span class="usuario-info">
                            <span class="usuario-nombre">${u.nombre_completo}</span>
                            ${u.categoria ? `<span class="usuario-categoria">${u.categoria}</span>` : ''}
                        </span>
                        <span class="usuario-vacaciones ${claseVacaciones}">
                            ${u.vacaciones_usadas}/${u.vacaciones_totales}
                        </span>
                    </div>
                `;
            })
            .join('');
    }

    async function mostrarSelectorUsuario(fechaInicio, fechaFin) {
        const Swal = window.Swal;
        // Cargar usuarios si no están en cache
        if (!usuariosCache) {
            try {
                const response = await fetch(cfg.urls.usuariosConVacaciones);
                usuariosCache = await response.json();
            } catch (error) {
                console.error('Error cargando usuarios:', error);
                mostrarError('No se pudieron cargar los usuarios');
                return;
            }
        }

        const usuarios = usuariosCache;
        const esMismoDia = fechaInicio === fechaFin;

        const { value: usuarioSeleccionado, isConfirmed } = await Swal.fire({
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

                setTimeout(() => buscador.focus(), 100);

                buscador.addEventListener('input', (e) => {
                    const filtro = e.target.value.toLowerCase().trim();
                    const usuariosFiltrados = usuarios.filter((u) => u.nombre_completo.toLowerCase().includes(filtro));
                    listaContainer.innerHTML = generarListaUsuarios(usuariosFiltrados);
                    bindClickUsuarios();
                });

                function bindClickUsuarios() {
                    listaContainer.querySelectorAll('.usuario-item').forEach((item) => {
                        item.addEventListener('click', () => {
                            listaContainer.querySelectorAll('.usuario-item').forEach((i) => i.classList.remove('selected'));
                            item.classList.add('selected');
                            document.getElementById('usuario-seleccionado-id').value = item.dataset.userId;
                        });
                    });
                }
                bindClickUsuarios();
            },
        });

        if (!isConfirmed || !usuarioSeleccionado) return;

        // Preguntar año de imputación (sobrantes del año anterior)
        const eleccion = await elegirAnioVacacional(usuarioSeleccionado, cfg);
        if (eleccion.cancelled) return;

        try {
            const response = await fetch(cfg.urls.asignarDirecto, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    user_id: usuarioSeleccionado,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    anio_vacacional: eleccion.anioVacacional,
                    sobrantes_anterior: eleccion.sobrantesAnterior,
                }),
            });

            const data = await response.json();

            if (data.success) {
                usuariosCache = null;
                calendar.refetchEvents();
                Swal.fire({
                    icon: 'success',
                    title: 'Vacaciones asignadas',
                    text: data.message,
                    timer: 3000,
                    showConfirmButton: false,
                });
            } else {
                mostrarError(data.error || 'No se pudieron asignar las vacaciones');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error de conexión');
        }
    }

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: esLocale,
        height: 'auto',
        editable: true,
        firstDay: 1,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        events: function (info, successCallback) {
            fetch(cfg.urls.eventos)
                .then((r) => r.json())
                .then((data) => successCallback(data))
                .catch((err) => {
                    console.warn('Error cargando eventos:', err);
                    successCallback(cfg.eventosFallback || []);
                });
        },

        // Sistema click-and-click para seleccionar rango
        dateClick: function (info) {
            const clicked = info.dateStr;

            if (!state.startClick) {
                state.startClick = clicked;
                updateTempHighlight(clicked, clicked);
                return;
            }

            const startStr = clicked < state.startClick ? clicked : state.startClick;
            const endStr = clicked < state.startClick ? state.startClick : clicked;

            clearTempHighlight();
            state.startClick = null;

            mostrarSelectorUsuario(startStr, endStr);
        },

        eventDrop: function (info) {
            const fecha = info.event.startStr;
            const idUsuario = info.event.extendedProps.user_id;

            if (!idUsuario) {
                // Es un festivo, no se puede mover
                info.revert();
                return;
            }

            fetch(cfg.urls.reprogramar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    user_id: idUsuario,
                    fecha_original: info.oldEvent.startStr,
                    nueva_fecha: fecha,
                }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (!data.success) {
                        mostrarError(data.error || 'No se pudo reprogramar');
                        info.revert();
                    }
                })
                .catch((err) => {
                    console.error(err);
                    mostrarError('Error de conexión');
                    info.revert();
                });
        },

        eventClick: function (info) {
            if (state.startClick) {
                clearTempHighlight();
                state.startClick = null;
                return;
            }

            const userId = info.event.extendedProps.user_id;
            if (userId) {
                window.location.href = `${cfg.urls.users}/${userId}`;
            }
        },

        // Render personalizado: nombre y, debajo, la categoría del trabajador
        eventContent: function (info) {
            // Los eventos de fondo (preview de selección) usan el render por defecto
            if (info.event.display === 'background') return undefined;

            const wrapper = document.createElement('div');
            wrapper.className = 'fc-event-cuerpo';

            const nombreEl = document.createElement('div');
            nombreEl.className = 'fc-event-nombre';
            nombreEl.textContent = info.event.title;
            wrapper.appendChild(nombreEl);

            const categoria = info.event.extendedProps.categoria;
            if (categoria) {
                const catEl = document.createElement('div');
                catEl.className = 'fc-event-categoria';
                catEl.textContent = categoria;
                wrapper.appendChild(catEl);
            }

            return { domNodes: [wrapper] };
        },

        eventDidMount: function (info) {
            const userId = info.event.extendedProps.user_id;
            if (!userId) return; // Es un festivo, no hacer nada

            info.el.addEventListener('contextmenu', function (ev) {
                ev.preventDefault();

                window.Swal.fire({
                    title: `Eliminar vacaciones de ${info.event.title}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(cfg.urls.eliminarEvento, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                fecha: info.event.startStr,
                            }),
                        })
                            .then((res) => res.json())
                            .then((data) => {
                                if (data.success) {
                                    info.event.remove();
                                    usuariosCache = null;
                                    window.Swal.fire('Eliminado', 'Vacaciones eliminadas correctamente.', 'success');
                                } else {
                                    mostrarError(data.error || 'No se pudo eliminar');
                                }
                            })
                            .catch(() => {
                                mostrarError('Error de conexión');
                            });
                    }
                });
            });
        },
    });

    // Bind hover para mostrar preview del rango
    function bindHoverCells() {
        const cells = el.querySelectorAll('.fc-daygrid-day');
        cells.forEach((cell) => {
            cell.addEventListener('mouseenter', () => {
                if (!state.startClick) return;
                const day = cell.getAttribute('data-date');
                if (day) updateTempHighlight(state.startClick, day);
            });
        });
    }

    calendar.on('datesSet', bindHoverCells);

    // Cancelar selección con ESC
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

function initVacacionesPage() {
    // Solo actuar en la página de vacaciones
    if (!document.getElementById('calendario-vacaciones')) return;

    // Prevenir doble inicialización
    if (document.body.dataset.vacacionesPageInit === 'true') return;

    const cfg = leerConfig();
    if (!cfg) return;

    document.body.dataset.vacacionesPageInit = 'true';
    inicializarCalendario(cfg);
}

// Exponer las acciones de la tabla como globales (se llaman por onclick)
window.aprobarSolicitud = aprobarSolicitud;
window.denegarSolicitud = denegarSolicitud;

// --- Arranque robusto ---
// En Livewire v3 `livewire:navigated` se dispara tanto en la carga inicial como
// tras cada navegación SPA. Como este módulo (entrada de Vite) se evalúa una vez
// y pronto, el listener queda registrado a tiempo para todas las navegaciones.
document.addEventListener('livewire:navigated', initVacacionesPage);
document.addEventListener('DOMContentLoaded', initVacacionesPage);

// Al salir de la página, permitir que se reinicialice al volver.
document.addEventListener('livewire:navigating', () => {
    document.body.dataset.vacacionesPageInit = 'false';
});

// Si el DOM ya está listo cuando se evalúa el módulo, intentar arrancar ya.
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    initVacacionesPage();
}
