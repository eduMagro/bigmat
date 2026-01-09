# Sistema de Vacaciones - Documentacion de Cambios

## Resumen General

Se implemento un sistema completo de gestion de solicitudes de vacaciones con las siguientes funcionalidades:

1. **Solicitud de vacaciones** desde el perfil del usuario
2. **Aprobacion/Denegacion** via AJAX sin recargar pagina
3. **Eliminacion de solicitudes** pendientes (completas o dias especificos)
4. **Fusion inteligente** de solicitudes adyacentes (incluso separadas por fines de semana)
5. **Exclusion automatica** de fines de semana y festivos
6. **Validacion de limites** al momento de solicitar (no solo al aprobar)

---

## Proyectos Modificados

- **manager** (C:\xampp\htdocs\manager) - Proyecto principal donde se desarrollo primero
- **bigmat** (C:\xampp\htdocs\bigmat) - Replica del sistema

---

## Archivos Modificados en MANAGER

### 1. VacacionesController.php
**Ruta:** `app/Http/Controllers/VacacionesController.php`

**Metodos agregados/modificados:**

- `store()` - Mejorado con:
  - Exclusion de fines de semana y festivos al crear solicitud
  - Validacion de dias disponibles (aprobados + pendientes vs tope)
  - Fusion inteligente de solicitudes "laboralmente adyacentes"
  - Exclusion de dias que ya tienen vacaciones aprobadas

- `aprobar()` - Mejorado con:
  - Soporte AJAX (detecta `request()->ajax()`)
  - Exclusion de fines de semana y festivos al aprobar
  - Try-catch con respuesta JSON para errores

- `denegar()` - Mejorado con:
  - Soporte AJAX
  - Try-catch con respuesta JSON

- `eliminarSolicitud()` - **NUEVO**
  - Elimina una solicitud pendiente completa
  - Solo el propietario puede eliminar

- `eliminarDiasSolicitud()` - **NUEVO**
  - Elimina dias especificos de una solicitud
  - Puede dividir la solicitud si se eliminan dias del medio

- `misSolicitudesPendientes()` - **NUEVO**
  - API que devuelve las solicitudes pendientes del usuario autenticado

### 2. routes/web.php
**Rutas agregadas:**
```php
Route::get('/vacaciones/mis-solicitudes-pendientes', [VacacionesController::class, 'misSolicitudesPendientes'])->name('vacaciones.misSolicitudesPendientes');
Route::delete('/vacaciones/solicitud/{id}', [VacacionesController::class, 'eliminarSolicitud'])->name('vacaciones.eliminarSolicitud');
Route::post('/vacaciones/solicitud/eliminar-dias', [VacacionesController::class, 'eliminarDiasSolicitud'])->name('vacaciones.eliminarDiasSolicitud');
```

### 3. PerfilController.php
**Ruta:** `app/Http/Controllers/PerfilController.php`

**Agregado al config de rutas:**
```php
'misSolicitudesPendientesUrl' => route('vacaciones.misSolicitudesPendientes'),
'eliminarSolicitudUrl' => url('/vacaciones/solicitud'),
'eliminarDiasSolicitudUrl' => route('vacaciones.eliminarDiasSolicitud'),
```

### 4. calendario.js
**Ruta:** `public/js/calendario/calendario.js`

**Cambios:**
- Funcion `events` modificada para cargar solicitudes pendientes en paralelo
- Exclusion de fines de semana y festivos en la visualizacion de "V. pendiente"
- `eventClick` mejorado con modal de gestion:
  - Opcion de eliminar solicitud completa
  - Opcion de eliminar dias especificos con checkboxes

### 5. tabla-solicitudes.blade.php
**Ruta:** `resources/views/components/tabla-solicitudes.blade.php`

**Cambios:**
- Botones cambiados de formularios a `onclick` con funciones AJAX
- `aprobarSolicitud(id, btn)` y `denegarSolicitud(id, btn)`

### 6. vacaciones/index.blade.php
**Ruta:** `resources/views/vacaciones/index.blade.php`

**Funciones JavaScript agregadas:**
- `aprobarSolicitud()` - AJAX para aprobar
- `denegarSolicitud()` - AJAX para denegar
- `refrescarCalendarios()` - Actualiza calendarios sin recargar
- `verificarTablasVacias()` - Muestra mensaje si no quedan solicitudes

---

## Archivos Modificados en BIGMAT

### 1. VacacionesController.php
**Ruta:** `app/Http/Controllers/VacacionesController.php`

Mismos cambios que en manager.

### 2. routes/web.php
**Rutas agregadas:**
```php
Route::get('/vacaciones/mis-solicitudes-pendientes', [VacacionesController::class, 'misSolicitudesPendientes'])->name('vacaciones.misSolicitudesPendientes');
Route::delete('/vacaciones/solicitud/{id}', [VacacionesController::class, 'eliminarSolicitud'])->name('vacaciones.eliminarSolicitud');
Route::post('/vacaciones/solicitud/eliminar-dias', [VacacionesController::class, 'eliminarDiasSolicitud'])->name('vacaciones.eliminarDiasSolicitud');
```

### 3. PerfilController.php
**Ruta:** `app/Http/Controllers/PerfilController.php`

Agregadas las URLs de rutas igual que en manager.

### 4. ProfileController.php
**Ruta:** `app/Http/Controllers/ProfileController.php`

**Cambios en generacion de eventos (linea ~1085):**
- Exclusion de fines de semana y festivos al generar eventos de solicitudes pendientes/denegadas
```php
$festivosFechas = Festivo::pluck('fecha')->map(fn($f) => $f->format('Y-m-d'))->toArray();
// ... filtrar por isWeekend() y festivos
```

### 5. AsignacionTurnoController.php
**Ruta:** `app/Http/Controllers/AsignacionTurnoController.php`

**Cambio en asignacion masiva de turnos (linea ~1144):**
- Ahora excluye fines de semana y festivos al asignar turnos (no solo vacaciones)
```php
if (
    ($tipo === 'vacaciones' || $esTurno) &&
    (in_array($currentDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ||
        in_array($dateStr, $festivos))
) {
    continue;
}
```

### 6. calendario.js
**Ruta:** `public/js/calendario/calendario.js`

**Cambios:**
- Funcion `events` simplificada (no carga solicitudes por separado, vienen del backend)
- `eventClick` actualizado para detectar solicitudes con `es_solicitud_vacaciones && estado === 'pendiente'`
- Modal de gestion igual que manager

### 7. tabla-solicitudes.blade.php
**Ruta:** `resources/views/components/tabla-solicitudes.blade.php`

Mismos cambios que manager (botones AJAX).

### 8. vacaciones/index.blade.php
**Ruta:** `resources/views/vacaciones/index.blade.php`

**Cambios:**
- Funciones AJAX para aprobar/denegar
- URLs actualizadas con `{{ url() }}` para subdirectorios
- Mejor manejo de errores en fetch

---

## Logica de Negocio Importante

### Fusion Inteligente de Solicitudes
Cuando un usuario solicita vacaciones, el sistema busca solicitudes pendientes que sean "laboralmente adyacentes" (separadas solo por fines de semana o festivos) y las fusiona en una sola.

**Ejemplo:**
- Solicitud existente: 20-23 enero (lun-jue)
- Nueva solicitud: 27-28 enero (lun-mar)
- Si 24 (vie), 25-26 (sab-dom) son no laborables -> Se fusionan en: 20-28 enero

### Validacion de Limites
Al solicitar vacaciones se valida:
1. Dias ya aprobados en el año
2. Dias en solicitudes pendientes
3. Total disponible = tope - (aprobados + pendientes)
4. Si la nueva solicitud excede -> Error

### Exclusion de Dias
Se excluyen automaticamente:
- Fines de semana (sabado y domingo)
- Festivos (de la tabla `festivos`)
- Dias que ya tienen estado 'vacaciones'

---

## Pendientes / Mejoras Futuras

1. [ ] Notificaciones en tiempo real cuando se aprueba/deniega
2. [ ] Historial de cambios en solicitudes
3. [ ] Exportar calendario de vacaciones a PDF/Excel
4. [ ] Validar solapamiento con otros usuarios del mismo equipo

---

## Comandos Utiles

```bash
# Limpiar cache de rutas en bigmat
cd C:\xampp\htdocs\bigmat
php artisan route:clear
php artisan cache:clear

# Ver rutas de vacaciones
php artisan route:list --name=vacaciones
```

---

## Fecha de Documentacion
Enero 2026
