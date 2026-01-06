import { openActionsMenu } from "../menu/baseMenu.js";
import { crearFestivo } from "../dialogs/festivo.js";
import { generarTurnosDialog } from "../dialogs/generarTurnos.js";
import { propagarDiaDialog } from "../dialogs/propagarDia.js";

export function openCellMenu(x, y, { fechaISO, resourceId, horaISO }, calendar, maquinas) {
    // Obtener el nombre de la máquina si tenemos resourceId
    const maquinaNombre = resourceId
        ? maquinas.find((m) => String(m.id) === String(resourceId))?.title || `Máquina ${resourceId}`
        : "Seleccione una máquina";

    const items = [
            {
                icon: "📅",
                label: "Crear festivo este día",
                onClick: async () => {
                    const festivo = await crearFestivo(fechaISO);
                    if (!festivo) return;

                    const start = new Date(festivo.fecha + "T00:00:00");
                    const end = new Date(start);
                    end.setDate(end.getDate() + 1);
                    const resourceIds = (maquinas || []).map((m) => m.id);

                    calendar.addEvent({
                        id: "festivo-" + festivo.id,
                        title: festivo.titulo,
                        start: start.toISOString(),
                        end: end.toISOString(),
                        allDay: true,
                        resourceIds,
                        backgroundColor: "#ff0000",
                        borderColor: "#b91c1c",
                        textColor: "#ffffff",
                        editable: true,
                        classNames: ["evento-festivo"],
                        extendedProps: {
                            es_festivo: true,
                            festivo_id: festivo.id,
                            entrada: null,
                            salida: null,
                        },
                    });

                    Swal.fire({
                        icon: "success",
                        title: "Festivo creado",
                        timer: 1200,
                        showConfirmButton: false,
                    });
                },
            },
            { type: 'separator' },
            {
                icon: "📤",
                label: `Propagar asignaciones de ${fechaISO}...`,
                onClick: () =>
                    propagarDiaDialog({
                        fechaISO,
                        maquinaId: resourceId || null,
                        maquinaNombre,
                        calendar,
                    }),
            },
            { type: 'separator' },
            {
                icon: "🔧",
                label: resourceId
                    ? `Generar turnos para ${maquinaNombre}`
                    : "Generar turnos (seleccione una máquina)",
                disabled: !resourceId,
                onClick: async () => {
                    console.log("[menu] Click en generar turnos, resourceId:", resourceId);

                    if (!resourceId) {
                        console.log("[menu] No hay resourceId, mostrando advertencia");
                        Swal.fire({
                            icon: "warning",
                            title: "Máquina no seleccionada",
                            text: "Haz clic derecho sobre una máquina específica para generar turnos.",
                        });
                        return;
                    }

                    console.log("[menu] Llamando a generarTurnosDialog...");

                    const resultado = await generarTurnosDialog(
                        fechaISO,
                        resourceId,
                        maquinaNombre,
                        horaISO
                    );

                    console.log("[menu] Resultado del diálogo:", resultado);

                    if (resultado && resultado.eventos) {
                        console.log("[menu] Procesando eventos:", resultado.eventos.length);

                        // Primero, eliminar eventos antiguos del trabajador en las fechas afectadas
                        // Nota: user_id viene dentro de extendedProps (estructura normalizada)
                        const userId = resultado.eventos[0]?.extendedProps?.user_id;
                        if (userId) {
                            const eventosExistentes = calendar.getEvents();
                            const fechasNuevas = resultado.eventos.map(e => e.start?.slice(0, 10));

                            eventosExistentes.forEach(evento => {
                                const eventoUserId = evento.extendedProps?.user_id;
                                const eventoFecha = evento.startStr?.slice(0, 10) || evento.start?.toISOString().slice(0, 10);

                                // Eliminar si es del mismo usuario y está en las fechas que estamos actualizando
                                if (eventoUserId === userId && fechasNuevas.includes(eventoFecha) && !evento.extendedProps?.es_festivo) {
                                    console.log("[menu] Eliminando evento antiguo:", evento.id);
                                    evento.remove();
                                }
                            });
                        }

                        // Agregar los nuevos eventos (estructura normalizada desde el backend)
                        resultado.eventos.forEach(evento => {
                            console.log("[menu] Añadiendo evento:", {
                                id: evento.id,
                                start: evento.start,
                                end: evento.end,
                                resourceId: evento.resourceId
                            });
                            calendar.addEvent({
                                id: evento.id,
                                title: evento.title,
                                start: evento.start,
                                end: evento.end,
                                resourceId: evento.resourceId,
                                backgroundColor: evento.backgroundColor,
                                borderColor: evento.borderColor,
                                textColor: evento.textColor || '#000000',
                                extendedProps: evento.extendedProps || {},
                            });
                        });

                        console.log("[menu] Eventos actualizados correctamente");
                    }
                },
            },
        ];

    openActionsMenu(x, y, {
        headerHtml: `<div>Acciones para <b>${fechaISO}</b></div>`,
        items,
    });
}
