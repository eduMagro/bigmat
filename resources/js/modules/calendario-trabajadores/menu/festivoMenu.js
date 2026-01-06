import { openMenuAt, closeMenu } from "./baseMenu.js";
import { httpJSON } from "../http.js";
import { R } from "../config.js";

export function openFestivoMenu(x, y, { event, titulo }) {
    const el = openMenuAt(
        x,
        y,
        `
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${titulo}
    </div>
    <button id="ctx-eliminar-festivo" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      🗑️ Eliminar festivo
    </button>
  `
    );

    el.querySelector("#ctx-eliminar-festivo").addEventListener(
        "click",
        async () => {
            closeMenu();
            const ok = await Swal.fire({
                icon: "warning",
                title: "Eliminar festivo",
                html: `<div>¿Seguro que quieres eliminar <b>${titulo}</b>?</div>`,
                showCancelButton: true,
                confirmButtonText: "Eliminar",
                cancelButtonText: "Cancelar",
            }).then((r) => r.isConfirmed);

            if (!ok) return;

            const festivoId = event.extendedProps.festivo_id;
            await httpJSON(R().festivo.delete.replace("__ID__", festivoId), {
                method: "DELETE",
            });
            event.remove();
            Swal.fire({
                icon: "success",
                title: "Festivo eliminado",
                timer: 1200,
                showConfirmButton: false,
            });
        }
    );
}
