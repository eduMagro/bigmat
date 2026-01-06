import { DATA, R } from "./config.js";
import { openCellMenu } from "./menu/cellMenu.js";
import { openFestivoMenu } from "./menu/festivoMenu.js";
import { openWorkerMenu } from "./menu/workerMenu.js";
import { verificarConflictosObraTaller } from "./utils/verificarConflictos.js";

/**
 * Genera eventos de carga de trabajo por turno para mostrar en vista de día
 * Son indicadores pequeños que muestran las horas de trabajo por turno
 * @param {Object} cargaTrabajo - Datos de carga por máquina/día/turno
 * @param {Array} turnosConfig - Configuración de turnos desde BD
 */
function generarEventosCarga(cargaTrabajo, turnosConfig) {
    const eventos = [];

    if (!cargaTrabajo || typeof cargaTrabajo !== 'object') {
        return eventos;
    }

    // Filtrar turnos válidos (con hora_inicio y hora_fin)
    const turnosValidos = (turnosConfig || []).filter(t => t.hora_inicio && t.hora_fin);

    if (turnosValidos.length === 0) {
        return eventos;
    }

    // Mapear turnos con horas alineadas a los slots del calendario
    // Orden visual: Noche (00:00-08:00) → Mañana (08:00-16:00) → Tarde (16:00-24:00)
    const turnosMap = {};
    turnosValidos.forEach(turno => {
        const nombre = turno.nombre.toLowerCase();
        const [hIni] = turno.hora_inicio.split(':').map(Number);
        const [hFin] = turno.hora_fin.split(':').map(Number);
        const esNocturno = hFin < hIni;

        // Mapeo visual: noche=00-08, mañana=08-16, tarde=16-24
        let slotInicio, slotFin;
        if (esNocturno) {
            slotInicio = '00:00:00';
            slotFin = '00:30:00';
        } else if (hIni < 14) {
            slotInicio = '08:00:00';
            slotFin = '08:30:00';
        } else {
            slotInicio = '16:00:00';
            slotFin = '16:30:00';
        }

        turnosMap[nombre] = {
            esNocturno: esNocturno,
            slotInicio: slotInicio,
            slotFin: slotFin,
            color: turno.color,
        };
    });

    for (const [maquinaId, dias] of Object.entries(cargaTrabajo)) {
        if (!dias || typeof dias !== 'object') continue;

        for (const [fecha, data] of Object.entries(dias)) {
            if (!data) continue;

            // Crear indicador por cada turno que tenga horas
            for (const [turnoNombre, turnoConfig] of Object.entries(turnosMap)) {
                const horas = data[turnoNombre] || 0;
                if (horas <= 0) continue;

                // Todos los eventos usan la fecha de la asignación
                eventos.push({
                    id: `carga-${maquinaId}-${fecha}-${turnoNombre}`,
                    title: `${horas}h`,
                    start: `${fecha}T${turnoConfig.slotInicio}`,
                    end: `${fecha}T${turnoConfig.slotFin}`,
                    resourceId: maquinaId,
                    backgroundColor: getColorCarga(horas),
                    borderColor: getColorCarga(horas),
                    textColor: '#000',
                    editable: false,
                    classNames: ['evento-carga'],
                    extendedProps: {
                        es_carga: true,
                        turno: turnoNombre,
                        horas: horas,
                    }
                });
            }
        }
    }

    return eventos;
}

// Sumar minutos a una hora HH:MM
function sumarMinutos(hora, minutos) {
    const [h, m] = hora.split(':').map(Number);
    const totalMin = h * 60 + m + minutos;
    const newH = Math.floor(totalMin / 60) % 24;
    const newM = totalMin % 60;
    return `${String(newH).padStart(2, '0')}:${String(newM).padStart(2, '0')}`;
}

// Color según horas de carga
function getColorCarga(horas) {
    if (horas > 6) return '#fca5a5';   // rojo claro
    if (horas > 3) return '#fcd34d';   // amarillo
    return '#86efac';                   // verde claro
}

/**
 * =====================================================
 * LÓGICA DE TURNOS EN EL CALENDARIO DE TRABAJADORES
 * =====================================================
 *
 * CONCEPTO CLAVE:
 * El turno de noche del LUNES significa que el trabajador trabaja
 * desde el DOMINGO a las 22:00 hasta el LUNES a las 06:00.
 * La asignación en la BD tiene fecha = LUNES.
 *
 * ORDEN VISUAL DE LOS TURNOS:
 * El primer turno del día es NOCHE (no mañana), por lo que
 * visualmente debe aparecer a la IZQUIERDA.
 *
 * MAPEO VISUAL (horas ficticias para ordenar correctamente):
 * - Turno NOCHE (real 22:00-06:00) → se muestra en slot 00:00-08:00
 * - Turno MAÑANA (real 06:00-14:00) → se muestra en slot 08:00-16:00
 * - Turno TARDE (real 14:00-22:00) → se muestra en slot 16:00-24:00
 *
 * Esto permite que FullCalendar ordene cronológicamente los slots
 * pero visualmente aparezcan en el orden correcto: Noche → Mañana → Tarde
 *
 * ARCHIVOS RELACIONADOS:
 * - Este archivo (calendar.js): configuración del calendario y eventos de carga
 * - ProduccionController.php: genera los eventos de trabajadores con el mismo mapeo
 * - calcularCargaTrabajoPorDia(): calcula horas de máquina por turno
 *
 * IMPORTANTE: Si se modifica el mapeo aquí, también hay que modificarlo en:
 * 1. generarEventosCarga() - eventos de horas de máquina
 * 2. ProduccionController::trabajadores() - eventos de asignaciones
 * 3. calcularCargaTrabajoPorDia() - cálculo de horas por turno
 * =====================================================
 */

// Calcular configuración de slots basada en turnos
// Orden visual: Noche (00-08) → Mañana (08-16) → Tarde (16-24)
function calcularConfigTurnos(turnos) {
    const defaultConfig = {
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        slotDuration: '08:00:00',
        turnos: [],
    };

    if (!turnos || turnos.length === 0) {
        return defaultConfig;
    }

    // Filtrar turnos válidos (con hora_inicio y hora_fin)
    const turnosValidos = turnos.filter(t => t.hora_inicio && t.hora_fin);

    if (turnosValidos.length === 0) {
        return defaultConfig;
    }

    // Ordenar: noche primero, luego mañana, luego tarde
    const turnosOrdenados = [...turnosValidos].sort((a, b) => {
        const [hIniA] = a.hora_inicio.split(':').map(Number);
        const [hFinA] = a.hora_fin.split(':').map(Number);
        const [hIniB] = b.hora_inicio.split(':').map(Number);
        const [hFinB] = b.hora_fin.split(':').map(Number);

        const esNocturnoA = hFinA < hIniA;
        const esNocturnoB = hFinB < hIniB;

        if (esNocturnoA && !esNocturnoB) return -1;
        if (!esNocturnoA && esNocturnoB) return 1;
        return hIniA - hIniB;
    });

    return {
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00',
        slotDuration: '08:00:00',
        turnos: turnosOrdenados,
    };
}

function getVistaValida(claveLocalStorage, vistasPermitidas, vistaDefault) {
    const vista = localStorage.getItem(claveLocalStorage);
    return vistasPermitidas.includes(vista) ? vista : vistaDefault;
}

// Variable global para almacenar trabajadores ocupados en obras externas
let trabajadoresEnObrasExternas = {};

/**
 * Verifica qué trabajadores tienen asignación en obras externas (no Paco Reyes)
 * y almacena la información para mostrar indicadores visuales
 */
async function verificarOcupacionEnObrasExternas(start, end) {
    try {
        const response = await fetch(`/asignaciones-turno/ocupacion-cruzada?start=${start}&end=${end}&calendario=produccion`);
        const data = await response.json();

        if (data.success) {
            trabajadoresEnObrasExternas = data.ocupados || {};
            console.log('[cal] Trabajadores en obras externas:', trabajadoresEnObrasExternas);
        }
    } catch (error) {
        console.error('[cal] Error verificando ocupación en obras:', error);
    }
}

/**
 * Verifica si un trabajador está ocupado en obras externas
 */
function estaEnObraExterna(userId) {
    return trabajadoresEnObrasExternas[userId] !== undefined;
}

/**
 * Obtiene los días que un trabajador está en obra externa
 */
function diasEnObraExterna(userId) {
    return trabajadoresEnObrasExternas[userId]?.total_dias || 0;
}

/**
 * Obtiene los días formateados que un trabajador está en obra externa
 */
function diasEnObraExternaFormateados(userId) {
    const diasRaw = trabajadoresEnObrasExternas[userId]?.dias || [];
    const diasLista = Array.isArray(diasRaw) ? diasRaw : Object.values(diasRaw);

    return diasLista.map(fecha => {
        const partes = fecha.split('-');
        if (partes.length === 3) {
            const d = new Date(partes[0], partes[1] - 1, partes[2]);
            return d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
        }
        return fecha;
    }).join(', ');
}

export function initCalendar(domEl) {
    const { maquinas, eventos, cargaTrabajo, turnos } = DATA();

    // Calcular configuración de slots basada en turnos de la BD
    const configTurnos = calcularConfigTurnos(turnos);

    // Generar eventos de carga de trabajo (indicadores por día/máquina)
    const eventosCarga = generarEventosCarga(cargaTrabajo, turnos);

    // Combinar eventos de trabajadores con eventos de carga
    const todosEventos = [...eventos, ...eventosCarga];

    const VISTA_KEY = "vistaObras";
    const FECHA_KEY = "fechaObras";

    // Variable global para controlar si estamos arrastrando
    let isDragging = false;

    const calendar = new FullCalendar.Calendar(domEl, {
        schedulerLicenseKey: "CC-Attribution-NonCommercial-NoDerivatives",
        locale: "es",
        initialView: getVistaValida(
            VISTA_KEY,
            ["resourceTimelineDay", "resourceTimelineWeek"],
            "resourceTimelineWeek"
        ),
        initialDate: localStorage.getItem(FECHA_KEY) || undefined,
        selectable: true,
        unselectAuto: true,
        async datesSet(info) {
            // Guardar vista y fecha válidas en localStorage
            localStorage.setItem("vistaObras", info.view.type);
            localStorage.setItem("fechaObras", info.startStr);

            // Mostrar/ocultar eventos de carga según la vista
            const esVistaDia = info.view.type === "resourceTimelineDay";
            domEl.classList.toggle('vista-dia', esVistaDia);
            domEl.classList.toggle('vista-semana', !esVistaDia);

            // En vista día, sobrescribir el título para mostrar solo un día
            if (esVistaDia) {
                const titleEl = domEl.querySelector('.fc-toolbar-title');
                if (titleEl) {
                    const fecha = info.start;
                    const opciones = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                    titleEl.textContent = fecha.toLocaleDateString('es-ES', opciones);
                }
            }

            // Mostrar botón solo en vista semanal (si el botón existe)
            const btn = document.getElementById("btnRepetirSemana");
            if (btn) {
                if (info.view.type === "resourceTimelineWeek") {
                    btn.classList.remove("hidden");
                    btn.dataset.fecha = info.startStr;
                } else {
                    btn.classList.add("hidden");
                }
            }

            // Verificar trabajadores ocupados en obras externas
            const start = info.startStr.slice(0, 10);
            const end = info.endStr.slice(0, 10);
            await verificarOcupacionEnObrasExternas(start, end);
        },
        displayEventEnd: true,
        eventMinHeight: 30,
        firstDay: 1,
        height: "auto",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "resourceTimelineDay,resourceTimelineWeek",
        },
        buttonText: {
            today: "Hoy",
            week: "Semana",
            day: "Día",
        },
        slotLabelDidMount(info) {
            const viewType = info.view.type;
            if (viewType === "resourceTimelineDay" && configTurnos.turnos) {
                const hour = info.date.getHours();
                // Mapeo visual: 00-08=Noche, 08-16=Mañana, 16-24=Tarde
                let turnoIndex = 0;
                if (hour >= 8 && hour < 16) {
                    turnoIndex = 1; // Mañana
                } else if (hour >= 16) {
                    turnoIndex = 2; // Tarde
                }
                // Aplicar color del turno correspondiente
                const turno = configTurnos.turnos[turnoIndex];
                if (turno) {
                    info.el.style.backgroundColor = turno.color || '#e5e7eb';
                }
            }
        },
        slotLabelContent(info) {
            const viewType = info.view.type;
            if (viewType === "resourceTimelineDay") {
                const hour = info.date.getHours();
                // Mapeo visual: 00-08=Noche, 08-16=Mañana, 16-24=Tarde
                if (hour === 0) return { html: '<b>Noche</b>' };
                if (hour === 8) return { html: '<b>Mañana</b>' };
                if (hour === 16) return { html: '<b>Tarde</b>' };
            }
            return null; // Usar formato por defecto para otras vistas
        },
        views: {
            resourceTimelineDay: {
                slotMinTime: configTurnos.slotMinTime,
                slotMaxTime: configTurnos.slotMaxTime,
                slotDuration: configTurnos.slotDuration,
                // Mostrar solo un día en el título (no "30 nov - 1 dic")
                titleFormat: {
                    weekday: "long",
                    day: "numeric",
                    month: "long",
                    year: "numeric",
                },
            },
            resourceTimelineWeek: {
                slotDuration: { days: 1 },
                slotLabelFormat: { weekday: "long" },  // Solo: Lunes, Martes, etc.
            },
        },
        editable: true,
        resources: maquinas,
        resourceOrder: "orden",
        resourceAreaWidth: "100px",
        resourceLabelDidMount(info) {
            const color = info.resource.extendedProps.backgroundColor;
            if (color) {
                info.el.style.backgroundColor = color;
                info.el.style.color = "#fff";
            }
        },
        filterResourcesWithEvents: false,
        events: todosEventos,
        resourceAreaColumns: [{ field: "title", headerContent: "Máquinas" }],

        eventDragStart: (info) => {
            // Marcar que estamos arrastrando
            isDragging = true;

            // Solo deshabilitar (no destruir) el tooltip del elemento que se está arrastrando
            const el = info.el;
            if (el._tippy) {
                el._tippy.hide();
                el._tippy.disable();
            }

            // Deshabilitar todos los demás tooltips existentes
            document.querySelectorAll('.fc-event').forEach(eventEl => {
                if (eventEl._tippy) {
                    eventEl._tippy.disable();
                }
            });
        },

        eventDragStop: (info) => {
            // Marcar que terminamos de arrastrar
            isDragging = false;

            // Habilitar todos los tooltips después de un pequeño delay
            setTimeout(() => {
                document.querySelectorAll('.fc-event').forEach(eventEl => {
                    if (eventEl._tippy) {
                        eventEl._tippy.enable();
                    }
                });

                // Recrear tooltip si fue destruido durante el drag
                const el = info.el;
                const props = info.event.extendedProps || {};
                if (!el._tippy && props.foto && !props.es_festivo) {
                    const content = `<img src="${props.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`;
                    tippy(el, {
                        content,
                        allowHTML: true,
                        placement: "top",
                        theme: "transparent-avatar",
                        interactive: false,
                        arrow: false,
                        delay: [100, 0],
                        offset: [0, 10],
                        onShow() {
                            if (isDragging) return false;
                        },
                    });
                }
            }, 100);
        },

        eventDrop: async (info) => {
            const e = info.event;
            const props = e.extendedProps || {};
            try {
                if (props.es_festivo) {
                    const nuevaFecha = e.startStr.slice(0, 10);
                    await fetch(
                        R().festivo.update.replace("__ID__", props.festivo_id),
                        {
                            method: "PUT",
                            headers: {
                                "X-CSRF-TOKEN": window.AppPlanif.csrf,
                                Accept: "application/json",
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({ fecha: nuevaFecha }),
                        }
                    ).then((r) => {
                        if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    });
                    return;
                }
                const asignacionId = e.id.replace(/^turno-/, "");
                const recurso = e.getResources()?.[0];
                const nuevoMaquinaId = recurso
                    ? parseInt(recurso.id, 10)
                    : null;
                const nuevaHoraInicio = e.start?.toISOString();
                const hora = new Date(nuevaHoraInicio).getHours();

                // Determinar turno_id basado en la hora y los turnos de la BD
                let turnoId = null;
                for (const turno of configTurnos.turnos) {
                    const [hIni] = turno.hora_inicio.split(':').map(Number);
                    const [hFin] = turno.hora_fin.split(':').map(Number);
                    const esNocturno = hFin < hIni;

                    let enTurno = false;
                    if (esNocturno) {
                        // Turno nocturno: 22:00 - 06:00
                        enTurno = hora >= hIni || hora < hFin;
                    } else {
                        // Turnos normales
                        enTurno = hora >= hIni && hora < hFin;
                    }

                    if (enTurno) {
                        turnoId = turno.id;
                        break;
                    }
                }

                // Fallback si no se encontró turno
                if (!turnoId) {
                    turnoId = hora >= 6 && hora < 14 ? 1 : hora >= 14 && hora < 22 ? 2 : 3;
                }

                if (!nuevoMaquinaId || !nuevaHoraInicio)
                    throw new Error("Datos incompletos");

                const url = R().asignacion.updatePuesto.replace(
                    "__ID__",
                    asignacionId
                );
                const resp = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": window.AppPlanif.csrf,
                    },
                    body: JSON.stringify({
                        maquina_id: nuevoMaquinaId,
                        start: nuevaHoraInicio,
                        turno_id: turnoId,
                    }),
                });
                const data = await resp.json();
                if (!resp.ok)
                    throw new Error(data?.message || `HTTP ${resp.status}`);
                if (data.color) {
                    e.setProp("backgroundColor", data.color);
                    e.setProp("borderColor", data.color);
                }
                if (typeof data.nuevo_obra_id !== "undefined")
                    e.setExtendedProp("obra_id", data.nuevo_obra_id);

                // Actualizar turno_id y turno_nombre en el evento
                e.setExtendedProp("turno_id", turnoId);
                const turnoEncontrado = configTurnos.turnos.find(t => t.id === turnoId);
                if (turnoEncontrado) {
                    e.setExtendedProp("turno_nombre", turnoEncontrado.nombre);
                }

                // Forzar re-renderizado del evento para recrear el tooltip
                calendar.refetchEvents();
            } catch (err) {
                console.error(err);
                Swal.fire(
                    "Error",
                    err.message || "Ocurrió un error inesperado.",
                    "error"
                );
                info.revert();
            }
        },
        eventDidMount(info) {
            const e = info.event;
            const props = e.extendedProps || {};

            // Ignorar eventos de carga (sin tooltip, sin menú)
            if (props.es_carga) {
                // Solo tooltip simple con info
                info.el.title = `${props.horas}h de trabajo - Turno ${props.turno}`;
                return;
            }

            // Destruir tooltip anterior si existe
            if (info.el._tippy) {
                info.el._tippy.destroy();
            }

            if (props.foto && !props.es_festivo) {
                const content = `<img src="${props.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`;
                const tippyInstance = tippy(info.el, {
                    content,
                    allowHTML: true,
                    placement: "top",
                    theme: "transparent-avatar",
                    interactive: false,
                    arrow: false,
                    delay: [100, 0],
                    offset: [0, 10],
                    // No mostrar tooltip si estamos arrastrando
                    onShow() {
                        if (isDragging) {
                            return false;
                        }
                    },
                });

                // Si estamos arrastrando, deshabilitar inmediatamente
                if (isDragging && tippyInstance) {
                    tippyInstance.disable();
                }
            }

            info.el.addEventListener("contextmenu", (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                if (props.es_festivo) {
                    openFestivoMenu(ev.clientX, ev.clientY, {
                        event: e,
                        titulo: e.title,
                    });
                } else {
                    openWorkerMenu(ev.clientX, ev.clientY, e);
                }
            });
        },

        eventClick(info) {
            const e = info.event;
            const props = e.extendedProps || {};
            if (props.es_festivo) return;
            const userId = props.user_id;
            if (userId) {
                const url = R().userShow.replace(":id", userId);
                window.location.href = url;
            }
        },

        eventContent(arg) {
            const p = arg.event.extendedProps;

            // Eventos de carga: formato simple
            if (p?.es_carga) {
                return {
                    html: `<div class="carga-content">${p.horas}h</div>`,
                };
            }

            // Festivos
            if (p?.es_festivo) {
                return {
                    html: `<div class="px-2 py-1 text-xs font-semibold" style="color:#fff">${arg.event.title}</div>`,
                };
            }

            // Eventos de asignación de turno (trabajadores)
            const horas =
                p.entrada && p.salida
                    ? `${p.entrada} / ${p.salida}`
                    : p.entrada || p.salida || "-- / --";
            const turnoNombre = p.turno_nombre ? p.turno_nombre.charAt(0).toUpperCase() + p.turno_nombre.slice(1) : '';

            // Verificar si el trabajador está en obra externa
            const userId = p.user_id;
            const enObraExterna = estaEnObraExterna(userId);
            const diasObra = diasEnObraExterna(userId);
            const diasObraFormateados = diasEnObraExternaFormateados(userId);
            const badgeObra = enObraExterna
                ? `<span class="ml-1 px-1 py-0.5 bg-green-500 text-white text-[8px] rounded font-bold" title="En obra: ${diasObraFormateados}">🏗️${diasObra}</span>`
                : '';

            return {
                html: `
          <div class="px-2 py-1 text-xs font-semibold flex items-center ${enObraExterna ? 'tiene-obra-externa' : ''}">
            <div class="flex flex-col">
              <span>${arg.event.title} <span class="text-[10px] font-medium opacity-70">[${turnoNombre}]</span>${badgeObra}</span>
              <span class="text-[10px] font-normal opacity-80">(${
                  p.categoria_nombre ?? ""
              } 🛠 ${p.especialidad_nombre ?? "Sin especialidad"})</span>
            </div>
            <div class="ml-auto text-right">
              <span class="text-[10px] font-normal opacity-80">${horas}</span>
            </div>
          </div>`,
            };
        },
    });

    calendar.render();

    // Filtro de búsqueda de trabajadores (eventos)
    const filtroEventosInput = document.getElementById('filtro-eventos');
    if (filtroEventosInput) {
        filtroEventosInput.addEventListener('input', function() {
            const texto = this.value.toLowerCase().trim();

            // Obtener todos los eventos del calendario
            const todosEventos = calendar.getEvents();

            todosEventos.forEach(evento => {
                const props = evento.extendedProps || {};
                // Ignorar eventos de carga y festivos
                if (props.es_carga || props.es_festivo) return;

                const titulo = (evento.title || '').toLowerCase();
                const categoria = (props.categoria_nombre || '').toLowerCase();
                const especialidad = (props.especialidad_nombre || '').toLowerCase();

                const coincide = !texto ||
                    titulo.includes(texto) ||
                    categoria.includes(texto) ||
                    especialidad.includes(texto);

                // Mostrar/ocultar usando display del elemento DOM
                const eventEl = document.querySelector(`[data-event-id="${evento.id}"]`);
                if (eventEl) {
                    eventEl.style.display = coincide ? '' : 'none';
                }

                // Alternativa: usar setProp para cambiar visibilidad
                evento.setProp('display', coincide ? 'auto' : 'none');
            });
        });
    }

    console.log("[cal] Configurando event listener para contextmenu...");

    const root = calendar.el;
    console.log("[cal] Elemento raíz del calendario:", root);

    // Siempre agregar el event listener (el destroy debería limpiar el elemento)
    console.log("[cal] Agregando event listener de contextmenu al calendario");

    root.addEventListener("contextmenu", (ev) => {
            console.log("[cal] ¡Contextmenu disparado!", ev.target);

            if (ev.target.closest(".fc-event")) {
                console.log("[cal] Es un evento, ignorando");
                return;
            }

            ev.preventDefault();

            // Método alternativo: encontrar el recurso por posición vertical
            let resourceId = null;
            let fechaISO = null;

            // Primero intentar obtener la fecha del elemento clickeado
            let horaISO = null;
            const dateEl = ev.target.closest("[data-date]");
            if (dateEl) {
                const dataDate = dateEl.getAttribute("data-date") || "";
                // Extraer fecha y hora si viene completa (ej: "2025-12-11T08:00:00")
                if (dataDate.includes('T')) {
                    fechaISO = dataDate.slice(0, 10);
                    horaISO = dataDate.slice(11, 16); // HH:MM
                } else {
                    fechaISO = dataDate.slice(0, 10);
                }
            }

            // Si no hay hora, intentar obtenerla de la vista actual del calendario
            if (!horaISO && calendar.view.type === 'resourceTimelineDay') {
                // Usar el API del calendario para obtener la fecha/hora del punto clickeado
                const calendarApi = calendar;
                const dateAtClick = calendarApi.getDate();
                // Intentar usar el método dateFromPoint si existe
                if (typeof calendarApi.el.getBoundingClientRect === 'function') {
                    // Obtener la posición X relativa al calendario
                    const calRect = calendarApi.el.getBoundingClientRect();
                    const timelineBody = calendarApi.el.querySelector('.fc-timeline-body');
                    if (timelineBody) {
                        const bodyRect = timelineBody.getBoundingClientRect();
                        const relX = ev.clientX - bodyRect.left;
                        const totalWidth = bodyRect.width;
                        // El día tiene 24 horas, calcular la hora según la posición
                        const horaDecimal = (relX / totalWidth) * 24;
                        const hora = Math.floor(horaDecimal);
                        horaISO = String(hora).padStart(2, '0') + ':00';
                        console.log("[cal] Hora calculada por posición X:", horaISO);
                    }
                }
            }

            if (!fechaISO) {
                console.log("[cal] No se pudo determinar la fecha");
                return;
            }

            console.log("[cal] Fecha encontrada:", fechaISO, "Hora:", horaISO);
            console.log("[cal] Elemento clickeado:", ev.target);
            console.log("[cal] Elemento con data-date:", dateEl);

            // Intentar obtener el recurso desde la posición Y del click
            // Buscar todas las filas de recursos en el área izquierda
            const resourceLanes = root.querySelectorAll('.fc-timeline-lane[data-resource-id]');
            console.log("[cal] Filas de recursos encontradas:", resourceLanes.length);

            if (resourceLanes.length > 0) {
                const clickY = ev.clientY;
                console.log("[cal] Posición Y del click:", clickY);

                for (const lane of resourceLanes) {
                    const rect = lane.getBoundingClientRect();
                    console.log("[cal] Examinando lane con resource-id:", lane.dataset.resourceId, "top:", rect.top, "bottom:", rect.bottom);

                    if (clickY >= rect.top && clickY <= rect.bottom) {
                        resourceId = lane.dataset.resourceId;
                        console.log("[cal] ¡ResourceId encontrado por posición Y!:", resourceId);
                        break;
                    }
                }
            }

            console.log("[cal] ResourceId final detectado:", resourceId, "Fecha:", fechaISO, "Hora:", horaISO);

            openCellMenu(
                ev.clientX,
                ev.clientY,
                { fechaISO, resourceId, horaISO },
                calendar,
                maquinas
            );
        });

    console.log("[cal] Event listener de contextmenu agregado correctamente");

    return calendar;
}
