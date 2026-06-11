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
            initialEvents = null, // eventos precargados del mes actual
        } = cfg;

        let {
            fechaIncorporacion = null,
            diasVacacionesAsignados = 0,
        } = cfg;

        // Flag para controlar si usamos eventos precargados o AJAX
        let usedInitialEvents = false;

        if (typeof fechaIncorporacion === 'undefined') fechaIncorporacion = null; // Safety check

        console.log('Config Calendario:', { userId, permissions, fechaIncorporacion, diasVacacionesAsignados, hasInitialEvents: !!initialEvents });

        // Estado de selección "clic-clic"
        let startClick = null;
        let hoverDayEvs = [];

        // Datos de vacaciones para actualización dinámica
        let vacationData = null;

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
            vacationData = null;
        }

        // Actualiza el modal con solo el botón de cancelar
        function updateVacationModal(diasSeleccionados) {
            const modal = document.getElementById('vacation-bottom-modal');
            const content = document.getElementById('vacation-bottom-content');
            if (!modal || !content) return;

            modal.classList.remove('translate-y-full');
            modal.classList.add('translate-y-0');

            content.innerHTML = `
                <div class="flex items-center gap-3 text-xs sm:text-sm">
                    <span class="text-amber-300">Selecciona dia final</span>
                    <button id="btn-cancelar-seleccion" style="background:#ef4444;color:white;padding:4px 12px;border-radius:6px;font-weight:600;font-size:12px;display:flex;align-items:center;gap:4px;border:none;cursor:pointer;">
                        Cancelar
                    </button>
                </div>
            `;

            // Añadir listener al botón cancelar
            const btnCancelar = document.getElementById('btn-cancelar-seleccion');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function(e) {
                    e.stopPropagation();
                    startClick = null;
                    clearTempHighlight(window.calendar, false);
                });
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

        // Cuenta días naturales (incluye fines de semana y festivos, excluye solo vacaciones ya asignadas)
        function contarDiasLaborables(aStr, bStr, calendar) {
            const days = eachDayStr(aStr, bStr);
            const eventos = calendar.getEvents();

            // Crear set de fechas a excluir (solo vacaciones ya asignadas)
            const fechasExcluidas = new Set();
            eventos.forEach(ev => {
                const estado = ev.extendedProps?.estado || '';
                // Solo excluir vacaciones ya asignadas
                if (estado === 'vacaciones') {
                    const fechaEvento = ev.startStr?.split('T')[0] || ev.start?.toISOString().split('T')[0];
                    if (fechaEvento) fechasExcluidas.add(fechaEvento);
                }
            });

            let count = 0;
            days.forEach(dayStr => {
                // Solo excluir días que ya tienen vacaciones asignadas
                if (fechasExcluidas.has(dayStr)) return;

                count++;
            });

            return count;
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

            // Validar días disponibles antes de enviar
            const diasSeleccionados = contarDiasLaborables(fechaInicio, fechaFin, calendar);

            // Obtener datos de vacaciones frescos para validar
            const baseUrl = routes.vacationDataUrl || `/usuarios/${userId}/vacation-data`;
            const fetchUrl = `${baseUrl}?fecha=${fechaInicio}`;

            let disponiblesTotal = 0;

            try {
                const response = await fetch(fetchUrl);
                if (!response.ok) throw new Error('Error al obtener datos');
                const data = await response.json();

                const fechaInc = data.fecha_incorporacion ? new Date(data.fecha_incorporacion) : null;
                const clickDate = new Date(fechaInicio);
                const clickYear = clickDate.getFullYear();
                const previousYear = clickYear - 1;

                const isGracePeriod = clickDate.getMonth() <= 2; // enero, febrero, marzo
                let disponiblesAnterior = 0;
                let disponiblesActual = 0;

                // Incluir días de solicitudes pendientes
                const diasSolicitadosAnterior = data.dias_solicitados_anterior || 0;
                const diasSolicitadosActual = data.dias_solicitados_actual || 0;
                const diasSolicitadosPeriodoGracia = data.dias_solicitados_periodo_gracia || 0;
                const diasSolicitadosPostGracia = data.dias_solicitados_post_gracia || 0;

                if (fechaInc && fechaInc < new Date(clickYear, 0, 1)) {
                    // Usuario incorporado antes de este año
                    const diasUsadosAnterior = (data.dias_asignados_anterior || 0) + diasSolicitadosAnterior;
                    const diasUsadosPeriodoGracia = (data.dias_usados_periodo_gracia || 0) + diasSolicitadosPeriodoGracia;
                    const diasUsadosPostGracia = (data.dias_usados_post_gracia || 0) + diasSolicitadosPostGracia;

                    const generadasAnterior = 30;
                    const saldoAnterior = Math.max(0, generadasAnterior - diasUsadosAnterior);

                    if (isGracePeriod) {
                        disponiblesAnterior = Math.max(0, saldoAnterior - diasUsadosPeriodoGracia);
                        const excesoSobreAnterior = Math.max(0, diasUsadosPeriodoGracia - saldoAnterior);
                        disponiblesActual = 30 - excesoSobreAnterior - diasUsadosPostGracia;
                        disponiblesTotal = disponiblesAnterior + disponiblesActual;
                    } else {
                        const excesoSobreAnterior = Math.max(0, diasUsadosPeriodoGracia - saldoAnterior);
                        disponiblesTotal = 30 - excesoSobreAnterior - diasUsadosPostGracia;
                    }
                } else {
                    // Usuario incorporado este año - cálculo proporcional PROGRESIVO
                    // Los días se activan proporcionalmente hasta la fecha de la solicitud
                    const diasUsadosEsteAnio = (data.dias_asignados_actual || 0) + diasSolicitadosActual;

                    if (fechaInc) {
                        const inicioAnio = new Date(clickYear, 0, 1);
                        const finDeAnio = new Date(clickYear, 11, 31);
                        const diasTotalesAnio = Math.ceil((finDeAnio - inicioAnio) / (1000 * 60 * 60 * 24)) + 1;

                        // Días desde incorporación hasta la fecha solicitada
                        const diasHastaFechaSolicitada = Math.max(0, Math.ceil((clickDate - fechaInc) / (1000 * 60 * 60 * 24)) + 1);
                        // Días que le corresponderían en todo el año
                        const diasDesdeIncorporacionHastaFinAnio = Math.ceil((finDeAnio - fechaInc) / (1000 * 60 * 60 * 24)) + 1;
                        const generadasTotalesAnio = Math.floor((diasDesdeIncorporacionHastaFinAnio / diasTotalesAnio) * 30);

                        // Días activados hasta la fecha solicitada (proporcional)
                        const proporcionTrabajada = Math.min(1, diasHastaFechaSolicitada / diasDesdeIncorporacionHastaFinAnio);
                        const generadasHastaFecha = Math.floor(generadasTotalesAnio * proporcionTrabajada);

                        disponiblesTotal = generadasHastaFecha - diasUsadosEsteAnio;
                    } else {
                        disponiblesTotal = 30 - diasUsadosEsteAnio;
                    }
                    disponiblesActual = Math.max(0, disponiblesTotal);
                }

            } catch (error) {
                console.error('Error obteniendo datos de vacaciones:', error);
                if (typeof mostrarError === 'function') {
                    mostrarError('No se pudieron verificar los días disponibles. Inténtalo de nuevo.');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron verificar los días disponibles.' });
                }
                return;
            }

            const restantes = disponiblesTotal - diasSeleccionados;

            if (restantes < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Días insuficientes',
                    html: `
                        <p class="text-gray-600 mb-2">No tienes suficientes días de vacaciones disponibles.</p>
                        <p class="text-gray-600">Disponibles: <strong>${disponiblesTotal}</strong></p>
                        <p class="text-gray-600">Solicitados: <strong>${diasSeleccionados}</strong></p>
                        <p class="text-red-600 font-semibold mt-2">Te faltan ${Math.abs(restantes)} día(s)</p>
                    `,
                    confirmButtonColor: '#1e3a5f',
                });
                return;
            }

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

        // Mostrar opciones: Vacaciones o Revision de fichajes
        async function mostrarOpcionesAccion(fechaInicio, fechaFin, calendar) {
            const esMismoDia = fechaInicio === fechaFin;
            const rangoTexto = esMismoDia
                ? fechaInicio
                : `${fechaInicio} - ${fechaFin}`;

            const { value: opcion } = await Swal.fire({
                title: 'Que deseas hacer?',
                html: `
                    <p class="text-gray-600 mb-4">${rangoTexto}</p>
                    <div class="flex flex-col gap-3">
                        <button type="button" class="swal2-option-btn px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors" data-value="vacaciones">
                            Solicitar vacaciones
                        </button>
                        <button type="button" class="swal2-option-btn px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-semibold transition-colors" data-value="revision">
                            Pedir revision de fichajes
                        </button>
                    </div>
                `,
                showCancelButton: true,
                showConfirmButton: false,
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    const buttons = document.querySelectorAll('.swal2-option-btn');
                    buttons.forEach(btn => {
                        btn.addEventListener('click', () => {
                            Swal.close({ value: btn.dataset.value });
                        });
                    });
                },
                preConfirm: () => null,
            });

            if (opcion === 'vacaciones') {
                pedirVacaciones(fechaInicio, fechaFin, calendar);
            } else if (opcion === 'revision') {
                solicitarRevisionFichaje(fechaInicio, fechaFin, calendar);
            }
        }

        // Solicitar revision de fichajes
        async function solicitarRevisionFichaje(fechaInicio, fechaFin, calendar) {
            const esMismoDia = fechaInicio === fechaFin;
            const rangoTexto = esMismoDia
                ? fechaInicio
                : `Del ${fechaInicio} al ${fechaFin}`;

            // Obtener datos de fichajes para mostrar resumen
            const fichajesUrl = `${routes.fichajesRangoUrl}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;

            let fichajesData = null;
            try {
                const response = await fetch(fichajesUrl);
                if (!response.ok) throw new Error('Error al obtener fichajes');
                fichajesData = await response.json();
            } catch (error) {
                console.error('Error obteniendo fichajes:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron obtener los datos de fichajes.',
                    confirmButtonColor: '#1e3a5f',
                });
                return;
            }

            // Construir tabla resumen de fichajes
            let tablaFichajes = '';
            if (fichajesData.fichajes && fichajesData.fichajes.length > 0) {
                tablaFichajes = '<div class="text-left mt-3 max-h-48 overflow-y-auto">';
                tablaFichajes += '<table class="w-full text-xs border-collapse">';
                tablaFichajes += '<thead><tr class="bg-gray-100"><th class="p-1 border">Fecha</th><th class="p-1 border">Turno</th><th class="p-1 border">E</th><th class="p-1 border">S</th><th class="p-1 border">E2</th><th class="p-1 border">S2</th></tr></thead>';
                tablaFichajes += '<tbody>';

                fichajesData.fichajes.forEach(f => {
                    const icono = f.completo ? 'OK' : 'X';
                    const fechaCorta = f.fecha.substring(5); // MM-DD
                    tablaFichajes += `<tr class="${f.completo ? '' : 'bg-red-50'}">`;
                    tablaFichajes += `<td class="p-1 border text-center">${icono} ${fechaCorta}</td>`;
                    tablaFichajes += `<td class="p-1 border text-center">${f.turno || '-'}</td>`;
                    tablaFichajes += `<td class="p-1 border text-center">${f.entrada || '-'}</td>`;
                    tablaFichajes += `<td class="p-1 border text-center">${f.salida || '-'}</td>`;
                    tablaFichajes += `<td class="p-1 border text-center">${f.entrada2 || '-'}</td>`;
                    tablaFichajes += `<td class="p-1 border text-center">${f.salida2 || '-'}</td>`;
                    tablaFichajes += '</tr>';
                });

                tablaFichajes += '</tbody></table></div>';
            } else {
                tablaFichajes = '<p class="text-gray-500 mt-3">No hay fichajes registrados para estas fechas.</p>';
            }

            // Mostrar modal de confirmacion con resumen
            const { isConfirmed, value: observaciones } = await Swal.fire({
                title: 'Solicitar revision de fichajes',
                html: `
                    <p class="text-gray-600 mb-2">${rangoTexto}</p>
                    <p class="text-sm text-gray-500 mb-2">Estado actual de tus fichajes:</p>
                    ${tablaFichajes}
                    <div class="mt-4">
                        <label class="block text-left text-sm font-medium text-gray-700 mb-1">Observaciones (opcional):</label>
                        <textarea id="revision-observaciones" class="w-full p-2 border rounded text-sm" rows="2" placeholder="Indica que fichajes necesitan correccion..."></textarea>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">Se enviara una solicitud al equipo de programacion para revisar estos fichajes.</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Enviar solicitud',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#1e3a5f',
                width: 500,
                preConfirm: () => {
                    return document.getElementById('revision-observaciones')?.value || '';
                }
            });

            if (!isConfirmed) return;

            // Enviar solicitud
            try {
                const response = await fetch(routes.revisionFichajeStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        fecha_inicio: fechaInicio,
                        fecha_fin: fechaFin,
                        observaciones: observaciones,
                    }),
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Error al enviar la solicitud');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud enviada',
                    text: data.success || 'Tu solicitud de revision ha sido enviada.',
                    confirmButtonColor: '#1e3a5f',
                });

            } catch (error) {
                console.error('Error enviando solicitud:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo enviar la solicitud.',
                    confirmButtonColor: '#1e3a5f',
                });
            }
        }

        function openGestionAsignacionModal({
            esMismoDia,
            rangoSeleccionado,
            opcionesTurnos,
            entradaExistente,
            salidaExistente,
            entrada2Existente,
            salida2Existente,
        }) {
            return new Promise((resolve) => {
                const hasSecondShiftByDefault = Boolean(entrada2Existente || salida2Existente);
                const parseTime = (time) => {
                    const match = String(time || "").match(/^(\d{1,2}):(\d{1,2})/);
                    if (!match) return { hour: 0, minute: 0 };
                    return {
                        hour: Math.max(0, Math.min(23, Number(match[1]) || 0)),
                        minute: Math.max(0, Math.min(59, Number(match[2]) || 0)),
                    };
                };
                const sanitizeTime = (time) => {
                    const match = String(time || "").match(/^(\d{1,2}):(\d{1,2})/);
                    if (!match) return "";
                    const h = String(Math.max(0, Math.min(23, Number(match[1]) || 0))).padStart(2, "0");
                    const m = String(Math.max(0, Math.min(59, Number(match[2]) || 0))).padStart(2, "0");
                    return `${h}:${m}`;
                };
                const createTimeField = (id, label, toneClass, value) => {
                    const initial = sanitizeTime(value);
                    const parsed = initial ? parseTime(initial) : null;
                    const hh = parsed ? String(parsed.hour).padStart(2, "0") : "";
                    const mm = parsed ? String(parsed.minute).padStart(2, "0") : "";
                    return `
                        <div data-time-picker="${id}" class="flex flex-col gap-2 rounded-xl border border-slate-300/55 bg-white/70 p-3 dark:border-slate-500/45 dark:bg-slate-900/45">
                            <div class="flex items-center justify-between gap-2">
                                <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold ${toneClass}">${label}</label>
                                <button type="button" data-clear-picker="${id}" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200/80 text-[11px] font-bold leading-none text-slate-500 transition hover:bg-red-500 hover:text-white dark:bg-slate-700/80 dark:text-slate-300 dark:hover:bg-red-500 dark:hover:text-white" aria-label="Borrar ${label}" title="Dejar en blanco">×</button>
                            </div>
                            <input type="hidden" id="${id}" value="${initial}">
                            <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-1.5">
                                <div>
                                    <span class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-300">HH</span>
                                    <input data-time-input="hour" inputmode="numeric" maxlength="2" placeholder="HH" value="${hh}" class="h-12 w-full rounded-lg border border-slate-300/70 bg-white px-2 text-center text-xl font-semibold text-slate-900 outline-none transition focus:border-blue-500/70 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-500/55 dark:bg-slate-800/75 dark:text-slate-100">
                                </div>
                                <span class="pb-2 text-xl font-semibold text-slate-500 dark:text-slate-300">:</span>
                                <div>
                                    <span class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-300">MM</span>
                                    <input data-time-input="minute" inputmode="numeric" maxlength="2" placeholder="MM" value="${mm}" class="h-12 w-full rounded-lg border border-slate-300/70 bg-white px-2 text-center text-xl font-semibold text-slate-900 outline-none transition focus:border-blue-500/70 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-500/55 dark:bg-slate-800/75 dark:text-slate-100">
                                </div>
                            </div>
                        </div>
                    `;
                };

                const overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 z-[99999] flex items-stretch justify-center bg-slate-900/55 p-0 opacity-0 backdrop-blur-sm transition-opacity duration-200 md:items-center md:p-4';
                overlay.innerHTML = `
                    <div data-modal-dialog class="flex h-[100dvh] w-[100dvw] max-w-[100dvw] translate-y-3 flex-col overflow-hidden bg-white backdrop-blur-xl transition-transform duration-200 dark:border-slate-500/40 dark:from-slate-900/90 dark:via-slate-800/85 dark:to-slate-900/90 md:h-auto md:max-h-[94dvh] md:w-[500px] md:max-w-[500px] md:rounded-2xl" role="dialog" aria-modal="true" aria-labelledby="cal-assign-modal-title">
                        <div data-modal-body class="flex min-h-0 flex-1 flex-col overflow-y-auto overflow-x-hidden md:max-h-full">
                            <div class="flex min-h-full flex-col text-slate-900 dark:text-slate-100">
                                <header class="border-b border-slate-300/55 bg-white/85 p-3 text-slate-900 shadow-sm dark:border-slate-500/45 dark:bg-slate-900/70 dark:text-slate-100">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="m-0 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-300">Gestión de jornada</p>
                                        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-700 transition dark:text-slate-100" data-action="close" aria-label="Cerrar modal">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M18 6 6 18"></path>
                                                <path d="m6 6 12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="cal-assign-modal-title" class="flex gap-2 mt-1 text-base font-semibold items-center">
                                        <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M8 2v4"></path>
                                            <path d="M16 2v4"></path>
                                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                            <path d="M3 10h18"></path>
                                            <path d="M8 14h.01"></path>
                                            <path d="M12 14h.01"></path>
                                            <path d="M16 14h.01"></path>
                                            <path d="M8 18h.01"></path>
                                            <path d="M12 18h.01"></path>
                                            <path d="M16 18h.01"></path>
                                        </svg>
                                        ${rangoSeleccionado}
                                    </div>
                                </header>

                                <section data-reveal class="border-b border-slate-300/50 p-3 shadow-sm opacity-0 translate-y-3 scale-[0.985] transition duration-500 dark:border-slate-500/35 dark:bg-slate-800/70">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200" for="sel-turno">
                                                <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                                Turno
                                            </label>
                                            <select id="sel-turno" class="w-full rounded-xl border border-slate-400/40 bg-white/90 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500/70 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-500/35 dark:bg-slate-900/60 dark:text-slate-100">
                                                <option value="">Sin cambios</option>
                                                ${opcionesTurnos}
                                                <option disabled>─────────</option>
                                                <option value="__quitar__">Quitar turno</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200" for="sel-estado">
                                                <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M8 6h13"></path>
                                                    <path d="M8 12h13"></path>
                                                    <path d="M8 18h13"></path>
                                                    <path d="M3 6h.01"></path>
                                                    <path d="M3 12h.01"></path>
                                                    <path d="M3 18h.01"></path>
                                                </svg>
                                                Estado
                                            </label>
                                            <select id="sel-estado" class="w-full rounded-xl border border-slate-400/40 bg-white/90 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500/70 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-500/35 dark:bg-slate-900/60 dark:text-slate-100">
                                                <option value="">Sin cambios</option>
                                                <option value="curso">Cursos</option>
                                                <option value="vacaciones">Vacaciones</option>
                                                <option value="baja">Baja</option>
                                                <option value="justificada">Justificada</option>
                                                <option value="injustificada">Injustificada</option>
                                                <option disabled>─────────</option>
                                                <option value="__quitar__">Quitar estado</option>
                                            </select>
                                        </div>
                                    </div>
                                </section>

                                <section data-reveal class="flex flex-col gap-3 p-3 opacity-0 translate-y-3 scale-[0.985] transition duration-500">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-300">Primera jornada</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        ${createTimeField("hora-entrada", "Entrada", "text-emerald-600 dark:text-emerald-300", entradaExistente)}
                                        ${createTimeField("hora-salida", "Salida", "text-rose-600 dark:text-rose-300", salidaExistente)}
                                    </div>
                                    ${!esMismoDia ? `
                                        <p class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-300">
                                            <svg class="h-3.5 w-3.5 shrink-0 text-blue-700 dark:text-blue-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <path d="M12 16v-4"></path>
                                                <path d="M12 8h.01"></path>
                                            </svg>
                                            Deja vacío para mantener las horas actuales
                                        </p>
                                    ` : ''}
                                </section>

                                <section class="flex flex-col gap-3 border-t border-slate-300/50 p-3 dark:border-slate-500/35">
                                    <div class="flex items-center justify-between">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-300">Segunda jornada</p>
                                        <button type="button" id="btn-toggle-second-shift" class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition ${hasSecondShiftByDefault ? 'border-slate-300/70 bg-white text-slate-700 hover:bg-red-50 hover:border-red-300 hover:text-red-700 dark:border-slate-500/50 dark:bg-slate-900/70 dark:text-slate-100' : 'border-dashed border-blue-400/55 bg-blue-50/85 text-blue-700 hover:bg-blue-100 dark:border-blue-400/45 dark:bg-blue-900/25 dark:text-blue-100'}">
                                            <span id="btn-toggle-second-shift-label">${hasSecondShiftByDefault ? 'Quitar' : '+ Añadir'}</span>
                                        </button>
                                    </div>
                                    <div id="panel-jornada-2" class="${hasSecondShiftByDefault ? '' : 'hidden'} grid grid-cols-2 gap-2">
                                        ${createTimeField("hora-entrada2", "Entrada", "text-cyan-600 dark:text-cyan-300", entrada2Existente)}
                                        ${createTimeField("hora-salida2", "Salida", "text-amber-600 dark:text-amber-300", salida2Existente)}
                                    </div>
                                </section>
                            </div>
                        </div>
                        <div class="mt-auto shrink-0 flex justify-end gap-2 border-t border-slate-300/40 bg-white/40 px-3 py-3 dark:border-slate-500/40 dark:bg-slate-900/35">
                            <button type="button" class="rounded-lg border border-slate-400/45 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white dark:border-slate-500/45 dark:bg-slate-800/70 dark:text-slate-100" data-action="cancel">
                                Cancelar
                            </button>
                            <button type="button" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white" data-action="confirm">
                                Guardar
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);
                const prevOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';

                const dialog = overlay.querySelector('[data-modal-dialog]');
                const body = overlay.querySelector('[data-modal-body]');
                const tipoInput = overlay.querySelector('#sel-turno');
                const panelJornada2 = overlay.querySelector('#panel-jornada-2');
                const btnToggleSecondShift = overlay.querySelector('#btn-toggle-second-shift');
                const btnToggleLabel = overlay.querySelector('#btn-toggle-second-shift-label');
                let secondShiftEnabled = hasSecondShiftByDefault;
                let secondShiftMarkedForRemoval = false;
                const pickers = new Map();
                let revealObserver = null;
                let closed = false;

                const SECOND_ON = ['border-slate-300/70', 'bg-white', 'text-slate-700', 'hover:bg-red-50', 'hover:border-red-300', 'hover:text-red-700', 'dark:border-slate-500/50', 'dark:bg-slate-900/70', 'dark:text-slate-100'];
                const SECOND_OFF = ['border-dashed', 'border-blue-400/55', 'bg-blue-50/85', 'text-blue-700', 'hover:bg-blue-100', 'dark:border-blue-400/45', 'dark:bg-blue-900/25', 'dark:text-blue-100'];

                const bindPicker = (fieldId) => {
                    const picker = overlay.querySelector(`[data-time-picker="${fieldId}"]`);
                    const hidden = overlay.querySelector(`#${fieldId}`);
                    if (!picker || !hidden) return;
                    const inputHour = picker.querySelector('[data-time-input="hour"]');
                    const inputMinute = picker.querySelector('[data-time-input="minute"]');
                    if (!inputHour || !inputMinute) return;
                    const parsed = parseTime(hidden.value);
                    const state = {
                        picker, hidden, inputHour, inputMinute,
                        hour: parsed.hour, minute: parsed.minute,
                        touched: false, cleared: !hidden.value,
                    };
                    pickers.set(fieldId, state);
                    const parseTwoDigit = (value) => String(value || '').replace(/\D/g, '').slice(0, 2);
                    const normalizePart = (value, max) => {
                        const raw = parseTwoDigit(value);
                        if (!raw) return '';
                        return String(Math.max(0, Math.min(max, Number(raw) || 0))).padStart(2, '0');
                    };
                    const syncFromInputs = ({ normalize = false } = {}) => {
                        const rawHour = parseTwoDigit(state.inputHour.value);
                        const rawMinute = parseTwoDigit(state.inputMinute.value);
                        state.inputHour.value = rawHour;
                        state.inputMinute.value = rawMinute;
                        if (!rawHour && !rawMinute) { state.hidden.value = ''; state.cleared = true; return; }
                        const hh = normalize ? normalizePart(rawHour || '0', 23) : rawHour;
                        const mm = normalize ? normalizePart(rawMinute || '0', 59) : rawMinute;
                        const hourValue = normalizePart(hh || '0', 23);
                        const minuteValue = normalizePart(mm || '0', 59);
                        state.hour = Number(hourValue);
                        state.minute = Number(minuteValue);
                        state.hidden.value = `${hourValue}:${minuteValue}`;
                        state.cleared = false;
                        if (normalize) { state.inputHour.value = hourValue; state.inputMinute.value = minuteValue; }
                    };
                    state.clear = () => {
                        state.touched = true; state.cleared = true;
                        state.hidden.value = ''; state.inputHour.value = ''; state.inputMinute.value = '';
                    };
                    state.inputHour.addEventListener('focus', () => state.inputHour.select());
                    state.inputMinute.addEventListener('focus', () => state.inputMinute.select());
                    state.inputHour.addEventListener('input', () => {
                        state.touched = true;
                        const rawHour = parseTwoDigit(state.inputHour.value);
                        if (rawHour.length === 1 && Number(rawHour) > 2) {
                            state.inputHour.value = `0${rawHour}`; syncFromInputs(); state.inputMinute.focus(); return;
                        }
                        syncFromInputs();
                        if (state.inputHour.value.length === 2) state.inputMinute.focus();
                    });
                    state.inputMinute.addEventListener('input', () => { state.touched = true; syncFromInputs(); });
                    state.inputHour.addEventListener('blur', () => syncFromInputs({ normalize: true }));
                    state.inputMinute.addEventListener('blur', () => syncFromInputs({ normalize: true }));
                    state.inputHour.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') { e.preventDefault(); syncFromInputs({ normalize: true }); state.inputMinute.focus(); }
                    });
                    state.inputMinute.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') { e.preventDefault(); syncFromInputs({ normalize: true }); state.inputMinute.blur(); }
                    });
                    syncFromInputs({ normalize: Boolean(hidden.value) });
                };
                const teardown = (result) => {
                    if (closed) return;
                    closed = true;
                    document.removeEventListener('keydown', onKeyDown);
                    revealObserver?.disconnect();
                    revealObserver = null;
                    document.body.style.overflow = prevOverflow;
                    overlay.classList.remove('opacity-100');
                    overlay.classList.add('opacity-0');
                    if (dialog) {
                        dialog.classList.remove('translate-y-0', 'scale-100');
                        dialog.classList.add('translate-y-3', 'scale-[0.985]');
                    }
                    setTimeout(() => { overlay.remove(); resolve(result); }, 180);
                };

                const onKeyDown = (event) => { if (event.key === 'Escape') teardown(null); };

                overlay.addEventListener('click', (event) => { if (event.target === overlay) teardown(null); });
                overlay.querySelector('[data-action="close"]')?.addEventListener('click', () => teardown(null));
                overlay.querySelector('[data-action="cancel"]')?.addEventListener('click', () => teardown(null));

                overlay.querySelector('[data-action="confirm"]')?.addEventListener('click', () => {
                    const getValueOrNull = (id) => {
                        const input = overlay.querySelector(`#${id}`);
                        const state = pickers.get(id);
                        if (!input) return null;
                        if (id.endsWith('2') && !secondShiftEnabled) return null;
                        if (!esMismoDia && state && !state.touched) return null;
                        if (state?.cleared) return null;
                        const val = String(input.value || '').trim();
                        return val || null;
                    };
                    teardown({
                        turno: overlay.querySelector('#sel-turno')?.value || '',
                        estado: overlay.querySelector('#sel-estado')?.value || '',
                        entrada: getValueOrNull('hora-entrada'),
                        salida: getValueOrNull('hora-salida'),
                        entrada2: getValueOrNull('hora-entrada2'),
                        salida2: getValueOrNull('hora-salida2'),
                        removeSecondShift: secondShiftMarkedForRemoval,
                    });
                });

                btnToggleSecondShift?.addEventListener('click', () => {
                    if (secondShiftEnabled) {
                        secondShiftEnabled = false;
                        secondShiftMarkedForRemoval = true;
                        panelJornada2?.classList.add('hidden');
                        pickers.get('hora-entrada2')?.clear?.();
                        pickers.get('hora-salida2')?.clear?.();
                        if (btnToggleLabel) btnToggleLabel.textContent = '+ Añadir';
                        btnToggleSecondShift.classList.remove(...SECOND_ON);
                        btnToggleSecondShift.classList.add(...SECOND_OFF);
                    } else {
                        secondShiftEnabled = true;
                        secondShiftMarkedForRemoval = false;
                        panelJornada2?.classList.remove('hidden');
                        if (btnToggleLabel) btnToggleLabel.textContent = 'Quitar';
                        btnToggleSecondShift.classList.remove(...SECOND_OFF);
                        btnToggleSecondShift.classList.add(...SECOND_ON);
                    }
                });

                ['hora-entrada', 'hora-salida', 'hora-entrada2', 'hora-salida2'].forEach((id) => {
                    overlay.querySelector(`[data-clear-picker="${id}"]`)?.addEventListener('click', () => {
                        pickers.get(id)?.clear?.();
                    });
                });

                const revealItems = overlay.querySelectorAll('[data-reveal]');
                if ('IntersectionObserver' in window && revealItems.length > 0 && body) {
                    revealObserver = new IntersectionObserver(
                        (entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    entry.target.classList.remove('opacity-0', 'translate-y-3', 'scale-[0.985]');
                                    entry.target.classList.add('opacity-100', 'translate-y-0', 'scale-100');
                                    revealObserver?.unobserve(entry.target);
                                }
                            });
                        },
                        { root: body, threshold: 0.2 }
                    );
                    revealItems.forEach((item) => revealObserver.observe(item));
                } else {
                    revealItems.forEach((item) => {
                        item.classList.remove('opacity-0', 'translate-y-3', 'scale-[0.985]');
                        item.classList.add('opacity-100', 'translate-y-0', 'scale-100');
                    });
                }

                document.addEventListener('keydown', onKeyDown);
                ['hora-entrada', 'hora-salida', 'hora-entrada2', 'hora-salida2'].forEach(bindPicker);
                requestAnimationFrame(() => {
                    overlay.classList.remove('opacity-0');
                    overlay.classList.add('opacity-100');
                    if (dialog) {
                        dialog.classList.remove('translate-y-3', 'scale-[0.985]');
                        dialog.classList.add('translate-y-0', 'scale-100');
                    }
                });
                if (dialog) dialog.scrollTop = 0;
                if (tipoInput) tipoInput.focus();
            });
        }

        async function registrarEventoOficina(fechaInicio, fechaFin, calendar) {
            const opcionesTurnos = (turnos || [])
                .map((t) => `<option value="${t.nombre}">${t.nombre}</option>`)
                .join("");

            const esMismoDia = fechaInicio === fechaFin;
            const rangoSeleccionado = esMismoDia
                ? fechaInicio
                : `${fechaInicio} → ${fechaFin}`;

            // Si es un solo día, buscar horas existentes en los eventos del calendario
            let entradaExistente = '';
            let salidaExistente = '';
            let entrada2Existente = '';
            let salida2Existente = '';
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
                        if (props.entrada2 && !entrada2Existente) {
                            entrada2Existente = props.entrada2.substring(0, 5);
                        }
                        if (props.salida2 && !salida2Existente) {
                            salida2Existente = props.salida2.substring(0, 5);
                        }
                    }
                });
            }

            const formData = await openGestionAsignacionModal({
                esMismoDia,
                rangoSeleccionado,
                opcionesTurnos,
                entradaExistente,
                salidaExistente,
                entrada2Existente,
                salida2Existente,
            });
            if (!formData) return;

            const turnoSel = formData.turno || '';
            const estadoSel = formData.estado || '';
            const horaEntrada = formData.entrada;
            const horaSalida = formData.salida;
            const horaEntrada2 = formData.entrada2;
            const horaSalida2 = formData.salida2;
            const removeSecondShift = Boolean(formData.removeSecondShift);

            // Helper: añade las horas (1ª y 2ª jornada) al body
            const aplicarHoras = (body) => {
                if (horaEntrada) body.entrada = horaEntrada;
                if (horaSalida) body.salida = horaSalida;
                if (removeSecondShift) {
                    body.entrada2 = null;
                    body.salida2 = null;
                } else if (esMismoDia) {
                    // Día único: enviar siempre (mantiene valor existente o null si vacío)
                    body.entrada2 = horaEntrada2;
                    body.salida2 = horaSalida2;
                } else {
                    // Rango: solo enviar si tienen valor (no machacar lo existente)
                    if (horaEntrada2) body.entrada2 = horaEntrada2;
                    if (horaSalida2) body.salida2 = horaSalida2;
                }
                return body;
            };

            const postStore = (body) =>
                fetch(routes.storeUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
                    body: JSON.stringify(body),
                }).then((res) => res.json());

            const postDestroy = (body) =>
                fetch(routes.destroyUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
                    body: JSON.stringify(body),
                }).then((res) => res.json());

            const finalizar = (mensaje) => {
                smartRefetch(calendar, () => actualizarResumenAsistencia(routes.resumenUrl));
                Swal.fire("Listo", mensaje, "success");
            };

            // Calcula el objeto vacationData a partir de la respuesta del endpoint.
            // Misma lógica que el flujo de selección de rango del calendario (dateClick);
            // si se cambia una, actualizar también la otra.
            const calcularVacationData = (data, fechaRefStr) => {
                const fechaIncorporacion = data.fecha_incorporacion;
                if (!fechaIncorporacion) return null;

                const incorpDate = new Date(fechaIncorporacion);
                const refDate = new Date(fechaRefStr);
                if (refDate < incorpDate) return null;

                const clickYear = refDate.getFullYear();
                const isGracePeriod = refDate.getMonth() <= 2; // ene-mar
                const previousYear = clickYear - 1;

                // Vacaciones generadas el año anterior (máx 22)
                let generadasAnterior = 0;
                if (incorpDate < new Date(clickYear, 0, 1)) {
                    const endOfPrevYear = new Date(previousYear, 11, 31);
                    const prevYearStart = incorpDate > new Date(previousYear, 0, 1) ? incorpDate : new Date(previousYear, 0, 1);
                    const diffDaysPrev = Math.ceil(Math.max(0, endOfPrevYear - prevYearStart) / (1000 * 60 * 60 * 24)) + 1;
                    generadasAnterior = Math.floor(Math.min((diffDaysPrev / 30) * 2.5, 22));
                }

                const usadasAnteriorDirec = (data.dias_asignados_anterior || 0) + (data.dias_solicitados_anterior || 0);
                const saldoAnteriorAlFinalizar = generadasAnterior - usadasAnteriorDirec;
                const usadasPeriodoGracia = (data.dias_usados_periodo_gracia || 0) + (data.dias_solicitados_periodo_gracia || 0);
                const usadasPostGracia = (data.dias_usados_post_gracia || 0) + (data.dias_solicitados_post_gracia || 0);

                if (isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                    const saldoAnteriorPositivo = Math.max(0, saldoAnteriorAlFinalizar);
                    const disponiblesAnterior = Math.max(0, saldoAnteriorPositivo - usadasPeriodoGracia);
                    const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - saldoAnteriorPositivo);
                    const disponiblesActual = 30 - excesoSobreAnterior - usadasPostGracia;
                    return { disponiblesTotal: disponiblesAnterior + disponiblesActual, disponiblesAnterior, previousYear, clickYear };
                } else if (!isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                    const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - Math.max(0, saldoAnteriorAlFinalizar));
                    const disponiblesActual = 30 - (excesoSobreAnterior + usadasPostGracia);
                    return { disponiblesTotal: disponiblesActual, disponiblesAnterior: 0, previousYear, clickYear };
                }

                // Incorporado este año: cálculo proporcional progresivo
                const diasUsadosEsteAnio = (data.dias_asignados_actual || 0) + (data.dias_solicitados_actual || 0);
                const finDeAnio = new Date(clickYear, 11, 31);
                const diasTotalesAnio = Math.ceil((finDeAnio - new Date(clickYear, 0, 1)) / (1000 * 60 * 60 * 24)) + 1;
                const diasHastaFecha = Math.max(0, Math.ceil((refDate - incorpDate) / (1000 * 60 * 60 * 24)) + 1);
                const diasIncorpHastaFin = Math.ceil((finDeAnio - incorpDate) / (1000 * 60 * 60 * 24)) + 1;
                const generadasTotalesAnio = Math.floor((diasIncorpHastaFin / diasTotalesAnio) * 30);
                const generadasHastaFecha = Math.floor(generadasTotalesAnio * Math.min(1, diasHastaFecha / diasIncorpHastaFin));
                return { disponiblesTotal: Math.max(0, generadasHastaFecha - diasUsadosEsteAnio), disponiblesAnterior: 0, previousYear, clickYear };
            };

            // Carga vacationData bajo demanda (cuando no se pobló por el clic previo en el calendario).
            const obtenerVacationData = async (fechaRefStr) => {
                try {
                    const baseUrl = routes.vacationDataUrl || `/usuarios/${userId}/vacation-data`;
                    const r = await fetch(`${baseUrl}?fecha=${fechaRefStr}`, { headers: { Accept: "application/json" } });
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    const data = await r.json();
                    if (data.error) throw new Error(data.error);
                    return calcularVacationData(data, fechaRefStr);
                } catch (e) {
                    console.error("Error cargando vacation-data on-demand:", e);
                    return null;
                }
            };

            // Vacaciones: lógica de año anterior + validación de días disponibles.
            // Devuelve true para continuar, false para abortar.
            const prepararVacaciones = async (body) => {
                // Cargar datos on-demand si el flujo de selección de rango no los pobló
                // (p.ej. al asignar el estado "vacaciones" desde el modal sin clic previo en el calendario).
                if (!vacationData) {
                    vacationData = await obtenerVacationData(fechaInicio);
                }

                if (vacationData && vacationData.disponiblesAnterior > 0) {
                    const fechaInicioDate = new Date(fechaInicio);
                    const mes = fechaInicioDate.getMonth(); // 0=ene, 1=feb, 2=mar
                    if (mes <= 2) {
                        const anioActual = fechaInicioDate.getFullYear();
                        const anioAnterior = anioActual - 1;
                        const diasSeleccionados = contarDiasLaborables(fechaInicio, fechaFin, calendar);

                        const { isConfirmed: usarAnterior } = await Swal.fire({
                            title: 'Días del año anterior',
                            html: `
                                <p class="text-sm text-gray-600 mb-4">Tiene <strong>${vacationData.disponiblesAnterior} días</strong> del año ${anioAnterior} que caducan el 31 de marzo.</p>
                                <p class="text-sm text-gray-600 mb-4">Estás asignando <strong>${diasSeleccionados} días</strong> de vacaciones.</p>
                                <p class="text-sm text-gray-600 mb-4">¿Quieres usar primero los días del año ${anioAnterior}?</p>
                                ${diasSeleccionados > vacationData.disponiblesAnterior ?
                                    `<p class="text-xs text-blue-600 mt-2"><em>Se asignarán ${Math.min(diasSeleccionados, vacationData.disponiblesAnterior)} días al ${anioAnterior} y ${diasSeleccionados - vacationData.disponiblesAnterior} días al ${anioActual}.</em></p>` :
                                    ''}
                            `,
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: `Sí, usar días de ${anioAnterior}`,
                            denyButtonText: `No, usar solo ${anioActual}`,
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#1e3a5f',
                            denyButtonColor: '#6b7280',
                        });

                        if (usarAnterior === true) {
                            body.usar_anterior_primero = true;
                            body.dias_disponibles_anterior = vacationData.disponiblesAnterior;
                            body.anio_anterior = anioAnterior;
                        } else {
                            body.anio_cargo = anioActual;
                        }
                    }
                }

                // Sin datos (usuario sin fecha de incorporación o fallo de carga):
                // continuar y dejar que el backend valide el tope de vacaciones.
                if (!vacationData) {
                    return true;
                }

                const diasSeleccionados = contarDiasLaborables(fechaInicio, fechaFin, calendar);
                const restantes = vacationData.disponiblesTotal - diasSeleccionados;
                if (restantes < 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Días insuficientes',
                        html: `
                            <p class="text-gray-600 mb-2">No tiene suficientes días de vacaciones disponibles.</p>
                            <p class="text-gray-600">Disponibles: <strong>${vacationData.disponiblesTotal}</strong></p>
                            <p class="text-gray-600">Solicitados: <strong>${diasSeleccionados}</strong></p>
                            <p class="text-red-600 font-semibold mt-2">Faltan ${Math.abs(restantes)} día(s)</p>
                        `,
                        confirmButtonColor: '#1e3a5f',
                    });
                    return false;
                }
                return true;
            };

            try {
                // === 1) QUITAR TURNO (elimina la fila completa: turno, estado y horas) ===
                if (turnoSel === '__quitar__') {
                    const confirmacion = await Swal.fire({
                        title: "Confirmar eliminación",
                        text: "¿Seguro que quieres eliminar el turno? Esto también eliminará el estado y las horas de entrada y salida.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Sí, eliminar",
                        cancelButtonText: "Cancelar",
                    });
                    if (!confirmacion.isConfirmed) return;

                    const data = await postDestroy({ fecha_inicio: fechaInicio, fecha_fin: fechaFin, user_id: userId, tipo: "eliminarTurnoEstado" });
                    if (data.success) finalizar(data.success);
                    else Swal.fire("Error", data.error || "No se pudo eliminar el turno.", "error");
                    return;
                }

                const asignaTurno = turnoSel !== '';
                const asignaEstado = estadoSel !== '' && estadoSel !== '__quitar__';
                const quitaEstado = estadoSel === '__quitar__';

                // === 2) SOLO HORAS (ningún cambio de turno ni estado) ===
                if (!asignaTurno && !asignaEstado && !quitaEstado) {
                    const body = aplicarHoras({ user_id: userId, fecha_inicio: fechaInicio, fecha_fin: fechaFin, tipo: "soloHoras" });
                    if (!horaEntrada && !horaSalida && !('entrada2' in body) && !('salida2' in body)) {
                        Swal.fire("Aviso", "Selecciona un turno/estado o indica al menos una hora.", "warning");
                        return;
                    }
                    const data = await postStore(body);
                    if (data.success) finalizar("Horas actualizadas correctamente.");
                    else if (typeof mostrarError === 'function') mostrarError(data.error || "No se pudieron actualizar las horas.");
                    else Swal.fire("Error", data.error || "No se pudieron actualizar las horas.", "error");
                    return;
                }

                let horasAplicadas = false;

                // === 3) ASIGNAR TURNO (turno_id + estado=activo + horas) ===
                if (asignaTurno) {
                    const body = aplicarHoras({ user_id: userId, fecha_inicio: fechaInicio, fecha_fin: fechaFin, tipo: turnoSel });
                    horasAplicadas = true;
                    const data = await postStore(body);
                    if (!data.success) {
                        Swal.fire("Error", data.error || "No se pudo asignar el turno.", "error");
                        return;
                    }
                }

                // === 4) ASIGNAR ESTADO (mantiene el turno asignado/existente) ===
                if (asignaEstado) {
                    const body = { user_id: userId, fecha_inicio: fechaInicio, fecha_fin: fechaFin, tipo: estadoSel };
                    if (!horasAplicadas) aplicarHoras(body);

                    if (estadoSel === 'vacaciones') {
                        const continuar = await prepararVacaciones(body);
                        if (!continuar) return;
                    }

                    const data = await postStore(body);
                    if (!data.success) {
                        Swal.fire("Error", data.error || "No se pudo asignar el estado.", "error");
                        return;
                    }
                }

                // === 5) QUITAR ESTADO (mantiene turno y horas) ===
                if (quitaEstado) {
                    const data = await postDestroy({ fecha_inicio: fechaInicio, fecha_fin: fechaFin, user_id: userId, tipo: "eliminarEstado" });
                    if (!data.success) {
                        Swal.fire("Error", data.error || "No se pudo eliminar el estado.", "error");
                        return;
                    }
                }

                finalizar("Cambios guardados correctamente.");
            } catch (err) {
                console.error("Error:", err);
                Swal.fire("Error", "Ocurrió un problema al guardar los cambios.", "error");
            }
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
                // Usar eventos precargados en la primera carga (evita AJAX inicial)
                if (initialEvents && !usedInitialEvents) {
                    usedInitialEvents = true;
                    console.log('Usando eventos precargados:', initialEvents.length);

                    const allDayEvents = initialEvents.filter(ev => ev.allDay !== false);
                    const timedEvents = initialEvents.filter(ev => ev.allDay === false);
                    const normalized = normalizeDailyEvents(allDayEvents);
                    const final = [...normalized, ...timedEvents];

                    success(final);
                    return;
                }

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

                            // Botón cancelar reutilizable
                            const cancelarBtnHtml = `
                                <button id="btn-cancelar-seleccion" style="background:#ef4444;color:white;padding:4px 12px;border-radius:6px;font-weight:600;font-size:12px;display:flex;align-items:center;gap:4px;border:none;cursor:pointer;margin-left:auto;">
                                    ✕ Cancelar
                                </button>
                            `;

                            // Función para añadir listener al botón cancelar
                            const addCancelarListener = () => {
                                const btnCancelar = document.getElementById('btn-cancelar-seleccion');
                                if (btnCancelar) {
                                    btnCancelar.addEventListener('click', function(e) {
                                        e.stopPropagation();
                                        startClick = null;
                                        clearTempHighlight(calendar, false);
                                    });
                                }
                            };

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
                                            generadasAnterior = Math.floor(Math.min((diffDaysPrev / 30) * 2.5, 22)); // Truncado, Max 22 días
                                        }

                                        // Días usados del año anterior (en fechas del año anterior) + solicitados
                                        const diasSolicitadosAnterior = data.dias_solicitados_anterior || 0;
                                        const diasSolicitadosPeriodoGracia = data.dias_solicitados_periodo_gracia || 0;
                                        const diasSolicitadosPostGracia = data.dias_solicitados_post_gracia || 0;

                                        const usadasAnteriorDirec = (data.dias_asignados_anterior || 0) + diasSolicitadosAnterior;

                                        // Saldo del año anterior AL FINALIZAR el año anterior
                                        const saldoAnteriorAlFinalizar = generadasAnterior - usadasAnteriorDirec;

                                        // Días usados durante el período de gracia (1 ene - 31 mar del año actual) + solicitados
                                        const usadasPeriodoGracia = (data.dias_usados_periodo_gracia || 0) + diasSolicitadosPeriodoGracia;

                                        // Días usados después del período de gracia (1 abril en adelante) + solicitados
                                        const usadasPostGracia = (data.dias_usados_post_gracia || 0) + diasSolicitadosPostGracia;

                                        if (isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                                            // === PERÍODO DE GRACIA (1 ene - 31 mar) ===
                                            // Las vacaciones del período de gracia se descuentan PRIMERO del año anterior

                                            // Saldo del año anterior (nunca negativo)
                                            const saldoAnteriorPositivo = Math.max(0, saldoAnteriorAlFinalizar);

                                            // Cuántas del año anterior quedan después de descontar las del período de gracia
                                            const disponiblesAnterior = Math.max(0, saldoAnteriorPositivo - usadasPeriodoGracia);

                                            // Si usó más que las del año anterior, el exceso viene del año actual
                                            const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - saldoAnteriorPositivo);

                                            // Si entró antes de este año, tiene los 30 días completos
                                            const generadasActual = 30;

                                            // Disponibles del año actual = generadas - exceso - post gracia ya usadas
                                            const disponiblesActual = generadasActual - excesoSobreAnterior - usadasPostGracia;
                                            const disponiblesTotal = disponiblesAnterior + disponiblesActual;

                                            // === SIN VACACIONES FUTURAS ===
                                            const usadasPeriodoGraciaHastaFecha = data.dias_usados_periodo_gracia_hasta_fecha || 0;
                                            const usadasPostGraciaHastaFecha = data.dias_usados_post_gracia_hasta_fecha || 0;
                                            const disponiblesAnteriorSinFuturas = Math.max(0, saldoAnteriorPositivo - usadasPeriodoGraciaHastaFecha);
                                            const excesoSobreAnteriorSinFuturas = Math.max(0, usadasPeriodoGraciaHastaFecha - saldoAnteriorPositivo);
                                            const disponiblesActualSinFuturas = generadasActual - excesoSobreAnteriorSinFuturas - usadasPostGraciaHastaFecha;
                                            const disponiblesTotalSinFuturas = disponiblesAnteriorSinFuturas + disponiblesActualSinFuturas;

                                            const colorAnterior = disponiblesAnterior >= 0 ? 'text-amber-400' : 'text-red-400';
                                            const colorActual = disponiblesActual >= 0 ? 'text-green-400' : 'text-red-400';
                                            const colorTotal = disponiblesTotal >= 0 ? 'text-emerald-400' : 'text-red-400';

                                            // Mostrar "sin futuras" solo si hay diferencia
                                            const sinFuturasHtml = disponiblesTotal !== disponiblesTotalSinFuturas
                                                ? `<span class="text-xs text-gray-500">(${disponiblesTotalSinFuturas} sin futuras)</span>`
                                                : '';

                                            // Guardar datos para actualización dinámica
                                            vacationData = {
                                                disponiblesTotal,
                                                disponiblesAnterior,
                                                previousYear,
                                                clickYear,
                                                colorBase: 'text-emerald-400'
                                            };

                                            // Mostrar modal inicial (0 días seleccionados)
                                            updateVacationModal(0);
                                        } else if (!isGracePeriod && incorpDate < new Date(clickYear, 0, 1)) {
                                            // === DESPUÉS DEL PERÍODO DE GRACIA (1 abril en adelante) ===
                                            // Las vacaciones del año anterior CADUCAN
                                            // Solo cuentan las del año actual (desde 1 enero hasta fecha clickeada)

                                            // Las vacaciones del período de gracia se consumieron del año anterior primero
                                            const usadasDelAnteriorEnGracia = Math.min(usadasPeriodoGracia, Math.max(0, saldoAnteriorAlFinalizar));
                                            const excesoSobreAnterior = Math.max(0, usadasPeriodoGracia - Math.max(0, saldoAnteriorAlFinalizar));

                                            // Vacaciones perdidas del año anterior (las que no se usaron y caducaron)
                                            const perdidas = Math.max(0, saldoAnteriorAlFinalizar - usadasPeriodoGracia);

                                            // Si entró antes de este año, tiene los 30 días completos
                                            const generadasActual = 30;

                                            // Total usadas del año actual = exceso del periodo gracia + usadas post gracia
                                            const usadasTotalActual = excesoSobreAnterior + usadasPostGracia;
                                            const disponiblesActual = generadasActual - usadasTotalActual;

                                            // === SIN VACACIONES FUTURAS ===
                                            const usadasPostGraciaHastaFecha = data.dias_usados_post_gracia_hasta_fecha || 0;
                                            const usadasTotalActualSinFuturas = excesoSobreAnterior + usadasPostGraciaHastaFecha;
                                            const disponiblesActualSinFuturas = generadasActual - usadasTotalActualSinFuturas;

                                            const colorClass = disponiblesActual >= 0 ? 'text-green-400' : 'text-red-400';

                                            // Guardar datos para actualización dinámica
                                            vacationData = {
                                                disponiblesTotal: disponiblesActual,
                                                disponiblesAnterior: 0,
                                                previousYear,
                                                clickYear,
                                                colorBase: 'text-green-400',
                                                perdidas
                                            };

                                            // Mostrar modal inicial (0 días seleccionados)
                                            updateVacationModal(0);
                                        } else {
                                            // === PERSONA INCORPORADA ESTE AÑO: cálculo proporcional PROGRESIVO ===
                                            // Incluir días solicitados pendientes
                                            const diasSolicitadosActual = data.dias_solicitados_actual || 0;
                                            const diasUsadosEsteAnio = (data.dias_asignados_actual || 0) + diasSolicitadosActual;

                                            const inicioAnio = new Date(clickYear, 0, 1);
                                            const finDeAnio = new Date(clickYear, 11, 31);
                                            const diasTotalesAnio = Math.ceil((finDeAnio - inicioAnio) / (1000 * 60 * 60 * 24)) + 1;

                                            // Días desde incorporación hasta la fecha clickeada
                                            const diasHastaFechaClickeada = Math.max(0, Math.ceil((clickDate - incorpDate) / (1000 * 60 * 60 * 24)) + 1);
                                            // Días que le corresponderían en todo el año
                                            const diasDesdeIncorporacionHastaFinAnio = Math.ceil((finDeAnio - incorpDate) / (1000 * 60 * 60 * 24)) + 1;
                                            const generadasTotalesAnio = Math.floor((diasDesdeIncorporacionHastaFinAnio / diasTotalesAnio) * 30);

                                            // Días activados hasta la fecha clickeada (proporcional)
                                            const proporcionTrabajada = Math.min(1, diasHastaFechaClickeada / diasDesdeIncorporacionHastaFinAnio);
                                            const generadasHastaFecha = Math.floor(generadasTotalesAnio * proporcionTrabajada);

                                            const disponibles = generadasHastaFecha - diasUsadosEsteAnio;

                                            // Guardar datos para actualización dinámica
                                            vacationData = {
                                                disponiblesTotal: Math.max(0, disponibles),
                                                disponiblesAnterior: 0,
                                                previousYear: clickDate.getFullYear() - 1,
                                                clickYear: clickDate.getFullYear(),
                                                colorBase: 'text-green-400',
                                                generadasHastaFecha,
                                                generadasTotalesAnio
                                            };

                                            // Mostrar modal inicial (0 días seleccionados)
                                            updateVacationModal(0);
                                        }
                                    }
                                }
                            } else {
                                // Mostrar mensaje cuando no hay fecha de incorporación
                                if (modal && content) {
                                    modal.classList.remove('translate-y-full');
                                    modal.classList.add('translate-y-0');

                                    content.innerHTML = `
                                        <div class="flex items-center gap-4 text-sm">
                                            <span class="text-yellow-400">
                                                Falta configurar tu fecha de incorporacion
                                            </span>
                                            ${cancelarBtnHtml}
                                        </div>
                                    `;
                                    addCancelarListener();
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
                        mostrarOpcionesAccion(clicked, clicked, calendar);
                    }
                } else {
                    // Rango
                    if (permissions.canAssignStates || permissions.canAssignShifts) {
                        registrarEventoOficina(startStr, endStr, calendar);
                    } else if (permissions.canRequestVacations) {
                        mostrarOpcionesAccion(startStr, endStr, calendar);
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

            // Renderizado personalizado para todos los eventos
            eventContent: function (arg) {
                const event = arg.event;
                const props = event.extendedProps || {};
                const classNames = event.classNames || [];

                // Eventos de fichajes - renderizado especial
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

                // Eventos de fondo (selección) - renderizado por defecto
                if (event.display === 'background') {
                    return null;
                }

                // Renderizado minimalista para todos los eventos (incluyendo solicitudes pendientes)
                return {
                    html: `<div class="evento-simple">${event.title}</div>`
                };
            },

            // Click en evento para mostrar tooltip con detalles
            eventClick: function (info) {
                const event = info.event;
                const props = event.extendedProps || {};

                // Ignorar eventos de fondo (selección de rango)
                if (event.display === 'background' || props.__tempHover) return;

                // Ignorar festivos
                if (event.id?.startsWith('festivo-')) return;

                // --- SOLICITUDES PENDIENTES: mostrar modal de gestión ---
                // Detectar solicitudes pendientes (del backend: es_solicitud_vacaciones + estado pendiente)
                if (props.es_solicitud_vacaciones && props.estado === 'pendiente') {
                    const solicitudId = props.solicitud_id;
                    const fechaInicio = props.fecha_inicio;
                    const fechaFin = props.fecha_fin;
                    const fechaActual = event.startStr?.split('T')[0] || props.fecha;

                    // Calcular todos los días del rango (días naturales)
                    const dias = [];
                    const inicio = new Date(fechaInicio);
                    const fin = new Date(fechaFin);
                    for (let d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 1)) {
                        const fechaStr = d.toISOString().split('T')[0];
                        dias.push(fechaStr);
                    }

                    const esMismoDia = dias.length === 1;

                    // Generar checkboxes para cada día
                    const checkboxesHtml = dias.map(dia => {
                        const esActual = dia === fechaActual;
                        return `
                            <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-100 cursor-pointer ${esActual ? 'bg-amber-50 border border-amber-200' : ''}">
                                <input type="checkbox" name="dias_eliminar" value="${dia}" class="w-4 h-4 text-red-600 rounded">
                                <span class="text-sm ${esActual ? 'font-semibold' : ''}">${dia}</span>
                                ${esActual ? '<span class="text-xs text-amber-600">(seleccionado)</span>' : ''}
                            </label>
                        `;
                    }).join('');

                    Swal.fire({
                        title: 'Solicitud de Vacaciones Pendiente',
                        html: `
                            <div style="text-align: left;">
                                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                                    <p style="margin: 0; font-size: 14px; color: #92400e;">
                                        <strong>Estado:</strong> Pendiente de aprobacion
                                    </p>
                                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #92400e;">
                                        ${esMismoDia ? `Fecha: ${fechaInicio}` : `Del ${fechaInicio} al ${fechaFin}`}
                                    </p>
                                </div>

                                ${!esMismoDia ? `
                                <div style="margin-bottom: 16px;">
                                    <p style="font-size: 13px; color: #4b5563; margin-bottom: 8px;">
                                        Selecciona los dias que quieres eliminar de la solicitud:
                                    </p>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px;">
                                        ${checkboxesHtml}
                                    </div>
                                </div>
                                ` : ''}

                                <p style="font-size: 12px; color: #6b7280; margin-top: 12px;">
                                    ${esMismoDia
                                        ? 'Pulsa "Eliminar solicitud" para cancelar esta peticion.'
                                        : 'Pulsa "Eliminar seleccionados" para quitar los dias marcados, o "Eliminar toda" para cancelar la solicitud completa.'
                                    }
                                </p>
                            </div>
                        `,
                        showCancelButton: true,
                        showDenyButton: !esMismoDia,
                        confirmButtonText: esMismoDia ? 'Eliminar solicitud' : 'Eliminar seleccionados',
                        denyButtonText: 'Eliminar toda',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        denyButtonColor: '#dc2626',
                        width: 450,
                        preConfirm: () => {
                            if (esMismoDia) {
                                return { action: 'eliminar_todo' };
                            }
                            const checkboxes = document.querySelectorAll('input[name="dias_eliminar"]:checked');
                            const diasSeleccionados = Array.from(checkboxes).map(cb => cb.value);
                            if (diasSeleccionados.length === 0) {
                                Swal.showValidationMessage('Selecciona al menos un dia para eliminar');
                                return false;
                            }
                            return { action: 'eliminar_dias', dias: diasSeleccionados };
                        },
                    }).then(async (result) => {
                        if (result.isDenied) {
                            // Eliminar toda la solicitud
                            const confirmacion = await Swal.fire({
                                title: 'Confirmar eliminacion',
                                text: 'Se eliminara toda la solicitud de vacaciones. Esta accion no se puede deshacer.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Si, eliminar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#ef4444',
                            });

                            if (confirmacion.isConfirmed) {
                                fetch(`${routes.eliminarSolicitudUrl}/${solicitudId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Eliminada', data.message, 'success');
                                        smartRefetch(calendar);
                                    } else {
                                        if (typeof mostrarError === 'function') { mostrarError(data.error || 'No se pudo eliminar la solicitud.'); } else { Swal.fire('Error', data.error || 'No se pudo eliminar la solicitud.', 'error'); }
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    if (typeof mostrarError === 'function') { mostrarError('Ocurrió un problema al eliminar la solicitud.'); } else { Swal.fire('Error', 'Ocurrió un problema al eliminar la solicitud.', 'error'); }
                                });
                            }
                        } else if (result.isConfirmed) {
                            const { action, dias: diasEliminar } = result.value;

                            if (action === 'eliminar_todo' || (diasEliminar && diasEliminar.length === dias.length)) {
                                // Eliminar toda la solicitud
                                fetch(`${routes.eliminarSolicitudUrl}/${solicitudId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Eliminada', data.message, 'success');
                                        smartRefetch(calendar);
                                    } else {
                                        if (typeof mostrarError === 'function') { mostrarError(data.error || 'No se pudo eliminar la solicitud.'); } else { Swal.fire('Error', data.error || 'No se pudo eliminar la solicitud.', 'error'); }
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    if (typeof mostrarError === 'function') { mostrarError('Ocurrió un problema al eliminar la solicitud.'); } else { Swal.fire('Error', 'Ocurrió un problema al eliminar la solicitud.', 'error'); }
                                });
                            } else if (diasEliminar && diasEliminar.length > 0) {
                                // Eliminar días específicos
                                fetch(routes.eliminarDiasSolicitudUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                    },
                                    body: JSON.stringify({
                                        solicitud_id: solicitudId,
                                        fechas_eliminar: diasEliminar,
                                    }),
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Modificada', data.message, 'success');
                                        smartRefetch(calendar);
                                    } else {
                                        if (typeof mostrarError === 'function') { mostrarError(data.error || 'No se pudo modificar la solicitud.'); } else { Swal.fire('Error', data.error || 'No se pudo modificar la solicitud.', 'error'); }
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    if (typeof mostrarError === 'function') { mostrarError('Ocurrió un problema al modificar la solicitud.'); } else { Swal.fire('Error', 'Ocurrió un problema al modificar la solicitud.', 'error'); }
                                });
                            }
                        }
                    });

                    return; // Salir, no mostrar tooltip normal
                }

                // Ignorar vacaciones denegadas (solo mostrar, no hacer nada)
                if (event.id?.startsWith('vac-')) return;

                // Eliminar tooltip existente
                const existente = document.getElementById('evento-tooltip');
                if (existente) existente.remove();

                const obraNombre = props.obra_nombre || null;
                const turnoNombre = props.turno_nombre || null;
                const entrada = props.entrada ? props.entrada.substring(0, 5) : null;
                const salida = props.salida ? props.salida.substring(0, 5) : null;

                // Si no hay datos que mostrar, no hacer nada
                if (!obraNombre && !turnoNombre && !entrada && !salida) return;

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
                if (turnoNombre) {
                    html += `<div style="margin-bottom: 4px;"><strong>Turno:</strong> ${turnoNombre}</div>`;
                }
                if (obraNombre) {
                    html += `<div style="margin-bottom: 4px;"><strong>Zona:</strong> ${obraNombre}</div>`;
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
                    if (day) {
                        updateTempHighlight(calendar, startClick, day, true);
                        // Calcular días naturales (excluye solo vacaciones ya asignadas)
                        const diasSeleccionados = contarDiasLaborables(startClick, day, calendar);
                        updateVacationModal(diasSeleccionados);
                    }
                });
            });

            // Si el cursor sale de la tabla de días, restauramos el highlight solo del primer día
            const table = el.querySelector('.fc-scrollgrid-sync-table');
            if (table) {
                table.addEventListener('mouseleave', () => {
                    if (startClick) {
                        updateTempHighlight(calendar, startClick, startClick, true);
                        // Calcular días seleccionados
                        const diasSeleccionados = contarDiasLaborables(startClick, startClick, calendar);
                        updateVacationModal(diasSeleccionados);
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
