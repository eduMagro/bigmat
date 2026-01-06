export const cfg = () => window.AppPlanif;
export const CSRF = () => cfg().csrf;
export const R = () => cfg().routes;
export const DATA = () => {
    const appConfig = cfg() || {};
    // Asegurar que turnos siempre sea un array
    let turnos = appConfig.turnos;
    if (!Array.isArray(turnos)) {
        // Si turnosConfig existe y tiene turnos, usarlo
        turnos = appConfig.turnosConfig?.turnos || [];
    }
    return {
        maquinas: appConfig.maquinas || [],
        eventos: appConfig.eventos || [],
        cargaTrabajo: appConfig.cargaTrabajo || {},
        turnos: turnos,
    };
};
