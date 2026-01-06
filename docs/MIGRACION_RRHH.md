# BIGMAT - Proyecto de Recursos Humanos

Este documento detalla todos los componentes a migrar desde la aplicación "manager" para crear la aplicación BIGMAT dedicada exclusivamente a Recursos Humanos.

---

## RESUMEN EJECUTIVO

| Componente | Cantidad |
|------------|----------|
| Modelos | 26 archivos |
| Controladores | 18 archivos |
| Middleware | 4 archivos |
| Observers | 1 archivo |
| Commands | 5 archivos |
| Jobs | 1 archivo |
| Vistas Blade | 50+ archivos |
| Migraciones | 28+ archivos |
| Componentes Livewire | 4 archivos |
| Servicios | 4 archivos |
| Helpers | 2 archivos |
| Exports | 2 archivos |
| Mails | 4 archivos |
| JavaScript Modules | 14+ archivos |
| Factory/Seeders | 2 archivos |
| Rutas | 80+ rutas |

---

## 1. MODELOS A MIGRAR

### Modelos Principales de RRHH

```
app/Models/
├── User.php                      # Usuario/Empleado (modelo central)
├── Vacaciones.php               # Días de vacaciones
├── VacacionesSolicitud.php      # Solicitudes de vacaciones
├── Nomina.php                   # Nóminas de empleados
├── Turno.php                    # Definición de turnos
├── AsignacionTurno.php          # Asignación de turnos a empleados
├── Incorporacion.php            # Proceso de incorporación
├── IncorporacionDocumento.php   # Documentos de incorporación
├── IncorporacionFormacion.php   # Formaciones en incorporación
├── IncorporacionLog.php         # Auditoría de incorporaciones
├── Departamento.php             # Departamentos
├── Festivo.php                  # Días festivos
├── DocumentoEmpleado.php        # Documentos personales
├── Epi.php                      # Equipos de protección
├── EpiUsuario.php               # Asignación EPIs a usuarios
├── EpiCompra.php                # Compras de EPIs
├── EpiCompraItem.php            # Items de compras EPIs
```

### Modelos de Configuración/Fiscalidad

```
app/Models/
├── Empresa.php                  # Empresas (HPR, etc.)
├── Categoria.php                # Categorías profesionales
├── Convenio.php                 # Convenios laborales
├── TasaIrpf.php                 # Tramos IRPF
├── TasaSeguridadSocial.php      # Porcentajes SS
├── Modelo145.php                # Modelo fiscal 145
```

### Modelos Adicionales

```
app/Models/
├── TrabajadorFicticio.php       # Trabajadores ficticios para pruebas
├── UserFcmToken.php             # Tokens Firebase para notificaciones
├── Seccion.php                  # Secciones/áreas de trabajo
├── PermisoAcceso.php            # Control de acceso por sección
```

### Modelos Relacionados (Evaluar necesidad)

```
app/Models/
├── Obra.php                     # Solo si se usan asignaciones a obra
├── Alerta.php                   # Sistema de alertas (tabla alertas_users)
```

---

## 2. CONTROLADORES A MIGRAR

```
app/Http/Controllers/
├── ProfileController.php                    # Gestión de usuarios
├── PerfilController.php                     # Vista de perfil
├── VacacionesController.php                 # Vacaciones
├── NominaController.php                     # Nóminas
├── TurnoController.php                      # Turnos
├── AsignacionTurnoController.php            # Asignaciones
├── IncorporacionController.php              # Incorporaciones
├── IncorporacionPublicaController.php       # Formulario público
├── DocumentoEmpleadoController.php          # Documentos
├── DepartamentoController.php               # Departamentos
├── FestivoController.php                    # Festivos
├── EpisController.php                       # EPIs
├── EmpresaController.php                    # Empresas
├── IrpfTramoController.php                  # Tramos IRPF
├── SeguridadSocialController.php            # Seguridad Social
├── ConvenioController.php                   # Convenios
├── PermisoAccesoController.php              # Permisos
├── PageController.php                       # Páginas (solo método recursosHumanos)
├── Auth/RegisteredUserController.php        # Registro de usuarios
├── Auth/AuthenticatedSessionController.php  # Login/Logout
├── SeccionController.php                    # Gestión de secciones
```

---

## 3. MIDDLEWARE A MIGRAR

```
app/Http/Middleware/
├── RoleMiddleware.php              # Control de roles de usuarios
├── VerificarPermisoAsistente.php   # Verificar permisos del asistente
├── VerificarAccesoSeccion.php      # Verificar acceso a secciones
├── VerificarClaveSeccion.php       # Verificar clave de sección
```

---

## 4. OBSERVERS A MIGRAR

```
app/Observers/
├── UserObserver.php                # Observer del modelo User (auditoría)
```

---

## 5. COMMANDS ARTISAN A MIGRAR

```
app/Console/Commands/
├── GenerarTurnosAnuales.php        # Generación automática de turnos anuales
├── ResetVacaciones.php             # Reset de vacaciones al inicio de año
├── ImportarFestivos.php            # Importación de festivos
├── SincronizarFestivosCommand.php  # Sincronización de festivos externos
├── VerificarFichajesEntrada.php    # Verificación de fichajes de entrada
```

---

## 6. JOBS A MIGRAR

```
app/Jobs/
├── SendFirebaseNotification.php    # Envío de notificaciones push
```

---

## 7. EXPORTS A MIGRAR

```
app/Exports/
├── UsersExport.php                 # Exportación de usuarios a Excel
├── AsignacionesTurnosExport.php    # Exportación de asignaciones de turnos
```

---

## 8. VISTAS BLADE A MIGRAR

### Vistas Principales

```
resources/views/
├── vacaciones/
│   └── index.blade.php
├── nominas/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── simulacion.blade.php
│   └── _form_datos_personales.blade.php
├── incorporaciones/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   ├── publica.blade.php
│   ├── publica-completada.blade.php
│   └── publica-cancelada.blade.php
├── asignaciones-turnos/
│   └── index.blade.php
├── configuracion/
│   └── turnos/
│       ├── index.blade.php
│       ├── create.blade.php
│       ├── edit.blade.php
│       └── form.blade.php
├── users/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── perfil/
│   └── show.blade.php
├── departamentos/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── epis/
│   └── index.blade.php
├── empresas/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
```

### Layouts y Componentes

```
resources/views/
├── layouts/
│   ├── app.blade.php
│   ├── guest.blade.php
│   └── navigation.blade.php
├── components/
│   ├── menu/                    # Componentes de menú
│   ├── modal.blade.php
│   ├── button.blade.php
│   └── [otros componentes comunes]
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
```

### Vistas de Email

```
resources/views/emails/
├── incorporaciones/
│   ├── aprobada-ceo.blade.php
│   └── pendiente-ceo.blade.php
```

---

## 4. MIGRACIONES A MIGRAR

### Orden recomendado de migración

```
database/migrations/
# 1. Tablas base
├── create_empresas_table.php
├── create_categorias_table.php
├── create_convenio_table.php
├── create_users_table.php

# 2. Tablas de configuración fiscal
├── create_tasas_irpf_table.php
├── create_tasas_seguridad_social_table.php

# 3. Tablas de departamentos
├── create_departamentos_table.php
├── create_departamento_seccion_table.php
├── create_departamento_user_table.php

# 4. Tablas de turnos
├── create_turnos_table.php
├── create_asignaciones_turnos_table.php

# 5. Tablas de vacaciones
├── create_vacaciones_table.php
├── create_solicitudes_vacaciones_table.php

# 6. Tablas de nóminas
├── create_nominas_table.php

# 7. Tablas de incorporación
├── create_incorporaciones_table.php
├── create_incorporacion_documentos_table.php
├── create_incorporacion_formaciones_table.php
├── create_incorporacion_logs_table.php

# 8. Tablas de EPIs
├── create_epis_table.php
├── create_epis_usuario_table.php
├── create_epi_compras_table.php
├── create_epi_compra_items_table.php

# 9. Tablas auxiliares
├── create_festivos_table.php
├── create_documento_empleados_table.php
├── create_permisos_acceso_table.php
```

---

## 5. COMPONENTES LIVEWIRE

```
app/Livewire/
├── AsignacionesTurnosTable.php
├── UsersTable.php
├── UsersTableMobile.php
├── SubirJustificante.php

resources/views/livewire/
├── asignaciones-turnos-table.blade.php
├── users-table.blade.php
├── users-table-mobile.blade.php
├── subir-justificante.blade.php
```

---

## 6. SERVICIOS Y HELPERS

### Servicios

```
app/Services/
├── FestivoService.php           # Lógica de festivos
├── OperarioService.php          # Servicios de operarios

app/Servicios/Turnos/
├── TurnoMapper.php              # Mapeo de turnos
├── ValidadorAsignaciones.php    # Validación de asignaciones
```

### Helpers

```
app/Helpers/
├── acceso.php                   # Control de acceso
├── SvgBarraHelper.php           # Barras SVG (gráficos)
```

---

## 7. CLASES DE EMAIL

```
app/Mail/
├── IncorporacionAprobadaCeoMail.php
├── IncorporacionPendienteCeoMail.php
├── IncorporacionCompletada.php
├── NominaEnviada.php
```

---

## 8. RUTAS A MIGRAR

### routes/web.php - Sección RRHH

```php
// === USUARIOS ===
Route::resource('users', ProfileController::class)->except(['create', 'store']);
Route::get('/users/{id}/edit', [ProfileController::class, 'edit']);
Route::patch('/profile', [ProfileController::class, 'update']);
Route::delete('/users/{id}', [ProfileController::class, 'destroy']);
Route::get('/users/{user}/resumen-asistencia', [ProfileController::class, 'resumenAsistencia']);
Route::get('/users/{user}/eventos-turnos', [ProfileController::class, 'eventosTurnos']);
Route::post('/usuarios/{user}/cerrar-sesiones', [ProfileController::class, 'cerrarSesionesDeUsuario']);
Route::post('/usuarios/{user}/despedir', [ProfileController::class, 'despedirUsuario']);
Route::get('/mi-perfil/{user}', [PerfilController::class, 'show']);

// === VACACIONES ===
Route::get('/vacaciones/usuarios-con-vacaciones', [VacacionesController::class, 'usuariosConVacaciones']);
Route::get('/vacaciones/eventos', [VacacionesController::class, 'eventos']);
Route::post('/vacaciones/solicitar', [VacacionesController::class, 'store']);
Route::post('/vacaciones/asignar-directo', [VacacionesController::class, 'asignarDirecto']);
Route::post('/vacaciones/reprogramar', [VacacionesController::class, 'reprogramar']);
Route::post('/vacaciones/eliminar-evento', [VacacionesController::class, 'eliminarEvento']);
Route::post('/vacaciones/{id}/aprobar', [VacacionesController::class, 'aprobar']);
Route::post('/vacaciones/{id}/denegar', [VacacionesController::class, 'denegar']);
Route::resource('vacaciones', VacacionesController::class);

// === TURNOS ===
Route::resource('turnos', TurnoController::class);
Route::patch('turnos/{turno}/toggle', [TurnoController::class, 'toggleActivo']);
Route::resource('asignaciones-turnos', AsignacionTurnoController::class);
Route::post('/fichar', [AsignacionTurnoController::class, 'fichar']);
Route::post('/profile/generar-turnos/{user}', [ProfileController::class, 'generarTurnos']);
Route::get('/api/usuarios/operarios', [ProfileController::class, 'getOperarios']);
Route::post('/asignaciones-turno/asignar-obra', [AsignacionTurnoController::class, 'asignarObra']);
Route::post('/asignaciones-turno/asignar-multiple', [AsignacionTurnoController::class, 'asignarObraMultiple']);

// === NÓMINAS ===
Route::resource('nominas', NominaController::class)->except(['destroy']);
Route::post('/generar-nominas', [NominaController::class, 'generarNominasMensuales']);
Route::get('/simulacion-irpf', [NominaController::class, 'formularioSimulacion']);
Route::post('/simulacion-irpf', [NominaController::class, 'simular']);
Route::post('/simulacion-inversa', [NominaController::class, 'simularDesdeNeto']);

// === INCORPORACIONES ===
Route::resource('incorporaciones', IncorporacionController::class);
Route::post('/incorporaciones/{incorporacion}/subir-documento', [IncorporacionController::class, 'subirDocumento']);
Route::post('/incorporaciones/{incorporacion}/cambiar-estado', [IncorporacionController::class, 'cambiarEstado']);
Route::post('/incorporaciones/{incorporacion}/aprobar-rrhh', [IncorporacionController::class, 'aprobarRrhh']);
Route::post('/incorporaciones/{incorporacion}/aprobar-ceo', [IncorporacionController::class, 'aprobarCeo']);
Route::get('/incorporacion/{token}', [IncorporacionPublicaController::class, 'show']);
Route::post('/incorporacion/{token}', [IncorporacionPublicaController::class, 'store']);

// === DOCUMENTOS EMPLEADO ===
Route::post('/documentos-empleado/{user}', [DocumentoEmpleadoController::class, 'store']);
Route::delete('/documentos-empleado/{documento}', [DocumentoEmpleadoController::class, 'destroy']);
Route::get('/documentos-empleado/{documento}/descargar', [DocumentoEmpleadoController::class, 'download']);

// === DEPARTAMENTOS ===
Route::resource('departamentos', DepartamentoController::class);
Route::post('/departamentos/{departamento}/asignar-usuarios', [DepartamentoController::class, 'asignarUsuarios']);
Route::post('/departamentos/{departamento}/permisos', [DepartamentoController::class, 'actualizarPermiso']);

// === EPIS ===
Route::get('/epis', [EpisController::class, 'index']);
Route::get('/epis/api/users', [EpisController::class, 'apiUsers']);
Route::get('/epis/api/epis', [EpisController::class, 'apiEpis']);
Route::post('/epis/api/compras', [EpisController::class, 'apiCrearCompra']);
Route::post('/epis/usuarios/{user}/asignaciones', [EpisController::class, 'asignarAUsuario']);
Route::patch('/epis/usuarios/{user}/asignaciones/{asignacion}/devolver', [EpisController::class, 'devolverAsignacion']);

// === FESTIVOS ===
Route::resource('festivos', FestivoController::class);

// === CONFIGURACIÓN FISCAL ===
Route::resource('empresas', EmpresaController::class);
Route::resource('categorias', CategoriaController::class);
Route::resource('convenios', ConvenioController::class);
Route::resource('irpf-tramos', IrpfTramoController::class);
Route::resource('seguridad-social', SeguridadSocialController::class);
```

---

## 9. JAVASCRIPT MODULES A MIGRAR

```
resources/js/modules/calendario-trabajadores/
├── index.js                     # Punto de entrada del módulo
├── calendar.js                  # Funcionalidad principal del calendario
├── config.js                    # Configuración del calendario
├── http.js                      # Peticiones HTTP/AJAX
│
├── dialogs/
│   ├── festivo.js               # Diálogo de gestión de festivos
│   ├── fichaje.js               # Diálogo de fichajes
│   ├── generarTurnos.js         # Diálogo de generación de turnos
│   └── propagarDia.js           # Diálogo de propagación de día
│
├── menu/
│   ├── baseMenu.js              # Menú contextual base
│   ├── cellMenu.js              # Menú de celda
│   ├── festivoMenu.js           # Menú de festivos
│   └── workerMenu.js            # Menú de trabajadores
│
└── utils/
    └── verificarConflictos.js   # Verificación de conflictos de asignación

resources/js/usersJs/
├── usersShow.js                 # JavaScript para vista de usuario
```

---

## 10. ARCHIVOS DE CONFIGURACIÓN

```
config/
├── menu.php                     # Configuración del menú (solo sección RRHH)
├── permission.php               # Configuración de permisos
├── acceso.php                   # Configuración de acceso
```

---

## 11. FACTORIES Y SEEDERS

```
database/factories/
├── UserFactory.php              # Factory para creación de usuarios

database/seeders/
├── DatabaseSeeder.php           # Seeder principal
├── AsistenteVirtualSeeder.php   # Seeder del asistente virtual
```

---

## 12. ASSETS Y RECURSOS

### JavaScript/CSS específicos

```
resources/js/
├── modules/calendario-trabajadores/  # Módulo completo de calendario

resources/css/
├── [estilos específicos de RRHH]

public/
├── storage/                     # Symlink a storage/app/public
│   ├── documentos/              # Documentos de empleados
│   ├── incorporaciones/         # Documentos de incorporación
│   ├── epis/                    # Imágenes de EPIs
│   └── avatars/                 # Fotos de usuarios
```

---

## 11. PAQUETES COMPOSER NECESARIOS

```json
{
    "require": {
        "livewire/livewire": "^3.0",
        "maatwebsite/excel": "^3.1",
        "barryvdh/laravel-dompdf": "^2.0",
        "intervention/image": "^2.7"
    }
}
```

Comandos a ejecutar:
```bash
composer require livewire/livewire
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require intervention/image
```

---

## 12. PASOS DE MIGRACIÓN RECOMENDADOS

### Fase 1: Estructura Base
1. Configurar .env con nueva base de datos "bigmat"
2. Copiar migraciones y ejecutar
3. Copiar modelos y ajustar namespaces
4. Copiar controladores y ajustar dependencias

### Fase 2: Vistas y Frontend
1. Copiar layouts base
2. Copiar componentes comunes
3. Copiar vistas específicas de RRHH
4. Ajustar rutas y nombres

### Fase 3: Funcionalidades
1. Configurar Livewire
2. Copiar componentes Livewire
3. Copiar servicios y helpers
4. Configurar emails

### Fase 4: Testing
1. Verificar autenticación
2. Probar cada módulo
3. Verificar integridad de datos
4. Ajustar permisos y roles

---

## 13. CONSIDERACIONES IMPORTANTES

### Dependencias a eliminar
- Todo lo relacionado con producción (planillas, elementos, máquinas, etc.)
- Sincronización con Ferrawin
- Gestión de obras y proyectos (solo mantener si es necesario para asignaciones)

### Modelo User
El modelo User deberá ser adaptado para eliminar relaciones con:
- Producción
- Planillas
- Elementos
- Máquinas

### Permisos
Revisar el sistema de permisos para mantener solo los relacionados con RRHH:
- Gestión de usuarios
- Gestión de vacaciones
- Gestión de nóminas
- Gestión de turnos
- Gestión de incorporaciones
- Gestión de EPIs

---

## 14. ESTRUCTURA FINAL SUGERIDA PARA BIGMAT

```
bigmat/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/
│   │   ├── ProfileController.php
│   │   ├── VacacionesController.php
│   │   ├── NominaController.php
│   │   ├── TurnoController.php
│   │   ├── AsignacionTurnoController.php
│   │   ├── IncorporacionController.php
│   │   ├── DepartamentoController.php
│   │   ├── EpisController.php
│   │   └── ConfiguracionController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Vacaciones.php
│   │   ├── Nomina.php
│   │   ├── Turno.php
│   │   ├── Incorporacion.php
│   │   ├── Departamento.php
│   │   └── Epi.php
│   ├── Livewire/
│   ├── Services/
│   ├── Helpers/
│   └── Mail/
├── database/migrations/
├── resources/views/
│   ├── layouts/
│   ├── auth/
│   ├── users/
│   ├── vacaciones/
│   ├── nominas/
│   ├── turnos/
│   ├── incorporaciones/
│   ├── departamentos/
│   └── epis/
├── routes/
│   └── web.php
└── config/
    └── menu.php
```

---

Documento generado para la migración del módulo de RRHH a la nueva aplicación BIGMAT.
