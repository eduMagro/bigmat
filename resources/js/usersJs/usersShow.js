function actualizarResumenAsistencia() {
    fetch(resumenUrl)
        .then((response) => {
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            return response.json();
        })
        .then((data) => {
            const div = document.getElementById("resumen-asistencia");
            if (div) {
                div.style.opacity = 0.5;
                div.innerHTML = `
                    <p><strong>Vacaciones asignadas: </strong> ${data.diasVacaciones}</p>
                    <p><strong>Faltas injustificadas: </strong> ${data.faltasInjustificadas}</p>
                    <p><strong>Faltas justificadas: </strong> ${data.faltasJustificadas}</p>
                    <p><strong>Días de baja: </strong> ${data.diasBaja}</p>
                `;
                setTimeout(() => (div.style.opacity = 1), 200);
            }
        })
        .catch((error) => {
            console.error("Error al actualizar asistencias:", error);
        });
}

const { userId, resumenUrl, eventosUrl, storeUrl, destroyUrl, csrf } =
    window.usuarioDetalleConfig;
const { turnos } = window.usuarioDetalleConfig;

const optionsHtml = turnos
    .map(
        (turno) => `
        <option value="${turno}">${
            turno.charAt(0).toUpperCase() + turno.slice(1)
        }</option>
    `
    )
    .join("");

function initUsersShowCalendario() {
    var calendarEl = document.getElementById("calendario");
    actualizarResumenAsistencia();

    const vistaGuardada =
        localStorage.getItem("ultimaVistaCalendario") || "dayGridMonth";
    const fechaGuardada = localStorage.getItem("fechaCalendario");

    if (!calendarEl) return;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        initialDate: fechaGuardada ? new Date(fechaGuardada) : undefined,
        locale: "es",
        height: "auto",
        selectable: true,
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "",
        },
        datesSet: function (info) {
            let fechaActual = info.startStr;

            if (calendar.view.type === "dayGridMonth") {
                const middleDate = new Date(info.start);
                middleDate.setDate(middleDate.getDate() + 15);
                fechaActual = middleDate.toISOString().split("T")[0];
            }

            localStorage.setItem("fechaCalendario", fechaActual);
            localStorage.setItem("ultimaVistaCalendario", calendar.view.type);
        },
        events: eventosUrl,
        select: function (info) {
            let fechaInicio = info.startStr;
            let fechaFinRaw = new Date(info.end);
            fechaFinRaw.setDate(fechaFinRaw.getDate() - 1);

            let fechaFin = fechaFinRaw.toISOString().split("T")[0];

            // Seguridad: si la fechaFin es menor que fechaInicio, igualamos
            if (new Date(fechaFin) < new Date(fechaInicio)) {
                fechaFin = fechaInicio;
            }

            let mensajeFecha =
                fechaInicio === fechaFin
                    ? `<p class="mb-2 text-gray-700"><strong>${fechaInicio}</strong></p>`
                    : `
                <p class="mb-1 text-gray-700">Desde: <strong>${fechaInicio}</strong></p>
                <p class="mb-2 text-gray-700">Hasta: <strong>${fechaFin}</strong></p>
            `;

            Swal.fire({
                title: '<span style="font-size:1.2rem;">Selecciona un turno o estado</span>',
                icon: "info",
                html: `
            <div class="text-left">
                ${mensajeFecha}
                <label for="tipo-dia" class="block text-sm font-medium text-gray-600 mb-1">Tipo:</label>
              <select id="tipo-dia" class="swal2-select" style="max-width: 100%; width: auto; min-width: 200px; padding: 0.4rem; border-radius: 0.375rem; border: 1px solid #ccc;">

                    <option value="eliminarTurnoEstado">🗑 Eliminar Turno</option>
                    ${optionsHtml}
                    <option value="eliminarEstado">🗑 Eliminar Estado</option>
                    <option value="vacaciones">🏖 Vacaciones</option>
                    <option value="baja">🤒 Baja</option>
                    <option value="justificada">✅ Falta Justificada</option>
                    <option value="injustificada">❌ Falta Injustificada</option>
                </select>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: "Registrar",
                cancelButtonText: "Cancelar",
                buttonsStyling: false,
                customClass: {
                    confirmButton:
                        "swal2-confirm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg",
                    cancelButton:
                        "swal2-cancel bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg",
                },
                preConfirm: () => {
                    return document.getElementById("tipo-dia").value;
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    let tipoSeleccionado = result.value;

                    if (
                        tipoSeleccionado === "eliminarTurnoEstado" ||
                        tipoSeleccionado === "eliminarEstado"
                    ) {
                        let eventosEnRango = calendar
                            .getEvents()
                            .filter((event) => {
                                let eventDate = event.startStr;
                                return (
                                    eventDate >= fechaInicio &&
                                    eventDate <= fechaFin
                                );
                            });

                        let todosSonFestivo =
                            eventosEnRango.length > 0 &&
                            eventosEnRango.every(
                                (e) => e.title?.toLowerCase() === "festivo"
                            );

                        let body = {
                            fecha_inicio: fechaInicio,
                            fecha_fin: fechaFin,
                        };

                        if (todosSonFestivo) {
                            body.tipo_turno = "festivo";
                        } else {
                            body.user_id = userId;
                            body.tipo = tipoSeleccionado;
                        }

                        fetch(destroyUrl, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrf,
                            },
                            body: JSON.stringify(body),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    eventosEnRango.forEach((event) =>
                                        event.remove()
                                    );
                                    calendar.refetchEvents();
                                    setTimeout(
                                        actualizarResumenAsistencia,
                                        200
                                    );
                                } else {
                                    Swal.fire({
                                        title: "Error",
                                        text: data.error,
                                        icon: "error",
                                    });
                                }
                            })
                            .catch((error) => {
                                console.error("Error:", error);
                                Swal.fire({
                                    title: "Error",
                                    text: "Ocurrió un problema al eliminar los turnos.",
                                    icon: "error",
        });
}

// Inicialización compatible con Livewire Navigate
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initUsersShowCalendario);
} else {
    initUsersShowCalendario();
}
document.addEventListener("livewire:navigated", initUsersShowCalendario);
                    } else {
                        fetch(storeUrl, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": csrf,
                            },
                            body: JSON.stringify({
                                user_id: userId,
                                fecha_inicio: fechaInicio,
                                fecha_fin: fechaFin,
                                tipo: tipoSeleccionado,
                            }),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.success) {
                                    calendar.refetchEvents();
                                    setTimeout(
                                        actualizarResumenAsistencia,
                                        200
                                    );
                                } else {
                                    Swal.fire({
                                        title: "Error",
                                        text: data.error,
                                        icon: "error",
                                    });
                                }
                            })
                            .catch((error) => {
                                console.error("Error:", error);
                                Swal.fire({
                                    title: "Error",
                                    text: "Ocurrió un problema al registrar los turnos.",
                                    icon: "error",
                                });
                            });
                    }
                }
            });
        },
    });

    calendar.render();
});
