import { R, DATA } from "../config.js";
import { verificarConflictosObraTaller } from "../utils/verificarConflictos.js";

/**
 * Detecta el turno según la hora usando la configuración del backend
 * @param {string} hora - Hora en formato HH:MM
 * @returns {string} - 'mañana', 'tarde' o 'noche'
 */
function detectarTurnoPorHora(hora) {
    if (!hora) return null;
    const [h] = hora.split(':').map(Number);

    // Obtener configuración de turnos del backend
    // turnosConfig tiene la estructura completa, turnos es el array simple
    const turnosConfig = window.AppPlanif?.turnosConfig?.turnos || window.AppPlanif?.turnos || [];

    for (const turno of turnosConfig) {
        if (!turno.hora_inicio || !turno.hora_fin) continue;

        const [hIni] = turno.hora_inicio.split(':').map(Number);
        const [hFin] = turno.hora_fin.split(':').map(Number);
        const esNocturno = hFin < hIni;

        let enTurno = false;
        if (esNocturno) {
            // Turno nocturno: 22:00 - 06:00
            enTurno = h >= hIni || h < hFin;
        } else {
            // Turnos normales
            enTurno = h >= hIni && h < hFin;
        }

        if (enTurno) {
            return turno.nombre;
        }
    }

    // Fallback: usar slots visuales del calendario
    // Noche: 00:00-08:00, Mañana: 08:00-16:00, Tarde: 16:00-24:00
    if (h >= 0 && h < 8) return 'noche';
    if (h >= 8 && h < 16) return 'mañana';
    return 'tarde';
}

/**
 * Colores para diferenciar visualmente las empresas
 */
const COLORES_EMPRESA = [
    { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', badge: 'bg-emerald-100 text-emerald-800' },
    { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700', badge: 'bg-blue-100 text-blue-800' },
    { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', badge: 'bg-amber-100 text-amber-800' },
    { bg: 'bg-purple-50', border: 'border-purple-200', text: 'text-purple-700', badge: 'bg-purple-100 text-purple-800' },
    { bg: 'bg-rose-50', border: 'border-rose-200', text: 'text-rose-700', badge: 'bg-rose-100 text-rose-800' },
    { bg: 'bg-cyan-50', border: 'border-cyan-200', text: 'text-cyan-700', badge: 'bg-cyan-100 text-cyan-800' },
];

/**
 * Formatea los días de obra para mostrar en tooltip
 */
function formatearDiasObra(diasLista) {
    if (!diasLista || diasLista.length === 0) return '';

    return diasLista.map(fecha => {
        const partes = fecha.split('-');
        if (partes.length === 3) {
            const d = new Date(partes[0], partes[1] - 1, partes[2]);
            return d.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
        }
        return fecha;
    }).join(', ');
}

/**
 * Genera el HTML de un select de operarios para una empresa
 */
function generarSelectEmpresa(grupo, index, tipo, colorIndex) {
    const color = COLORES_EMPRESA[colorIndex % COLORES_EMPRESA.length];
    const selectId = `swal-trabajador-${tipo}-${index}`;
    const count = grupo.operarios.length;

    if (count === 0) return '';

    const opciones = grupo.operarios.map(op => {
        const diasObra = op.dias_en_obra || 0;
        const diasObraLista = op.dias_en_obra_lista || [];
        const diasFormateados = formatearDiasObra(diasObraLista);
        const indicadorObra = diasObra > 0 ? ` 🏗️${diasObra}` : '';
        const tooltipObra = diasObra > 0 ? ` (En obra: ${diasFormateados})` : '';

        return `<option value="${op.id}" title="${op.name} ${op.primer_apellido || ''} ${op.segundo_apellido || ''}${tooltipObra}">${op.name} ${op.primer_apellido || ''} ${op.segundo_apellido || ''}${indicadorObra}</option>`;
    }).join('');

    return `
        <div class="mb-3 ${color.bg} ${color.border} border rounded-lg p-2">
            <label class="block text-xs font-semibold ${color.text} mb-1">
                ${grupo.empresa_nombre}
                <span class="${color.badge} text-xs px-1.5 py-0.5 rounded-full ml-1">${count}</span>
            </label>
            <select id="${selectId}" data-tipo="${tipo}" class="swal2-input w-full select-operario text-sm py-1" style="margin: 0;">
                <option value="" selected>Seleccionar...</option>
                ${opciones}
            </select>
        </div>
    `;
}

/**
 * Genera los selectores agrupados por empresa para un tipo (sin_turno, de_maquina, todos)
 */
function generarSeccionEmpresa(gruposPorEmpresa, tipo, titulo, iconColor) {
    if (!gruposPorEmpresa || gruposPorEmpresa.length === 0) {
        return `
            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-500 mb-2">${titulo}</p>
                <p class="text-xs text-gray-400 italic">No hay operarios</p>
            </div>
        `;
    }

    const totalOperarios = gruposPorEmpresa.reduce((sum, g) => sum + g.operarios.length, 0);
    const selects = gruposPorEmpresa.map((grupo, i) =>
        generarSelectEmpresa(grupo, i, tipo, i)
    ).join('');

    return `
        <div class="mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-2">
                ${titulo} <span class="${iconColor}">(${totalOperarios})</span>
            </p>
            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                ${selects}
            </div>
        </div>
    `;
}

/**
 * Abre el diálogo para generar turnos desde el calendario
 * @param {string} fechaISO - Fecha ISO seleccionada
 * @param {number} maquinaId - ID de la máquina/recurso desde el calendario
 * @param {string} maquinaNombre - Nombre de la máquina
 * @param {string} horaISO - Hora del clic (opcional) en formato HH:MM
 * @returns {Promise<object|null>} - Retorna el resultado o null si se canceló
 */
export async function generarTurnosDialog(fechaISO, maquinaId, maquinaNombre, horaISO = null) {
    // Detectar turno automáticamente según la hora del clic
    const turnoDetectado = detectarTurnoPorHora(horaISO);
    console.log("[generarTurnos] horaISO:", horaISO, "turnoDetectado:", turnoDetectado);

    // Datos que vendrán del backend
    let datosOperarios = {
        sin_turno_por_empresa: [],
        de_maquina_por_empresa: [],
        todos_por_empresa: [],
        todos: [] // Para compatibilidad
    };

    try {
        const response = await fetch(`/api/usuarios/operarios-agrupados?fecha=${fechaISO}&maquina_id=${maquinaId}`, {
            headers: {
                'X-CSRF-TOKEN': window.AppPlanif.csrf,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            datosOperarios = await response.json();
            console.log("[generarTurnos] Datos recibidos:", datosOperarios);
        } else {
            console.error('Error al obtener operarios:', response.status);
        }
    } catch (error) {
        console.error('Error al obtener operarios:', error);
    }

    // Texto del turno detectado para mostrar
    const turnoTexto = turnoDetectado
        ? `<span class="text-green-600 font-semibold">${turnoDetectado.charAt(0).toUpperCase() + turnoDetectado.slice(1)}</span>`
        : '<span class="text-gray-400">No detectado</span>';

    // Generar HTML dinámico para los selects por empresa
    const htmlSinTurno = generarSeccionEmpresa(
        datosOperarios.sin_turno_por_empresa,
        'sin_turno',
        'Sin turno asignado este día',
        'text-green-600'
    );

    const htmlDeMaquina = generarSeccionEmpresa(
        datosOperarios.de_maquina_por_empresa,
        'de_maquina',
        `Asignados a ${maquinaNombre}`,
        'text-blue-600'
    );

    const htmlTodos = generarSeccionEmpresa(
        datosOperarios.todos_por_empresa,
        'todos',
        'Todos los operarios',
        'text-gray-600'
    );

    const { value: formValues } = await Swal.fire({
        title: "Generar Turnos",
        html: `
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha inicio:</strong> ${fechaISO}<br>
                        <strong>Máquina:</strong> ${maquinaNombre}<br>
                        <strong>Turno detectado:</strong> ${turnoTexto}
                    </p>
                </div>

                <!-- Tabs para las secciones -->
                <div class="border-b border-gray-200 mb-3">
                    <nav class="flex space-x-1" aria-label="Tabs">
                        <button type="button" data-tab="sin_turno" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-green-500 text-green-600 bg-green-50">
                            Sin turno hoy
                        </button>
                        <button type="button" data-tab="de_maquina" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            De ${maquinaNombre}
                        </button>
                        <button type="button" data-tab="todos" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            Todos
                        </button>
                    </nav>
                </div>

                <!-- Contenido de tabs -->
                <div id="tab-content-sin_turno" class="tab-content">
                    ${htmlSinTurno}
                </div>
                <div id="tab-content-de_maquina" class="tab-content hidden">
                    ${htmlDeMaquina}
                </div>
                <div id="tab-content-todos" class="tab-content hidden">
                    ${htmlTodos}
                </div>

                <input type="hidden" id="swal-trabajador" value="" />
                <input type="hidden" id="swal-turno-detectado" value="${turnoDetectado || ''}" />

                <!-- Trabajador seleccionado -->
                <div id="trabajador-seleccionado" class="hidden bg-green-50 border border-green-200 rounded-lg p-2 mb-3">
                    <p class="text-sm text-green-800">
                        <strong>Seleccionado:</strong> <span id="nombre-seleccionado"></span>
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Alcance de generación <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-alcance" class="swal2-input w-full">
                        <option value="un_dia">Solo este día (${fechaISO})</option>
                        <option value="dos_semanas">Hasta el viernes de la semana siguiente</option>
                        <option value="resto_año">Desde ${fechaISO} hasta fin de año</option>
                    </select>
                </div>

                <div class="mb-4" id="turno-inicio-container" style="display: ${turnoDetectado ? 'none' : 'block'};">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Turno inicial (para diurnos) <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-turno-inicio" class="swal2-input w-full">
                        <option value="mañana" ${turnoDetectado === 'mañana' ? 'selected' : ''}>Mañana</option>
                        <option value="tarde" ${turnoDetectado === 'tarde' ? 'selected' : ''}>Tarde</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Los turnos alternarán cada viernes</p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>Nota:</strong> Se saltarán automáticamente sábados, domingos, festivos y días con vacaciones.
                    </p>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Generar Turnos",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#3b82f6",
        cancelButtonColor: "#6b7280",
        width: "650px",
        didOpen: () => {
            const trabajadorInput = document.getElementById("swal-trabajador");
            const turnoContainer = document.getElementById("turno-inicio-container");
            const trabajadorSeleccionadoDiv = document.getElementById("trabajador-seleccionado");
            const nombreSeleccionado = document.getElementById("nombre-seleccionado");

            // Combinar todos los operarios para búsqueda
            const todosParaBusqueda = datosOperarios.todos || [];

            // Función para actualizar el trabajador seleccionado
            function actualizarTrabajador(trabajadorId, selectActivo) {
                trabajadorInput.value = trabajadorId;

                // Limpiar TODOS los otros selects
                document.querySelectorAll('.select-operario').forEach(select => {
                    if (select !== selectActivo) {
                        select.value = "";
                    }
                });

                // Buscar trabajador para mostrar nombre y verificar si es diurno
                const trabajador = todosParaBusqueda.find(t => t.id === parseInt(trabajadorId));
                if (trabajador) {
                    // Mostrar nombre seleccionado con indicador de días en obra
                    const diasObra = trabajador.dias_en_obra || 0;
                    const diasObraLista = trabajador.dias_en_obra_lista || [];
                    const diasFormateados = formatearDiasObra(diasObraLista);
                    const indicadorObra = diasObra > 0 ? ` 🏗️ ${diasObra} día(s) en obra: ${diasFormateados}` : '';

                    nombreSeleccionado.innerHTML = `${trabajador.name} ${trabajador.primer_apellido || ''} ${trabajador.segundo_apellido || ''} (${trabajador.empresa_nombre || 'Sin empresa'})${indicadorObra ? `<br><span class="text-orange-600 text-xs">${indicadorObra}</span>` : ''}`;
                    trabajadorSeleccionadoDiv.classList.remove('hidden');

                    // Mostrar/ocultar selector de turno según tipo
                    if (trabajador.turno === "diurno") {
                        turnoContainer.style.display = "block";
                    } else {
                        turnoContainer.style.display = "none";
                    }
                }
            }

            // Event listeners para todos los selects de operarios
            document.querySelectorAll('.select-operario').forEach(select => {
                select.addEventListener("change", (e) => {
                    if (e.target.value) {
                        actualizarTrabajador(e.target.value, e.target);
                    }
                });
            });

            // Sistema de tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const tabId = e.target.dataset.tab;

                    // Actualizar botones
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.classList.remove('border-green-500', 'text-green-600', 'bg-green-50');
                        b.classList.add('border-transparent', 'text-gray-500');
                    });
                    e.target.classList.remove('border-transparent', 'text-gray-500');
                    e.target.classList.add('border-green-500', 'text-green-600', 'bg-green-50');

                    // Actualizar contenido
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(`tab-content-${tabId}`).classList.remove('hidden');
                });
            });
        },
        preConfirm: () => {
            const trabajadorId = document.getElementById("swal-trabajador").value;
            const alcance = document.getElementById("swal-alcance").value;
            const turnoInicio = document.getElementById("swal-turno-inicio").value;
            const turnoDetectadoValue = document.getElementById("swal-turno-detectado").value;

            if (!trabajadorId) {
                Swal.showValidationMessage("Debes seleccionar un trabajador");
                return false;
            }

            return {
                trabajador_id: trabajadorId,
                alcance: alcance,
                turno_inicio: turnoInicio,
                turno_detectado: turnoDetectadoValue || null,
            };
        },
    });

    if (!formValues) return null;

    // Calcular fecha fin según el alcance
    let fechaFin = fechaISO;
    if (formValues.alcance === 'dos_semanas') {
        const fechaInicio = new Date(fechaISO);
        // Ir al viernes de la semana siguiente
        const diasHastaViernes = (5 - fechaInicio.getDay() + 7) % 7 + 7;
        fechaInicio.setDate(fechaInicio.getDate() + diasHastaViernes);
        fechaFin = fechaInicio.toISOString().slice(0, 10);
    } else if (formValues.alcance === 'resto_año') {
        fechaFin = `${new Date(fechaISO).getFullYear()}-12-31`;
    }

    // Verificar conflictos obra/taller antes de continuar
    // En produccion/trabajadores siempre vamos hacia "taller"
    const continuar = await verificarConflictosObraTaller(
        formValues.trabajador_id,
        fechaISO,
        fechaFin,
        'taller'
    );

    if (!continuar) {
        return null; // Usuario canceló
    }

    // Llamar al backend para generar turnos
    try {
        const url = `/profile/generar-turnos-calendario`;
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.AppPlanif.csrf,
                Accept: "application/json",
            },
            body: JSON.stringify({
                user_id: formValues.trabajador_id,
                maquina_id: maquinaId,
                fecha_inicio: fechaISO,
                alcance: formValues.alcance,
                turno_inicio: formValues.turno_inicio,
                turno_detectado: formValues.turno_detectado,
            }),
        });

        const data = await response.json();
        console.log("[generarTurnos] Respuesta backend:", data);
        if (data.eventos && data.eventos.length > 0) {
            console.log("[generarTurnos] Primer evento:", data.eventos[0]);
        }

        if (!response.ok) {
            throw new Error(data.message || `Error HTTP ${response.status}`);
        }

        // Retornar los datos incluyendo los eventos para agregarlos al calendario
        return {
            ...data,
            eventos: data.eventos || []
        };
    } catch (error) {
        console.error("Error al generar turnos:", error);
        await Swal.fire({
            icon: "error",
            title: "Error",
            text: error.message || "No se pudieron generar los turnos",
            confirmButtonText: "Aceptar",
        });
        return null;
    }
}
