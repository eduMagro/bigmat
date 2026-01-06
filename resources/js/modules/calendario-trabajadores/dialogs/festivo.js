import { httpJSON } from "../http.js";
import { R } from "../config.js";

export async function crearFestivo(fechaISO) {
    const res = await Swal.fire({
        title: "Nuevo festivo",
        input: "text",
        inputLabel: "Título del festivo",
        inputValue: "Festivo",
        showCancelButton: true,
        confirmButtonText: "Crear",
        cancelButtonText: "Cancelar",
        inputValidator: (v) => (!v || !v.trim() ? "Pon un título" : undefined),
    });
    if (!res.isConfirmed) return null;

    const data = await httpJSON(R().festivo.store, {
        method: "POST",
        body: { fecha: fechaISO, titulo: res.value.trim() },
    });
    return data.festivo; // {id, titulo, fecha, ...}
}
