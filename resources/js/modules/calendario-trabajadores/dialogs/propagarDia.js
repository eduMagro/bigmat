import { CSRF } from "../config.js";
import { verificarConflictosObraTaller } from "../utils/verificarConflictos.js";

/**
 * Diálogo unificado para propagar asignaciones de un día
 * @param {string} fechaISO - Fecha origen
 * @param {string|null} maquinaId - ID de la máquina seleccionada (puede ser null)
 * @param {string} maquinaNombre - Nombre de la máquina
 * @param {object} calendar - Instancia del calendario
 */
export async function propagarDiaDialog({ fechaISO, maquinaId, maquinaNombre, calendar }) {

    const tieneMaquina = maquinaId && maquinaId !== 'null';

    const { value: formValues } = await Swal.fire({
        title: "Propagar Asignaciones",
        html: `
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha origen:</strong> ${fechaISO}<br>
                        ${tieneMaquina ? `<strong>Máquina:</strong> ${maquinaNombre}` : '<strong>Máquina:</strong> <span class="text-gray-500">Ninguna seleccionada</span>'}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ¿Qué quieres propagar?
                    </label>

                    <div class="space-y-2">
                        ${tieneMaquina ? `
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="tipo" value="maquina" class="mt-1 mr-3" checked>
                            <div>
                                <span class="font-medium text-gray-900">Solo ${maquinaNombre}</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Propaga únicamente las asignaciones de esta máquina al resto de días.
                                </p>
                            </div>
                        </label>
                        ` : ''}

                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="tipo" value="todas" class="mt-1 mr-3" ${!tieneMaquina ? 'checked' : ''}>
                            <div>
                                <span class="font-medium text-gray-900">TODAS las máquinas</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Propaga las asignaciones de todas las máquinas del día ${fechaISO} a los días siguientes.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ¿Hasta cuándo propagar?
                    </label>

                    <div class="space-y-2">
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="alcance" value="semana_actual" class="mt-1 mr-3" checked>
                            <div>
                                <span class="font-medium text-gray-900">Resto de esta semana</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Desde ${fechaISO} hasta el viernes de esta semana.
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="alcance" value="dos_semanas" class="mt-1 mr-3">
                            <div>
                                <span class="font-medium text-gray-900">Esta semana + la siguiente</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Desde ${fechaISO} hasta el viernes de la próxima semana (2 semanas en total).
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <p class="text-xs text-amber-800">
                        <strong>Importante:</strong><br>
                        - Se saltarán automáticamente sábados, domingos, festivos y días con vacaciones.<br>
                        - Si un trabajador ya tiene asignación en un día destino, se <strong>actualizará</strong> con los datos del día origen.
                    </p>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Propagar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#10b981",
        width: "550px",
        preConfirm: () => {
            const tipo = document.querySelector('input[name="tipo"]:checked')?.value;
            const alcance = document.querySelector('input[name="alcance"]:checked')?.value;

            if (!tipo || !alcance) {
                Swal.showValidationMessage("Selecciona todas las opciones");
                return false;
            }

            return { tipo, alcance };
        },
    });

    if (!formValues) return null;

    // Determinar el maquina_id a enviar
    const maquinaIdFinal = formValues.tipo === 'todas' ? null : maquinaId;

    try {
        const response = await fetch("/asignaciones-turno/propagar-dia", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF(),
                Accept: "application/json",
            },
            body: JSON.stringify({
                fecha_origen: fechaISO,
                alcance: formValues.alcance,
                maquina_id: maquinaIdFinal,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        // Eliminar eventos que ya no tienen máquina (modo espejo)
        if (data.eventos_eliminados && data.eventos_eliminados.length > 0) {
            data.eventos_eliminados.forEach((eventoId) => {
                const existente = calendar.getEventById(eventoId);
                if (existente) {
                    existente.remove();
                }
            });
        }

        // Agregar los nuevos eventos al calendario
        if (data.eventos && data.eventos.length > 0) {
            data.eventos.forEach((evento) => {
                // Eliminar evento existente si lo hay para evitar duplicados visuales
                const existente = calendar.getEventById(evento.id);
                if (existente) {
                    existente.remove();
                }

                calendar.addEvent({
                    id: evento.id,
                    title: evento.title,
                    start: evento.start,
                    end: evento.end,
                    resourceId: evento.resourceId,
                    backgroundColor: evento.backgroundColor,
                    borderColor: evento.borderColor,
                    textColor: evento.textColor || "#000000",
                    extendedProps: evento.extendedProps || {},
                });
            });
        }

        const tipoTexto = formValues.tipo === 'todas' ? 'todas las máquinas' : maquinaNombre;
        const alcanceTexto = formValues.alcance === 'semana_actual' ? 'esta semana' : '2 semanas';

        await Swal.fire({
            icon: "success",
            title: "Propagación completada",
            html: `
                <div class="text-left">
                    <p class="mb-2">Se propagaron <b>${data.copiadas || 0}</b> asignaciones a <b>${data.dias_procesados || 0}</b> días.</p>
                    ${data.eliminadas > 0 ? `<p class="mb-2 text-amber-600">Se quitaron <b>${data.eliminadas}</b> trabajadores de máquinas (modo espejo).</p>` : ''}
                    <p class="text-sm text-gray-500">
                        Origen: ${fechaISO}<br>
                        Máquinas: ${tipoTexto}<br>
                        Alcance: ${alcanceTexto}
                    </p>
                </div>
            `,
            timer: 3500,
            showConfirmButton: false,
        });

        return data;

    } catch (error) {
        console.error("Error al propagar día:", error);
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "No se pudieron propagar las asignaciones",
            confirmButtonText: "Aceptar",
        });
        return null;
    }
}
