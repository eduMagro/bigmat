// public/js/calendario/calendario.js
(function () {
    const qsAll = (sel, ctx = document) =>
        Array.from(ctx.querySelectorAll(sel));

    // Esperar a que FullCalendar esté disponible (máximo 5 segundos)
    function waitForFullCalendar(callback, maxAttempts = 50) {
        let attempts = 0;
        const check = () => {
            if (typeof FullCalendar !== "undefined") {
                callback();
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(check, 100);
            } else {
                console.error("FullCalendar no se cargó después de 5 segundos.");
            }
        };
        check();
    }
    const addDaysStr = (d, days) => {
        const x = new Date(d);
        x.setDate(x.getDate() + days);
        return x.toISOString().split("T")[0];
    };
    const addOneDayStr = (d) => addDaysStr(d, 1);

    function normalizeDailyEvents(events) {
        // Cada evento es individual por día - sin fusionar días consecutivos
        // NO añadimos 'end' para que FullCalendar los trate como eventos de un solo día
        return events.map((ev) => {
            const startISO = ev.startStr || ev.start || ev.startTime || ev.startDate;
            // Extraer solo la fecha (YYYY-MM-DD) sin hora
            let startStr;
            if (typeof startISO === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(startISO)) {
                // Ya es formato YYYY-MM-DD
                startStr = startISO;
            } else {
                const startDate = new Date(startISO);
                startStr = startDate.toISOString().split("T")[0];
            }

            // Devolver evento sin 'end' para que sea de un solo día
            const normalized = {
                ...ev,
                start: startStr,
                allDay: true,
            };
            // Eliminar 'end' si existe para evitar que se extienda a varios días
            delete normalized.end;
            delete normalized.endStr;
            return normalized;
        });
    }

    function actualizarResumenAsistencia(resumenUrl) {
        if (!resumenUrl) return;
        fetch(resumenUrl)
            .then((r) => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then((data) => {
                const div = document.getElementById("resumen-asistencia");
                if (!div) return;
                div.style.opacity = 0.5;
                div.innerHTML = `
                    <p><strong>Vacaciones asignadas: </strong> ${data.diasVacaciones}</p>
                    <p><strong>Faltas injustificadas: </strong> ${data.faltasInjustificadas}</p>
                    <p><strong>Faltas justificadas: </strong> ${data.faltasJustificadas}</p>
                    <p><strong>Días de baja: </strong> ${data.diasBaja}</p>`;
                setTimeout(() => (div.style.opacity = 1), 200);
            })
            .catch((e) => console.error("Error resumen asistencia:", e));
    }

    // --- Refetch con debounce (global) ---
    let refetchTimer = null;
    function smartRefetch(calendar, extraCb) {
        clearTimeout(refetchTimer);
        refetchTimer = setTimeout(() => {
            calendar.refetchEvents();
            if (typeof extraCb === "function") extraCb();
        }, 120); // ajusta (80–200ms)
    }

    function initCalendarOn(el) {
        let cfg = {};
        try {
            cfg = JSON.parse(el.getAttribute("data-config") || "{}");
        } catch (e) {
            console.error("data-config inválido", e);
            return;
        }

        const {
            locale = "es",
            csrfToken = "",
            routes = {},
            enableListMonth = true,
            permissions = {
                canRequestVacations: false,
                canEditHours: false,
                canAssignShifts: false,
                canAssignStates: false,
            },
            turnos = [], // opcional
            userId = null,
        } = cfg;

        let {
            fechaIncorporacion = null,
            diasVacacionesAsignados = 0,
        } = cfg;

        if (typeof fechaIncorporacion === 'undefined') fechaIncorporacion = null; // Safety check

        console.log('Config Calendario:', { userId, permissions, fechaIncorporacion, diasVacacionesAsignados });

        // Estado de selección "clic-clic"
        let startClick = null;
        let hoverDayEvs = [];

        function ensureTempEvents(calendar) {
            if (hoverRangeEv && hoverStartEv && hoverEndEv) return;
            calendar.batchRendering(() => {
                hoverRangeEv = calendar.addEvent({
                    start: null,
                    end: null,
                    display: "background",
                    overlap: true,
                    classNames: ["bg-select-range"],
                    __tempHover: true,
                });
                hoverStartEv = calendar.addEvent({
                    start: null,
                    end: null,
                    display: "background",
                    overlap: true,
                    classNames: ["bg-select-endpoint"],
                    __tempHover: true,
                });
                hoverEndEv = calendar.addEvent({
                    start: null,
                    end: null,
                    display: "background",
                    overlap: true,
                    classNames: ["bg-select-endpoint"],
                    __tempHover: true,
                });
            });
        }

        function clearVacationBadges() {
            // Limpiar modal INFERIOR
            const modal = document.getElementById('vacation-bottom-modal');
            if (modal) {
                modal.classList.remove('translate-y-0');
                modal.classList.add('translate-y-full');
            }
        }

        function clearTempHighlight(calendar, keepBadges = false) {
            if (!keepBadges) clearVacationBadges();
            if (!hoverDayEvs.length) return;
            calendar.batchRendering(() =>
                hoverDayEvs.forEach((ev) => ev.remove())
            );
            hoverDayEvs = [];
        }

        function eachDayStr(aStr, bStr) {
            const days = [];
            let a = new Date(aStr),
                b = new Date(bStr);
            if (a > b) [a, b] = [b, a];
            for (let d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
                days.push(d.toISOString().split("T")[0]);
            }
            return days;
        }

        // pilla el día anterior al siguiente en string YYYY-MM-DD
        const addOneDayStr = (d) => {
            const x = new Date(d);
            x.setDate(x.getDate() + 1);
            return x.toISOString().split("T")[0];
        };

        function updateTempHighlight(calendar, startStr, hoverStr, isHover = true) {
            const forward = startStr <= hoverStr;
            const days = eachDayStr(startStr, hoverStr);
            const first = days[0];
            const last = days[days.length - 1];

            clearTempHighlight(calendar, isHover);

            calendar.batchRendering(() => {
                days.forEach((d) => {
                    const isFirst = d === first;
                    const isLast = d === last;
                    const classes = [];

                    if (isFirst || isLast) {
                        classes.push("bg-select-endpoint");
                        if (isFirst)
                            classes.push(
                                forward
                                    ? "bg-select-endpoint-left"
                                    : "bg-select-endpoint-right"
                            );
                        if (isLast)
                            classes.push(
                                forward
                                    ? "bg-select-endpoint-right"
                                    : "bg-select-endpoint-left"
                            );
                    } else {
                        classes.push("bg-select-range");
                    }

                    const ev = calendar.addEvent({
                        start: d,
                        end: addOneDayStr(d),
                        display: "background",
                        overlap: true,
                        classNames: classes,
                        __tempHover: true,
                    });
                    hoverDayEvs.push(ev);
                });
            });
        }

        const storageKeyPrefix = el.id
            ? `fc:${el.id}:`
            : `fc:${Math.random().toString(36).slice(2)}:`;
        const vistasDisponibles = [
            "dayGridMonth",
            "timeGridWeek",
            "timeGridDay",
            "listWeek",
            "listMonth",
        ];
        let vistaGuardada = localStorage.getItem(storageKeyPrefix + "vista");
        if (!vistasDisponibles.includes(vistaGuardada))
            vistaGuardada = "dayGridMonth";
        const fechaGuardada = localStorage.getItem(storageKeyPrefix + "fecha");

        // --- Acciones por rol ---
        async function pedirVacaciones(fechaInicio, fechaFin, calendar) {
            const msg =
                fechaInicio === fechaFin
                    ? `<p>${fechaInicio}</p>`
                    : `<p>Desde: ${fechaInicio}</p><p>Hasta: ${fechaFin}</p>`;
            const { isConfirmed } = await Swal.fire({
                title: "Solicitar vacaciones",
                html: `${msg}<p class="mt-2 text-sm text-gray-600">Se enviará una solicitud para revisión.</p>`,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Enviar solicitud",
                cancelButtonText: "Cancelar",
            });
            if (!isConfirmed) return;
            if (!routes.vacacionesStoreUrl) {
                Swal.fire(
                    "Error",
                    "Ruta de solicitud de vacaciones no configurada.",
                    "error"
                );
                return;
            }
            fetch(routes.vacacionesStoreUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                }),
            })
                .then(async (res) => {
                    const ct = res.headers.get("content-type") || "";
                    const data = ct.includes("application/json")
                        ? await res.json()
                        : {};
                    if (!res.ok || data.error)
                        throw new Error(data.error || `HTTP ${res.status}`);
                    Swal.fire(
                        "Solicitud enviada",
                        data.success || "Tu solicitud ha sido registrada.",
                        "success"
                    ).then(() => {
                        smartRefetch(calendar, () =>
                            actualizarResumenAsistencia(routes.resumenUrl)
                        );
                    });
                })
                .catch((err) =>
                    Swal.fire(
                        "Error",
                        err.message || "No se pudo enviar la solicitud.",
                        "error"
                    )
                );
        }

        async function registrarEventoOficina(fechaInicio, fechaFin, calendar) {
            const opcionesTurnos = (turnos || [])
                .map((t) => `<option value="${t.nombre}">${t.nombre}</option>`)
                .join("");

            const esMismoDia = fechaInicio === fechaFin;
            const mensajeFecha = esMismoDia
                ? `<p class="mb-2">${fechaInicio}</p>`
                : `<p class="mb-2">Desde: ${fechaInicio} — Hasta: ${fechaFin}</p>`;

            // Si es un solo día, buscar horas existentes en los eventos del calendario
            let entradaExistente = '';
            let salidaExistente = '';
            if (esMismoDia) {
                const eventos = calendar.getEvents();
                eventos.forEach(ev => {
                    const props = ev.extendedProps || {};
                    if (props.fecha === fechaInicio || (ev.startStr && ev.startStr.startsWith(fechaInicio))) {
                        if (props.entrada && !entradaExistente) {
                            entradaExistente = props.entrada.substring(0, 5);
                        }
                        if (props.salida && !salidaExistente) {
                            salidaExistente = props.salida.substring(0, 5);
                        }
                    }
                });
            }

            const { value: formData, isConfirmed } = await Swal.fire({
                title: null,
                html: `
                    <div style="text-align: left; overflow-x: hidden;">
                        <!-- Header con fecha -->
                        <div style="background: linear-gradient(135deg, #1e3a5f 0%, #111827 100%); color: white; margin: -20px -20px 20px -20px; padding: 20px; border-radius: 8px 8px 0 0;">
                            <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600;">Gestionar Asignacion</h3>
                            <p style="margin: 0; font-size: 14px; opacity: 0.9;">
                                ${esMismoDia ? fechaInicio : `${fechaInicio} → ${fechaFin}`}
                            </p>
                        </div>

                        <!-- Selector de turno/estado -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Turno o Estado
                            </label>
                            <select id="tipo-dia" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: white; cursor: pointer; transition: border-color 0.2s;">
                                <option value="">Solo actualizar horas</option>
                                <option value="eliminarTurnoEstado">Eliminar turno</option>
                                ${opcionesTurnos}
                                <option disabled style="font-size: 8px;">─────────</option>
                                <option value="eliminarEstado">Eliminar estado</option>
                                <option value="curso">Cursos</option>
                                <option value="vacaciones">Vacaciones</option>
                                <option value="baja">Baja</option>
                                <option value="justificada">Justificada</option>
                                <option value="injustificada">Injustificada</option>
                            </select>
                        </div>

                        <!-- Campos de hora -->
                        <div style="background: #f9fafb; border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb;">
                            <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                Horario de fichaje
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #059669; margin-bottom: 6px; font-weight: 500;">
                                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
                                        Entrada
                                    </label>
                                    <input type="time" id="hora-entrada" value="${entradaExistente}"
                                        style="width: 100%; padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; font-family: monospace; transition: border-color 0.2s;">
                                </div>
                                <div>
                                    <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #dc2626; margin-bottom: 6px; font-weight: 500;">
                                        <span style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%; display: inline-block;"></span>
                                        Salida
                                    </label>
                                    <input type="time" id="hora-salida" value="${salidaExistente}"
                                        style="width: 100%; padding: 10px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; font-family: monospace; transition: border-color 0.2s;">
                                </div>
                            </div>
                            ${!esMismoDia ? `
                                <p style="margin: 12px 0 0 0; font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 6px;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Deja vacio para mantener las horas actuales
                                </p>
                            ` : ''}
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: "Guardar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#6b7280",
                width: 420,
                padding: "20px",
                customClass: {
                    popup: 'swal-calendario-popup',
                    confirmButton: 'swal-btn-confirm',
                    cancelButton: 'swal-btn-cancel'
                },
                preConfirm: () => {
                    return {
                        tipo: document.getElementById("tipo-dia").value,
                        entrada: document.getElementById("hora-entrada").value || null,
                        salida: document.getElementById("hora-salida").value || null,
                    };
                },
                didOpen: () => {
                    // Añadir efectos hover a los inputs
                    const inputs = document.querySelectorAll('#hora-entrada, #hora-salida, #tipo-dia');
                    inputs.forEach(input => {
                        input.addEventListener('focus', () => input.style.borderColor = '#3b82f6');
                        input.addEventListener('blur', () => input.style.borderColor = '#e5e7eb');
                    });
                }
            });

            if (!isConfirmed || !formData) return;

            const tipoSeleccionado = formData.tipo;
            const horaEntrada = formData.entrada;
            const horaSalida = formData.salida;

            // --- Solo actualizar horas (sin cambiar turno/estado) ---
            if (!tipoSeleccionado) {
                // Validar que al menos una hora esté especificada
                if (!horaEntrada && !horaSalida) {
                    Swal.fire("Aviso", "Debes especificar al menos una hora para actualizar.", "warning");
                    return;
                }

                const body = {
                    user_id: userId,
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    tipo: "soloHoras",
                };
                if (horaEntrada) body.entrada = horaEntrada;
                if (horaSalida) body.salida = horaSalida;

                fetch(routes.storeUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify(body),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            smartRefetch(calendar, () =>
                                actualizarResumenAsistencia(routes.resumenUrl)
                            );
                            Swal.fire("Actualizado", "Horas actualizadas correctamente.", "success");
                        } else {
                            Swal.fire("Error", data.error || "No se pudieron actualizar las horas.", "error");
                        }
                    })
                    .catch((err) => {
                        console.error("Error:", err);
                        Swal.fire("Error", "Ocurrio un problema al actualizar las horas.", "error");
                    });
                return;
            }

            // --- Lógica de eliminación ---
            if (
                tipoSeleccionado === "eliminarTurnoEstado" ||
                tipoSeleccionado === "eliminarEstado"
            ) {
                const mensajeConfirmacion =
                    tipoSeleccionado === "eliminarTurnoEstado"
                        ? "Estas seguro de que quieres eliminar el turno? Esto tambien eliminara cualquier estado asignado (vacaciones, baja...) y las horas de entrada y salida."
                        : "Seguro que quieres eliminar solo el estado? Las horas de entrada y salida y el turno se mantendran.";

                const confirmacion = await Swal.fire({
                    title: "Confirmar eliminacion",
                    text: mensajeConfirmacion,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Si, eliminar",
                    cancelButtonText: "Cancelar",
                });

                if (!confirmacion.isConfirmed) return;

                const body = {
                    fecha_inicio: fechaInicio,
                    fecha_fin: fechaFin,
                    user_id: userId,
                    tipo: tipoSeleccionado,
                };

                fetch(routes.destroyUrl, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                        },
                        body: JSON.stringify(body),
                    }
                )
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            smartRefetch(calendar, () =>
                                actualizarResumenAsistencia(routes.resumenUrl)
                            );

                            Swal.fire("Eliminado", data.success, "success");
                        } else {
                            Swal.fire(
                                "Error",
                                data.error || "No se pudo eliminar el turno.",
                                "error"
                            );
                        }
                    })
                    .catch((err) => {
                        console.error("Error:", err);
                        Swal.fire(
                            "Error",
                            "Ocurrio un problema al eliminar los turnos.",
                            "error"
                        );
                    });

                return;
            }

            // --- Lógica de asignación nueva ---
            const body = {
                user_id: userId,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                tipo: tipoSeleccionado,
            };

            // Añadir horas solo si se han especificado
            if (horaEntrada) body.entrada = horaEntrada;
            if (horaSalida) body.salida = horaSalida;

            fetch(routes.storeUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(body),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        smartRefetch(calendar, () =>
                            actualizarResumenAsistencia(routes.resumenUrl)
                        );

                        Swal.fire("Registrado", data.success, "success");
                    } else {
                        Swal.fire(
                            "Error",
                            data.error || "No se pudo registrar el evento.",
                            "error"
                        );
                    }
                })
                .catch((err) => {
                    console.error("Error:", err);
                    Swal.fire(
                        "Error",
                        "Ocurrio un problema al registrar el turno.",
                        "error"
                    );
                });
        }

        const rightButtons = enableListMonth
            ? "dayGridMonth,listMonth"
            : "dayGridMonth";

        const calendar = new FullCalendar.Calendar(el, {
            locale,
            initialView: vistaGuardada,
            initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
            firstDay: 1,
            height: "auto",
            selectable: false, // drag-select desactivado
            selectMirror: false,
            displayEventTime: false, // La hora ya está en el título
            displayEventEnd: false,
            eventDisplay: 'block', // Mostrar eventos timed como bloques
            nextDayThreshold: '00:00:00', // Eventos que terminan a medianoche no pasan al día siguiente
            forceEventDuration: true, // Forzar duración por defecto
            defaultAllDayEventDuration: { days: 1 }, // Duración por defecto de 1 día
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: rightButtons,
            },
            buttonText: {
                today: "Hoy",
                dayGridMonth: "Mes",
                listMonth: "Lista",
            },

            events: function (fetchInfo, success, failure) {
                if (!routes.eventosUrl) {
                    success([]);
                    return;
                }
                fetch(routes.eventosUrl)
                    .then((r) => r.json())
                    .then((events) => {
                        console.log('Eventos recibidos del servidor:', events.length);

                        // Separar eventos allDay de eventos con hora (fichajes)
                        const allDayEvents = events.filter(ev => ev.allDay !== false);
                        const timedEvents = events.filter(ev => ev.allDay === false);

                        console.log('Eventos allDay:', allDayEvents.length, 'Eventos con hora:', timedEvents.length);

                        // Normalizar solo los eventos allDay
                        const normalized = normalizeDailyEvents(allDayEvents);

                        // Verificar que no hay eventos con end multi-día
                        normalized.forEach(ev => {
                            if (ev.end) {
                                console.warn('Evento con end:', ev.id, ev.start, ev.end);
                            }
                        });

                        // Combinar ambos tipos
                        const final = [...normalized, ...timedEvents];
                        console.log('Total eventos a renderizar:', final.length);
                        success(final);
                    })
                    .catch(failure);
            },

            // Clic-clic para rango en ambos roles
            dateClick: function (info) {
                const clicked = info.dateStr;

                if (!startClick) {
                    // --- PRIMER CLIC ---
                    startClick = clicked;
                    updateTempHighlight(calendar, clicked, clicked, false); // false para que SI limpie/actualice el modal

                    // AJAX Fetch para datos frescos de vacaciones (solo en el primer clic)
                    // Enviar la fecha clickeada para que el backend calcule relativo a esa fecha
                    const baseUrl = routes.vacationDataUrl || `/usuarios/${userId}/vacation-data`;
                    const fetchUrl = `${baseUrl}?fecha=${clicked}`;
                    fetch(fetchUrl)
                        .then(r => {
                            if (!r.ok) {
                                throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                            }
                            const contentType = r.headers.get('content-type') || '';
                            if (!contentType.includes('application/json')) {
                                throw new Error('La respuesta no es JSON');
                            }
                            return r.json();
                        })
                        .then(data => {
                            if (data.error) throw new Error(data.error);

                            fechaIncorporacion = data.fecha_incorporacion;
                            diasVacacionesAsignados = data.dias_asignados;

                            const modal = document.getElementById('vacation-bottom-modal');
                            const content = document.getElementById('vacation-bottom-content');

                            if (fechaIncorporacion) {
                                const incorpDate = new Date(fechaIncorporacion);
                                const clickDate = new Date(clicked);

                                if (clickDate >= incorpDate) {
                                    const clickYear = clickDate.getFullYear();
                                    const clickMonth = clickDate.getMonth(); // 0-indexed (0=enero, 2=marzo)

                                    // Detectar período de gracia: 1 enero - 31 marzo
                                    const isGracePeriod = clickMonth <= 2; // enero, febrero, marzo
                                    const previousYear = clickYear - 1;

                                    if (modal && content) {
                                        modal.classList.remove('translate-y-full');
                                        modal.classList.add('translate-y-0');

                                        // Calcular vacaciones GENERADAS del año anterior (hasta 31 dic)
                                        const endOfPrevYear = new Date(previousYear, 11, 31);
                                        let generadasAnterior = 0;

                                        if (incorpDate < new Date(clickYear, 0, 1)) {
                                            // La persona ya trabajaba antes de este año
                                            const prevYearStart = incorpDate > new Date(previousYear, 0, 1) ? incorpDate : new Date(previousYear, 0, 1);
                                            const diffTimePrev = Math.max(0, endOfPrevYear - prevYearStart);
                                            const diffDaysPrev = Math.ceil(diffTimePrev / (1000 * 60 * 60 * 24)) + 1;
                                            generadasAnterior = Math.floor(Math.min((diffDaysPrev / 30) * 2.5, 30)); // Truncado, Max 30 días
                                        }

                                        // Días usados del año anterior (en fechas del año anterior)
                                        const usadasAnteriorDirec = data.dias_asignados_anterior || 0;

                                        // Saldo del año anterior AL FINALIZAR el año anterior
                                        const saldoAnteriorAlFinalizar = generadasAnterior - usadasAnteriorDirec;

                                        // Días usados durante el período de gracia (1 ene - 31 mar del año actual)
                                        const usadasPeriodoGracia = data.dias_usados_periodo_gracia || 0;

                                        // Días usados después del período de gracia (1 abril en adelante)
                                        const usadasPostGracia = data.dias_usados_post_gracia || 0;

                                        if (isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                                            // === PERÍODO DE GRACIA (1 ene - 31 mar) ===
                                            // Las vacaciones del período de gracia se descuentan PRIMERO del año anterior

                                            // Cuántas del año anterior quedan después de descontar las del período de gracia
                                            const usadasDelAnterior = Math.min(usadasPeriodoGracia, Math.max(0, saldoAnteriorAlFinalizar));
                                            const disponiblesAnterior = Math.max(0, saldoAnteriorAlFinalizar - usadasPeriodoGracia);

                                            // Si usó más que las del año anterior, el exceso viene del año actual
                                            const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - Math.max(0, saldoAnteriorAlFinalizar));

                                            // Días generados del año ACTUAL (desde 1 enero hasta fecha clickeada)
                                            const startOfCurrentYear = new Date(clickYear, 0, 1);
                                            const diffTimeCurrent = Math.max(0, clickDate - startOfCurrentYear);
                                            const diffDaysCurrent = Math.ceil(diffTimeCurrent / (1000 * 60 * 60 * 24)) + 1;
                                            const generadasActual = Math.floor((diffDaysCurrent / 30) * 2.5); // Truncado

                                            // Disponibles del año actual = generadas - exceso que se usó del actual
                                            const disponiblesActual = generadasActual - excesoSobreAnterior;
                                            const disponiblesTotal = disponiblesAnterior + disponiblesActual;

                                            // === SIN VACACIONES FUTURAS ===
                                            const usadasPeriodoGraciaHastaFecha = data.dias_usados_periodo_gracia_hasta_fecha || 0;
                                            const usadasDelAnteriorSinFuturas = Math.min(usadasPeriodoGraciaHastaFecha, Math.max(0, saldoAnteriorAlFinalizar));
                                            const disponiblesAnteriorSinFuturas = Math.max(0, saldoAnteriorAlFinalizar - usadasPeriodoGraciaHastaFecha);
                                            const excesoSobreAnteriorSinFuturas = Math.max(0, usadasPeriodoGraciaHastaFecha - Math.max(0, saldoAnteriorAlFinalizar));
                                            const disponiblesActualSinFuturas = generadasActual - excesoSobreAnteriorSinFuturas;
                                            const disponiblesTotalSinFuturas = disponiblesAnteriorSinFuturas + disponiblesActualSinFuturas;

                                            const colorAnterior = disponiblesAnterior >= 0 ? 'text-amber-400' : 'text-red-400';
                                            const colorActual = disponiblesActual >= 0 ? 'text-green-400' : 'text-red-400';
                                            const colorTotal = disponiblesTotal >= 0 ? 'text-emerald-400' : 'text-red-400';

                                            // Mostrar "sin futuras" solo si hay diferencia
                                            const sinFuturasHtml = disponiblesTotal !== disponiblesTotalSinFuturas
                                                ? `<span class="text-xs text-gray-500">(${disponiblesTotalSinFuturas} sin futuras)</span>`
                                                : '';

                                            content.innerHTML = `
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-sm">
                                                    <div class="flex flex-col items-center px-3 py-1 bg-gray-800 rounded-lg">
                                                        <span class="text-xs text-gray-500">${previousYear}</span>
                                                        <span class="${colorAnterior} font-semibold">${disponiblesAnterior} dias</span>
                                                        <span class="text-xs text-gray-500">(${saldoAnteriorAlFinalizar} - ${usadasDelAnterior})</span>
                                                    </div>
                                                    <span class="text-gray-600 hidden sm:block">+</span>
                                                    <div class="flex flex-col items-center px-3 py-1 bg-gray-800 rounded-lg">
                                                        <span class="text-xs text-gray-500">${clickYear}</span>
                                                        <span class="${colorActual} font-semibold">${disponiblesActual} dias</span>
                                                        <span class="text-xs text-gray-500">(${generadasActual} - ${excesoSobreAnterior})</span>
                                                    </div>
                                                    <span class="text-gray-600 hidden sm:block">=</span>
                                                    <div class="flex flex-col items-center px-3 py-1 bg-gradient-to-r from-gray-800 to-gray-700 rounded-lg border border-gray-600">
                                                        <span class="text-xs text-gray-400">Total</span>
                                                        <span class="${colorTotal} font-bold text-lg">${disponiblesTotal} dias</span>
                                                        ${sinFuturasHtml}
                                                    </div>
                                                </div>
                                            `;
                                        } else if (!isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                                            // === DESPUÉS DEL PERÍODO DE GRACIA (1 abril en adelante) ===
                                            // Las vacaciones del año anterior CADUCAN
                                            // Solo cuentan las del año actual (desde 1 enero hasta fecha clickeada)

                                            // Las vacaciones del período de gracia se consumieron del año anterior primero
                                            const usadasDelAnteriorEnGracia = Math.min(usadasPeriodoGracia, Math.max(0, saldoAnteriorAlFinalizar));
                                            const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - Math.max(0, saldoAnteriorAlFinalizar));

                                            // Vacaciones perdidas del año anterior (las que no se usaron y caducaron)
                                            const perdidas = Math.max(0, saldoAnteriorAlFinalizar - usadasPeriodoGracia);

                                            // Días generados del año ACTUAL (desde 1 enero hasta fecha clickeada)
                                            const startOfCurrentYear = new Date(clickYear, 0, 1);
                                            const diffTimeCurrent = Math.max(0, clickDate - startOfCurrentYear);
                                            const diffDaysCurrent = Math.ceil(diffTimeCurrent / (1000 * 60 * 60 * 24)) + 1;
                                            const generadasActual = Math.floor((diffDaysCurrent / 30) * 2.5); // Truncado

                                            // Total usadas del año actual = exceso del periodo gracia + usadas post gracia
                                            const usadasTotalActual = excesoSobreAnterior + usadasPostGracia;
                                            const disponiblesActual = generadasActual - usadasTotalActual;

                                            // === SIN VACACIONES FUTURAS ===
                                            const usadasPostGraciaHastaFecha = data.dias_usados_post_gracia_hasta_fecha || 0;
                                            const usadasTotalActualSinFuturas = excesoSobreAnterior + usadasPostGraciaHastaFecha;
                                            const disponiblesActualSinFuturas = generadasActual - usadasTotalActualSinFuturas;

                                            const colorClass = disponiblesActual >= 0 ? 'text-green-400' : 'text-red-400';

                                            let perdidasHtml = '';
                                            if (perdidas > 0) {
                                                perdidasHtml = `<span class="text-xs text-red-400">(${perdidas} dias de ${previousYear} caducaron)</span>`;
                                            }

                                            // Mostrar "sin futuras" solo si hay diferencia
                                            const sinFuturasHtml = disponiblesActual !== disponiblesActualSinFuturas
                                                ? `<span class="text-xs text-gray-500">(${disponiblesActualSinFuturas} sin futuras)</span>`
                                                : '';

                                            content.innerHTML = `
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-sm">
                                                    <span class="text-gray-400">
                                                        Año ${clickYear}: ${generadasActual} generadas - ${usadasTotalActual} usadas
                                                    </span>
                                                    <span class="${colorClass} font-bold text-lg">
                                                        ${disponiblesActual} dias disponibles
                                                    </span>
                                                    ${sinFuturasHtml}
                                                    ${perdidasHtml}
                                                </div>
                                            `;
                                        } else {
                                            // === CASO NORMAL: persona incorporada este año o clic en año anterior ===
                                            const diffTime = Math.abs(clickDate - incorpDate);
                                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                                            const generadas = Math.floor((diffDays / 30) * 2.5); // Truncado
                                            const disponibles = generadas - diasVacacionesAsignados;

                                            // === SIN VACACIONES FUTURAS ===
                                            const diasUsadosHastaFecha = data.dias_usados_hasta_fecha || 0;
                                            const disponiblesSinFuturas = generadas - diasUsadosHastaFecha;

                                            const colorClass = disponibles >= 0 ? 'text-green-400' : 'text-red-400';

                                            // Mostrar "sin futuras" solo si hay diferencia
                                            const sinFuturasHtml = disponibles !== disponiblesSinFuturas
                                                ? `<span class="text-xs text-gray-500">(${disponiblesSinFuturas} sin futuras)</span>`
                                                : '';

                                            content.innerHTML = `
                                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-sm">
                                                    <span class="text-gray-400">
                                                        (Generadas: ${generadas} - Usadas: ${diasVacacionesAsignados})
                                                    </span>
                                                    <span class="${colorClass} font-bold text-lg">
                                                        ${disponibles} dias disponibles
                                                    </span>
                                                    ${sinFuturasHtml}
                                                </div>
                                            `;
                                        }
                                    }
                                }
                            } else {
                                // Mostrar mensaje cuando no hay fecha de incorporación
                                if (modal && content) {
                                    modal.classList.remove('translate-y-full');
                                    modal.classList.add('translate-y-0');

                                    content.innerHTML = `
                                        <div class="flex items-center gap-2 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span class="text-yellow-400">
                                                Configure la fecha de incorporacion para ver el calculo de vacaciones
                                            </span>
                                        </div>
                                    `;
                                }
                            }
                        })
                        .catch(e => console.error("Error fetching vacation data:", e));

                    return;
                }

                // --- SEGUNDO CLIC ---
                const startStr = clicked < startClick ? clicked : startClick;
                const endStr = clicked < startClick ? startClick : clicked;

                // Limpiamos todo antes de la acción
                clearTempHighlight(calendar, false);
                const tempStart = startClick;
                startClick = null;

                if (clicked === tempStart) {
                    // Un solo día
                    if (permissions.canAssignStates || permissions.canAssignShifts) {
                        registrarEventoOficina(clicked, clicked, calendar);
                    } else if (permissions.canRequestVacations) {
                        pedirVacaciones(clicked, clicked, calendar);
                    }
                } else {
                    // Rango
                    if (permissions.canAssignStates || permissions.canAssignShifts) {
                        registrarEventoOficina(startStr, endStr, calendar);
                    } else if (permissions.canRequestVacations) {
                        pedirVacaciones(startStr, endStr, calendar);
                    }
                }
            },

            datesSet: function (info) {
                let fechaActual = info.startStr;
                if (calendar.view.type === "dayGridMonth") {
                    const mid = new Date(info.start);
                    mid.setDate(mid.getDate() + 15);
                    fechaActual = mid.toISOString().split("T")[0];
                }
                localStorage.setItem(storageKeyPrefix + "fecha", fechaActual);
                localStorage.setItem(
                    storageKeyPrefix + "vista",
                    calendar.view.type
                );
            },

            // Renderizado personalizado para fichajes
            eventContent: function (arg) {
                const event = arg.event;
                const props = event.extendedProps || {};
                const classNames = event.classNames || [];

                // Solo para eventos de fichajes
                if (classNames.includes('fichaje-evento')) {
                    const entrada1 = props.entrada1 || null;
                    const salida1 = props.salida1 || null;
                    const entrada2 = props.entrada2 || null;
                    const salida2 = props.salida2 || null;
                    const tieneSegundaJornada = props.tieneSegundaJornada || false;

                    let html = `<div class="fichajes-container${tieneSegundaJornada ? ' dos-jornadas' : ''}">`;

                    // Primera jornada
                    if (entrada1 || salida1) {
                        html += `<div class="jornada jornada-1">`;
                        if (tieneSegundaJornada) {
                            html += `<span class="jornada-label">1ª</span>`;
                        }
                        if (entrada1) {
                            html += `<span class="hora-entrada">${entrada1}</span>`;
                        }
                        if (salida1) {
                            html += `<span class="hora-salida">${salida1}</span>`;
                        }
                        html += `</div>`;
                    }

                    // Segunda jornada
                    if (entrada2 || salida2) {
                        html += `<div class="jornada jornada-2">`;
                        html += `<span class="jornada-label">2ª</span>`;
                        if (entrada2) {
                            html += `<span class="hora-entrada">${entrada2}</span>`;
                        }
                        if (salida2) {
                            html += `<span class="hora-salida">${salida2}</span>`;
                        }
                        html += `</div>`;
                    }

                    html += `</div>`;

                    return { html: html };
                }

                // Para otros eventos, usar el renderizado por defecto
                return null;
            },

            // Click en evento para mostrar tooltip con detalles
            eventClick: function (info) {
                const event = info.event;
                const props = event.extendedProps || {};

                // Ignorar eventos de fondo (selección de rango)
                if (event.display === 'background' || props.__tempHover) return;

                // Ignorar festivos y vacaciones pendientes
                if (event.id?.startsWith('festivo-') || event.id?.startsWith('vac-')) return;

                // Eliminar tooltip existente
                const existente = document.getElementById('evento-tooltip');
                if (existente) existente.remove();

                const obraNombre = props.obra_nombre || null;
                const entrada = props.entrada ? props.entrada.substring(0, 5) : null;
                const salida = props.salida ? props.salida.substring(0, 5) : null;

                // Si no hay datos que mostrar, no hacer nada
                if (!obraNombre && !entrada && !salida) return;

                // Crear tooltip
                const tooltip = document.createElement('div');
                tooltip.id = 'evento-tooltip';
                tooltip.style.cssText = `
                    position: fixed;
                    z-index: 9999;
                    background: #1f2937;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    max-width: 250px;
                    pointer-events: none;
                `;

                let html = '';
                if (obraNombre) {
                    html += `<div style="margin-bottom: 4px;"><strong>Obra:</strong> ${obraNombre}</div>`;
                }
                if (entrada || salida) {
                    html += `<div><strong>Horario:</strong> `;
                    if (entrada) html += entrada;
                    if (entrada && salida) html += ' - ';
                    if (salida) html += salida;
                    html += `</div>`;
                }
                tooltip.innerHTML = html;

                document.body.appendChild(tooltip);

                // Posicionar cerca del evento
                const rect = info.el.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();

                let top = rect.bottom + 5;
                let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

                // Ajustar si se sale de pantalla
                if (left < 10) left = 10;
                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                if (top + tooltipRect.height > window.innerHeight - 10) {
                    top = rect.top - tooltipRect.height - 5;
                }

                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';

                // Cerrar al hacer clic en cualquier lugar
                const cerrarTooltip = (e) => {
                    if (!tooltip.contains(e.target)) {
                        tooltip.remove();
                        document.removeEventListener('click', cerrarTooltip);
                    }
                };
                setTimeout(() => document.addEventListener('click', cerrarTooltip), 10);

                // Auto-cerrar después de 3 segundos
                setTimeout(() => {
                    if (document.getElementById('evento-tooltip')) {
                        tooltip.remove();
                        document.removeEventListener('click', cerrarTooltip);
                    }
                }, 3000);
            },
        });

        // Cancelar rango con ESC
        document.addEventListener("keydown", (ev) => {
            if (ev.key === "Escape" && startClick) {
                startClick = null;
                clearTempHighlight(calendar);
                // clearVacationBadges() is already called inside clearTempHighlight
            }
        });

        let rafId = null;
        function bindHoverCells() {
            const cells = el.querySelectorAll(".fc-daygrid-day");
            cells.forEach((cell) => {
                cell.addEventListener("mouseenter", () => {
                    if (!startClick) return;
                    const day = cell.getAttribute("data-date");
                    if (day) updateTempHighlight(calendar, startClick, day, true); // true = keep badges
                });
            });

            // Si el cursor sale de la tabla de días, restauramos el highlight solo del primer día
            const table = el.querySelector('.fc-scrollgrid-sync-table');
            if (table) {
                table.addEventListener('mouseleave', () => {
                    if (startClick) {
                        updateTempHighlight(calendar, startClick, startClick, true);
                    }
                });
            }
        }

        calendar.render();
        bindHoverCells();
        actualizarResumenAsistencia(routes.resumenUrl);

        calendar.on("datesSet", bindHoverCells);

        // Exponer calendario globalmente para poder refrescar desde otros scripts
        window.calendar = calendar;
    }

    // Función para inicializar calendarios que no han sido inicializados
    function initCalendars() {
        const calendarios = qsAll(".fc-calendario");
        if (calendarios.length === 0) return;

        // Esperar a que FullCalendar esté disponible
        waitForFullCalendar(() => {
            calendarios.forEach((el) => {
                // Solo inicializar si no tiene ya un calendario
                if (!el.classList.contains("fc-initialized")) {
                    el.classList.add("fc-initialized");
                    initCalendarOn(el);
                }
            });
        });
    }

    // Inicializar en carga inicial
    if (document.readyState === 'loading') {
        document.addEventListener("DOMContentLoaded", initCalendars);
    } else {
        // DOM ya está listo
        initCalendars();
    }

    // Reinicializar después de navegación Livewire (SPA)
    document.addEventListener("livewire:navigated", initCalendars);

    // Recargar eventos cuando se sube un justificante
    document.addEventListener("livewire:initialized", () => {
        Livewire.on("justificante-guardado", () => {
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
        });
    });
})();
