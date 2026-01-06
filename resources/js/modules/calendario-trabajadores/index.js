import { initCalendar } from "./calendar.js";

// Función para inicializar el calendario de trabajadores
function inicializarCalendarioTrabajadores() {
    const el = document.getElementById("calendario");
    if (!el) return;

    // Verificar que sea el calendario de trabajadores
    if (el.getAttribute('data-calendar-type') !== 'trabajadores') return;

    if (!window.AppPlanif) return;

    // Destruir calendario anterior si existe
    if (window.calendarTrabajadores) {
        try {
            window.calendarTrabajadores.destroy();
        } catch (e) {
            // Ignorar errores de destrucción
        }
        window.calendarTrabajadores = null;
    }

    const calendar = initCalendar(el, window.AppPlanif);
    window.calendarTrabajadores = calendar;
}

// Función para limpiar el calendario
function limpiarCalendario() {
    if (window.calendarTrabajadores) {
        try {
            window.calendarTrabajadores.destroy();
        } catch (e) {
            // Ignorar errores
        }
        window.calendarTrabajadores = null;
    }
}

// Inicializar en carga inicial
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCalendarioTrabajadores);
} else {
    inicializarCalendarioTrabajadores();
}

// Inicializar cuando se navega a esta página con Livewire (wire:navigate)
document.addEventListener('livewire:navigated', inicializarCalendarioTrabajadores);

// Limpiar al salir de la página con Livewire
document.addEventListener('livewire:navigating', limpiarCalendario);
