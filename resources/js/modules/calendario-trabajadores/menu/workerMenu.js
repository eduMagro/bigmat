import { openMenuAt, closeMenu } from "./baseMenu.js";
import { editarFichaje } from "../dialogs/fichaje.js";
import { httpJSON } from "../http.js";

export function openWorkerMenu(x, y, event) {
    const nombre = event.title || "Operario";
    const cargo = event.extendedProps?.categoria_nombre ?? "";
    const esp = event.extendedProps?.especialidad_nombre ?? "Sin especialidad";

    const el = openMenuAt(
        x,
        y,
        `
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${nombre} <div style="font-size:12px">${cargo} · ${esp}</div>
    </div>
    <button id="ctx-editar-fichajes" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      ✏️ Editar fichajes
    </button>
    <button id="ctx-eliminar-registro" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;color:#b91c1c;">
      🗑️ Eliminar registro
    </button>
  `
    );

    // Botón para editar fichajes
    el.querySelector("#ctx-editar-fichajes").addEventListener(
        "click",
        async () => {
            closeMenu();
            await editarFichaje(event);
        }
    );

    // Botón para eliminar registro
    el.querySelector("#ctx-eliminar-registro").addEventListener(
        "click",
        async () => {
            closeMenu();

            const ok = await Swal.fire({
                icon: "warning",
                title: "Eliminar registro",
                html: `<div>¿Seguro que quieres eliminar este evento/asignación?</div>
                   <div class="text-xs text-gray-500 mt-1">Esta acción no se puede deshacer.</div>`,
                confirmButtonText: "Eliminar",
                cancelButtonText: "Cancelar",
                showCancelButton: true,
                confirmButtonColor: "#b91c1c",
            }).then((r) => r.isConfirmed);

            if (!ok) return;

            // URL directa sin depender de R()
            const url = "/asignaciones-turnos/destroy";

            const payload = {
                _method: "DELETE",
                fecha_inicio: event.startStr,
                fecha_fin: event.endStr ?? event.startStr,
                tipo: "eliminarTurnoEstado",
                user_id: event.extendedProps?.user_id,
            };

            console.log("[workerMenu] Eliminando turno, payload:", payload);
            console.log("[workerMenu] Event extendedProps:", event.extendedProps);

            try {
                await httpJSON(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify(payload),
                });

                event.remove(); // eliminar visualmente del calendario

                Swal.fire({
                    icon: "success",
                    title: "Registro eliminado",
                    timer: 1300,
                    showConfirmButton: false,
                });
            } catch (error) {
                console.error("Error al eliminar el turno:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error al eliminar",
                    text: error.message || "No se pudo eliminar el turno.",
                });
            }
        }
    );
}
