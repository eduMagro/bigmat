<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\VacacionesController;
use App\Http\Controllers\AsignacionTurnoController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\FestivoController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\EpisController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\IrpfTramoController;
use App\Http\Controllers\SeguridadSocialController;
use App\Http\Controllers\ConvenioController;
use App\Http\Controllers\IncorporacionController;
use App\Http\Controllers\IncorporacionPublicaController;
use App\Http\Controllers\DocumentoEmpleadoController;
use App\Http\Controllers\PermisoAccesoController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\AjustesController;
use App\Http\Controllers\PoliticasController;
use App\Http\Controllers\RevisionFichajeController;
use App\Http\Controllers\AgrupacionTurnoController;

// Ruta principal - redirige según permisos
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        // Usuarios con acceso total o responsables van al dashboard
        if ($user->tieneAccesoTotal() || $user->esResponsableDepartamento()) {
            return redirect()->route('dashboard');
        }
        // Usuarios básicos van a su perfil
        return redirect()->route('usuarios.show', $user->id);
    }
    return redirect()->route('login');
});

// =============================================
// POLITICAS - RUTAS PUBLICAS (sin autenticacion)
// =============================================
Route::get('/politica-privacidad', [PoliticasController::class, 'privacidad'])->name('politicas.privacidad');
Route::get('/politica-cookies', [PoliticasController::class, 'cookies'])->name('politicas.cookies');
Route::get('/terminos-condiciones', [PoliticasController::class, 'terminos'])->name('politicas.terminos');

// POLITICAS - ACEPTACION Y REVOCACION (requiere auth, pero NO verificacion de politicas)
Route::middleware(['auth'])->group(function () {
    Route::get('/aceptar-politicas', [PoliticasController::class, 'mostrar'])->name('politicas.mostrar');
    Route::post('/aceptar-politicas', [PoliticasController::class, 'aceptar'])->name('politicas.aceptar');
    Route::post('/revocar-politicas', [PoliticasController::class, 'revocar'])->name('politicas.revocar');
});

// Dashboard principal
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'politicas.verificar', 'operario.redirect'])->name('dashboard');

// =============================================
// RUTAS DE RECURSOS HUMANOS
// =============================================

Route::middleware(['auth', 'verified', 'politicas.verificar', 'acceso.verificar', 'operario.redirect'])->group(function () {

    // === USUARIOS ===
    Route::resource('users', ProfileController::class)->except(['create', 'store']);
    Route::get('/users/{id}/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/users/{id}', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/exportar-usuarios', [ProfileController::class, 'exportarUsuarios'])->name('users.verExportar');
    Route::get('/users/{user}/resumen-asistencia', [ProfileController::class, 'resumenAsistencia'])->name('users.verResumen-asistencia');
    Route::get('/users/{user}/eventos-turnos', [ProfileController::class, 'eventosTurnos'])->name('users.verEventos-turnos');
    Route::post('/usuarios/{user}/cerrar-sesiones', [ProfileController::class, 'cerrarSesionesDeUsuario'])->name('usuarios.cerrarSesiones');
    Route::post('/usuarios/{user}/despedir', [ProfileController::class, 'despedirUsuario'])->name('usuarios.editarDespedir');
    Route::get('/mi-perfil/{user}', [PerfilController::class, 'show'])->name('usuarios.show');
    Route::get('/api/usuarios/operarios', [ProfileController::class, 'getOperarios'])->name('api.usuarios.operarios');
    Route::get('/usuarios/{user}/vacation-data', [ProfileController::class, 'getVacationData'])->name('usuarios.getVacationData');
    Route::post('/usuarios/subir-imagen', [ProfileController::class, 'subirImagen'])->name('usuarios.editarSubirImagen');
    Route::get('/perfil/imagen/{nombre}', function ($nombre) {
        $path = storage_path("app/public/perfiles/{$nombre}");
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path);
    })->name('usuarios.imagen');
    Route::post('/usuarios/{user}/update-fecha-incorporacion', [ProfileController::class, 'updateFechaIncorporacion'])->name('usuarios.updateFechaIncorporacion');
    Route::post('/usuarios/{user}/generar-turnos', [ProfileController::class, 'generarTurnos'])->name('profile.generar.turnos');

    // === VACACIONES ===
    Route::get('/vacaciones/usuarios-con-vacaciones', [VacacionesController::class, 'usuariosConVacaciones'])->name('vacaciones.usuariosConVacaciones');
    Route::get('/vacaciones/eventos', [VacacionesController::class, 'eventos'])->name('vacaciones.eventos');
    Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store'])->name('vacaciones.solicitar');
    Route::post('/vacaciones/asignar-directo', [VacacionesController::class, 'asignarDirecto'])->name('vacaciones.asignarDirecto');
    Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar'])->name('vacaciones.reprogramar');
    Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento'])->name('vacaciones.eliminarEvento');
    Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar'])->name('vacaciones.editarAprobar');
    Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar'])->name('vacaciones.editarDenegar');
    Route::get('/vacaciones/mis-solicitudes-pendientes', [VacacionesController::class, 'misSolicitudesPendientes'])->name('vacaciones.misSolicitudesPendientes');
    Route::delete('/vacaciones/solicitud/{id}', [VacacionesController::class, 'eliminarSolicitud'])->name('vacaciones.eliminarSolicitud');
    Route::post('/vacaciones/solicitud/eliminar-dias', [VacacionesController::class, 'eliminarDiasSolicitud'])->name('vacaciones.eliminarDiasSolicitud');
    Route::resource('vacaciones', VacacionesController::class);

    // === AJUSTES (Turnos y Festivos) ===
    Route::get('/ajustes', [AjustesController::class, 'index'])->name('ajustes.index');

    // === TURNOS Y ASIGNACIONES ===
    Route::resource('turnos', TurnoController::class);
    Route::patch('turnos/{turno}/toggle', [TurnoController::class, 'toggleActivo'])->name('turnos.toggle');
    Route::resource('asignaciones-turnos', AsignacionTurnoController::class);

    // === AGRUPACIONES DE TURNOS (Plantillas) ===
    Route::get('/agrupaciones-turnos', [AgrupacionTurnoController::class, 'index'])->name('agrupaciones-turnos.index');
    Route::post('/agrupaciones-turnos', [AgrupacionTurnoController::class, 'store'])->name('agrupaciones-turnos.store');
    Route::get('/agrupaciones-turnos/{agrupacionesTurno}', [AgrupacionTurnoController::class, 'show'])->name('agrupaciones-turnos.show');
    Route::put('/agrupaciones-turnos/{agrupacionesTurno}', [AgrupacionTurnoController::class, 'update'])->name('agrupaciones-turnos.update');
    Route::delete('/agrupaciones-turnos/{agrupacionesTurno}', [AgrupacionTurnoController::class, 'destroy'])->name('agrupaciones-turnos.destroy');
    Route::patch('/agrupaciones-turnos/{agrupacionesTurno}/toggle', [AgrupacionTurnoController::class, 'toggleActivo'])->name('agrupaciones-turnos.toggle');
    Route::post('/agrupaciones-turnos/asignar-usuario', [AgrupacionTurnoController::class, 'asignarUsuario'])->name('agrupaciones-turnos.asignarUsuario');
    Route::post('/fichar', [AsignacionTurnoController::class, 'fichar'])->name('users.fichar');
    Route::post('/asignaciones-turno/asignar-obra', [AsignacionTurnoController::class, 'asignarObra'])->name('asignaciones-turnos.asignarObra');
    Route::post('/asignaciones-turno/asignar-multiple', [AsignacionTurnoController::class, 'asignarObraMultiple'])->name('asignaciones-turnos.asignarObraMultiple');
    Route::get('/exportar-asignaciones', [AsignacionTurnoController::class, 'export'])->name('asignaciones-turnos.exportar');
    Route::post('/asignaciones-turnos/destroy', [AsignacionTurnoController::class, 'destroy'])->name('asignaciones-turnos.destroyByPost');

    // === NÓMINAS ===
    Route::resource('nominas', NominaController::class)->except(['destroy']);
    Route::post('/nominas/dividir', [NominaController::class, 'dividirNominas'])->name('nominas.dividir');
    Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales'])->name('generar.nominas');
    Route::get('/simulacion-irpf', [NominaController::class, 'formularioSimulacion'])->name('nomina.simulacion');
    Route::post('/simulacion-irpf', [NominaController::class, 'simular'])->name('nomina.simular');
    Route::post('/simulacion-inversa', [NominaController::class, 'simularDesdeNeto'])->name('nomina.inversa.calcular');
    Route::post('/nominas/descargar-mes', [NominaController::class, 'descargarNominasMes'])->name('nominas.crearDescargarMes');

    // === INCORPORACIONES ===
    Route::resource('incorporaciones', IncorporacionController::class)->parameters(['incorporaciones' => 'incorporacion']);
    Route::post('/incorporaciones/{incorporacion}/subir-documento', [IncorporacionController::class, 'subirDocumento'])->name('incorporaciones.crearSubirDocumento');
    Route::post('/incorporaciones/{incorporacion}/update-fecha', [IncorporacionController::class, 'updateFechaIncorporacion'])->name('incorporaciones.updateFecha');
    Route::delete('/incorporaciones/{incorporacion}/documento/{tipo}', [IncorporacionController::class, 'eliminarDocumento'])->name('incorporaciones.eliminarDocumento');
    Route::post('/incorporaciones/{incorporacion}/cambiar-estado', [IncorporacionController::class, 'cambiarEstado'])->name('incorporaciones.editarCambiarEstado');
    Route::post('/incorporaciones/{incorporacion}/marcar-enviado', [IncorporacionController::class, 'marcarEnlaceEnviado'])->name('incorporaciones.editarMarcarEnviado');
    Route::get('/incorporaciones/{incorporacion}/archivo/{archivo}', [IncorporacionController::class, 'verArchivo'])->name('incorporaciones.verArchivo');
    Route::get('/incorporaciones/{incorporacion}/descargar/{archivo}', [IncorporacionController::class, 'descargarArchivo'])->name('incorporaciones.verDescargarArchivo');
    Route::get('/incorporaciones/{incorporacion}/descargar-zip', [IncorporacionController::class, 'descargarZip'])->name('incorporaciones.verDescargarZip');
    Route::delete('/incorporaciones/{incorporacion}/eliminar-archivo', [IncorporacionController::class, 'eliminarArchivo'])->name('incorporaciones.eliminarArchivo');
    Route::post('/incorporaciones/{incorporacion}/resubir-archivo', [IncorporacionController::class, 'resubirArchivo'])->name('incorporaciones.editarResubirArchivo');
    Route::post('/incorporaciones/{incorporacion}/actualizar-campo', [IncorporacionController::class, 'actualizarCampo'])->name('incorporaciones.editarActualizarCampo');
    Route::get('/api/users/buscar-para-incorporacion', [IncorporacionController::class, 'buscarUsuarios'])->name('incorporaciones.buscarUsuarios');
    Route::post('/incorporaciones/{incorporacion}/crear-usuario', [IncorporacionController::class, 'crearOEncontrarUsuario'])->name('incorporaciones.crearUsuario');
    Route::get('/mi-contrato/descargar', [IncorporacionController::class, 'descargarMiContrato'])->name('incorporaciones.descargarMiContrato');

    // === DOCUMENTOS EMPLEADO ===
    Route::post('/documentos-empleado/{user}', [DocumentoEmpleadoController::class, 'store'])->name('documentos-empleado.store');
    Route::delete('/documentos-empleado/{documento}', [DocumentoEmpleadoController::class, 'destroy'])->name('documentos-empleado.destroy');
    Route::get('/documentos-empleado/{documento}/descargar', [DocumentoEmpleadoController::class, 'download'])->name('documentos-empleado.download');

    // === DEPARTAMENTOS ===
    Route::resource('departamentos', DepartamentoController::class);
    Route::post('/departamentos/{departamento}/asignar-usuarios', [DepartamentoController::class, 'asignarUsuarios'])->name('departamentos.asignar.usuarios');
    Route::post('/departamentos/{departamento}/asignar-secciones', [DepartamentoController::class, 'asignarSecciones'])->name('departamentos.asignarSecciones');
    Route::post('/departamentos/{departamento}/permisos', [DepartamentoController::class, 'actualizarPermiso']);
    Route::post('/departamentos/{departamento}/responsable', [DepartamentoController::class, 'cambiarResponsable'])->name('departamentos.cambiarResponsable');

    // === FESTIVOS ===
    Route::resource('festivos', FestivoController::class);

    // === EPIs ===
    Route::get('/epis', [EpisController::class, 'index'])->name('epis.index');
    Route::get('/epis/api/users', [EpisController::class, 'apiUsers'])->name('epis.api.users');
    Route::get('/epis/api/epis', [EpisController::class, 'apiEpis'])->name('epis.api.epis');
    Route::get('/epis/api/compras', [EpisController::class, 'apiCompras'])->name('epis.api.compras');
    Route::post('/epis/api/compras', [EpisController::class, 'apiCrearCompra'])->name('epis.api.compras.store');
    Route::post('/epis/import', [EpisController::class, 'import'])->name('epis.import');
    Route::post('/epis/catalogo', [EpisController::class, 'storeCatalogo'])->name('epis.catalogo.store');
    Route::post('/epis/usuarios/{user}/asignaciones', [EpisController::class, 'asignarAUsuario'])->name('epis.usuarios.asignaciones.store');
    Route::patch('/epis/usuarios/{user}/asignaciones/{asignacion}/devolver', [EpisController::class, 'devolverAsignacion'])->name('epis.usuarios.asignaciones.devolver');

    // === CONFIGURACIÓN FISCAL ===
    Route::resource('empresas', EmpresaController::class);
    Route::post('/empresas/obras/store', [EmpresaController::class, 'storeObra'])->name('empresas.obras.store');
    Route::post('/empresas/obras/update-field', [EmpresaController::class, 'updateObraField'])->name('empresas.obras.updateField');
    Route::post('/empresas/obras/destroy', [EmpresaController::class, 'destroyObra'])->name('empresas.obras.destroy');
    Route::post('/empresas/categorias/store', [EmpresaController::class, 'storeCategoria'])->name('empresas.categorias.store');
    Route::post('/empresas/categorias/update-field', [EmpresaController::class, 'updateCategoriaField'])->name('empresas.categorias.updateField');
    Route::post('/empresas/categorias/destroy', [EmpresaController::class, 'destroyCategoria'])->name('empresas.categorias.destroy');
    Route::resource('convenios', ConvenioController::class);
    Route::resource('irpf-tramos', IrpfTramoController::class);
    Route::resource('seguridad-social', SeguridadSocialController::class);

    // === SECCIONES ===
    // Rutas específicas ANTES del resource (evitar conflicto con {seccione})
    Route::post('/secciones/actualizar-orden', [SeccionController::class, 'actualizarOrden'])->name('secciones.actualizarOrden');
    Route::post('/secciones/sincronizar-todas', [SeccionController::class, 'sincronizarTodas'])->name('secciones.sincronizarTodas');
    Route::post('/secciones/crear-para-prefijo', [SeccionController::class, 'crearParaPrefijo'])->name('secciones.crearParaPrefijo');
    Route::get('/secciones/estado-sincronizacion', [SeccionController::class, 'estadoSincronizacion'])->name('secciones.estadoSincronizacion');
    Route::resource('secciones', SeccionController::class);

    // === ALERTAS ===
    // IMPORTANTE: Las rutas específicas deben ir ANTES del resource
    Route::post('/alertas/marcar-leidas', [AlertaController::class, 'marcarLeidas'])->name('alertas.marcarLeidas');
    Route::get('/alertas/sin-leer', [AlertaController::class, 'sinLeer'])->name('alertas.verSinLeer');
    Route::get('/alertas/{id}/hilo', [AlertaController::class, 'obtenerHilo'])->name('alertas.obtenerHilo');
    Route::resource('alertas', AlertaController::class);

    // === REVISION DE FICHAJES ===
    Route::post('/revision-fichaje/solicitar', [RevisionFichajeController::class, 'store'])->name('revision-fichaje.store');
    Route::post('/revision-fichaje/{id}/auto-rellenar', [RevisionFichajeController::class, 'autoRellenar'])->name('revision-fichaje.autoRellenar');
    Route::post('/revision-fichaje/{id}/denegar', [RevisionFichajeController::class, 'denegar'])->name('revision-fichaje.denegar');
    Route::get('/api/usuarios/{id}/fichajes-rango', [ProfileController::class, 'getFichajesRango'])->name('usuarios.fichajes-rango');

    // === PLANIFICACION ===
    Route::get('/planificacion/trabajadores', [ProduccionController::class, 'trabajadores'])->name('planificacion.trabajadores');
    Route::post('/planificacion/eliminar-asignacion', [ProduccionController::class, 'eliminarAsignacion'])->name('planificacion.eliminarAsignacion');
    Route::post('/planificacion/actualizar-fichaje', [ProduccionController::class, 'actualizarFichaje'])->name('planificacion.actualizarFichaje');
    Route::post('/planificacion/crear-asignacion', [ProduccionController::class, 'crearAsignacion'])->name('planificacion.crearAsignacion');
    Route::post('/planificacion/mover-asignacion', [ProduccionController::class, 'moverAsignacion'])->name('planificacion.moverAsignacion');
    Route::get('/planificacion/datos-formulario', [ProduccionController::class, 'datosFormulario'])->name('planificacion.datosFormulario');
    Route::get('/planificacion/datos-calendario', [ProduccionController::class, 'datosCalendario'])->name('planificacion.datosCalendario');

});

// === INCORPORACIÓN PÚBLICA (sin autenticación) ===
Route::get('/incorporacion/{token}', [IncorporacionPublicaController::class, 'show'])->name('incorporacion.publica');
Route::post('/incorporacion/{token}', [IncorporacionPublicaController::class, 'store'])->name('incorporacion.publica.store');

// Incluir rutas de autenticación
require __DIR__.'/auth.php';
