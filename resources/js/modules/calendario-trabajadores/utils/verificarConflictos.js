import { CSRF } from "../config.js";

/**
 * Verifica si hay conflictos entre obra externa y taller para un trabajador
 * y muestra un aviso si los hay
 *
 * @param {number} userId - ID del trabajador
 * @param {string} fechaInicio - Fecha inicio en formato YYYY-MM-DD
 * @param {string|null} fechaFin - Fecha fin en formato YYYY-MM-DD (opcional)
 * @param {string} destino - 'taller' si va hacia producción, 'obra' si va hacia obra externa
 * @returns {Promise<boolean>} - true si el usuario confirma continuar, false si cancela
 */
export async function verificarConflictosObraTaller(userId, fechaInicio, fechaFin, destino) {
    try {
        const response = await fetch('/asignaciones-turno/verificar-conflictos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin || fechaInicio,
                destino: destino,
            }),
        });

        if (!response.ok) {
            console.error('Error al verificar conflictos:', response.status);
            return true; // En caso de error, permitir continuar
        }

        const data = await response.json();

        if (!data.tiene_conflictos) {
            return true; // No hay conflictos, continuar
        }

        // Hay conflictos, mostrar aviso
        const esHaciaTaller = destino === 'taller';
        const titulo = esHaciaTaller
            ? '⚠️ Este trabajador tiene días en obra'
            : '⚠️ Este trabajador tiene días en taller';

        const diasConflicto = esHaciaTaller
            ? data.dias_en_obra
            : data.dias_en_taller;

        const tipoConflicto = esHaciaTaller
            ? 'obra externa'
            : 'taller/producción';

        const diasTexto = diasConflicto.length > 0
            ? diasConflicto.join(', ')
            : 'varios días';

        const result = await Swal.fire({
            icon: 'warning',
            title: titulo,
            html: `
                <div class="text-left">
                    <p class="mb-3">Este trabajador ya tiene asignaciones en <strong>${tipoConflicto}</strong> esta semana:</p>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
                        <p class="font-semibold text-amber-800">
                            ${esHaciaTaller ? '🏗️' : '🏭'} ${diasTexto}
                        </p>
                    </div>
                    <p class="text-sm text-gray-600">¿Deseas continuar con la asignación de todos modos?</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280',
        });

        return result.isConfirmed;

    } catch (error) {
        console.error('Error al verificar conflictos:', error);
        return true; // En caso de error, permitir continuar
    }
}

/**
 * Determina si una asignación va hacia taller o hacia obra externa
 * basándose en el ID de la máquina/obra
 *
 * @param {number|null} obraIdDestino - ID de la obra destino
 * @param {Array} obrasPacoReyes - Array con IDs de obras de Paco Reyes
 * @returns {string} - 'taller' o 'obra'
 */
export function determinarDestinoAsignacion(obraIdDestino, obrasPacoReyes = []) {
    if (!obraIdDestino) return 'taller'; // Sin obra = taller
    return obrasPacoReyes.includes(parseInt(obraIdDestino)) ? 'taller' : 'obra';
}
