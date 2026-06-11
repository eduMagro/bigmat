<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Turno;
use App\Models\AgrupacionTurno;
use App\Models\Festivo;
use App\Models\VacacionesSolicitud;
use App\Models\Categoria;
use App\Models\AsignacionTurno;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Obra;
use App\Models\Empresa;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Session;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session as FacadeSession;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use App\Servicios\Turnos\TurnoMapper;
use App\Services\OperarioService;

class ProfileController extends Controller
{
    private function escapeLike(string $value): string
    {
        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
        return "%{$value}%";
    }

    public function exportarUsuarios(Request $request)
    {
        $usuariosFiltrados = $this->aplicarFiltros($request)
            ->with(['empresa', 'categoria'])
            ->get();


        return Excel::download(new UsersExport($usuariosFiltrados), 'usuarios-app.xlsx');
    }
    // Limpieza del duplicado en filtrosActivos()
    private function filtrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('id'))
            $filtros[] = 'ID: <strong>' . e($request->id) . '</strong>';
        if ($request->filled('nombre_completo'))
            $filtros[] = 'Nombre: <strong>' . e($request->nombre_completo) . '</strong>';
        if ($request->filled('email'))
            $filtros[] = 'Email: <strong>' . e($request->email) . '</strong>';
        if ($request->filled('movil_personal'))
            $filtros[] = 'Móvil Personal: <strong>' . e($request->movil_personal) . '</strong>';
        if ($request->filled('movil_empresa'))
            $filtros[] = 'Móvil Empresa: <strong>' . e($request->movil_empresa) . '</strong>';
        if ($request->filled('numero_corto'))
            $filtros[] = 'Nº Corporativo: <strong>' . e($request->numero_corto) . '</strong>';
        if ($request->filled('dni'))
            $filtros[] = 'DNI: <strong>' . e($request->dni) . '</strong>';

        if ($request->filled('empresa_id')) {
            $empresa = \App\Models\Empresa::find($request->empresa_id);
            $filtros[] = 'Empresa: <strong>' . e($empresa->nombre ?? ('ID ' . $request->empresa_id)) . '</strong>';
        } elseif ($request->filled('empresa')) {
            $filtros[] = 'Empresa: <strong>' . e($request->empresa) . '</strong>';
        }

        if ($request->filled('categoria_id')) {
            $nombreCategoria = Categoria::find($request->categoria_id)?->nombre ?? 'Desconocida';
            $filtros[] = 'Categoría: <strong>' . e($nombreCategoria) . '</strong>';
        } elseif ($request->filled('categoria')) {
            $filtros[] = 'Categoría: <strong>' . e($request->categoria) . '</strong>';
        }

        if ($request->filled('turno'))
            $filtros[] = 'Turno: <strong>' . e($request->turno) . '</strong>';
        if ($request->filled('rol'))
            $filtros[] = 'Rol: <strong>' . e($request->rol) . '</strong>';

        if ($request->filled('estado')) {
            $filtros[] = 'Estado: <strong>' . ucfirst(e($request->estado)) . '</strong>';
        }

        if ($request->filled('sort')) {
            $sorts = [
                'nombre_completo' => 'Nombre',
                'email' => 'Email',
                'dni' => 'DNI',
                'empresa' => 'Empresa',
                'rol' => 'Rol',
                'categoria' => 'Categoría',
                'turno' => 'Turno',
                'estado' => 'Estado',
                'numero_corto' => 'Nº Corporativo',
                'id' => 'ID',
            ];
            $orden = strtolower($request->order) === 'desc' ? 'descendente' : 'ascendente';
            $filtros[] = 'Ordenado por <strong>' . e($sorts[$request->sort] ?? $request->sort) . "</strong> en orden <strong>$orden</strong>";
        }

        if ($request->filled('per_page')) {
            $filtros[] = 'Mostrando <strong>' . e($request->per_page) . '</strong> registros por página';
        }

        return $filtros;
    }

    private function getOrdenamiento(string $columna, string $titulo): string
    {
        $currentSort = request('sort');
        $currentOrder = request('order');
        $isSorted = $currentSort === $columna;
        $nextOrder = ($isSorted && $currentOrder === 'asc') ? 'desc' : 'asc';

        $icon = $isSorted
            ? ($currentOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down')
            : 'fas fa-sort';

        // Sanitiza la URL de ordenamiento
        $url = request()->fullUrlWithQuery([
            'sort' => $columna,
            'order' => $nextOrder
        ]);

        return '<a href="' . e($url) . '" class="text-white text-decoration-none">'
            . e($titulo) . ' <i class="' . $icon . '"></i></a>';
    }

    public function aplicarFiltros(Request $request)
    {
        // Mapa de orden seguro (añadimos nombres "bonitos" cuando haga falta join)
        $ordenables = [
            'id' => 'users.id',
            'nombre_completo' => DB::raw("CONCAT_WS(' ', users.name, users.primer_apellido, users.segundo_apellido)"),
            'email' => 'users.email',
            'numero_corto' => DB::raw('CAST(users.numero_corto AS UNSIGNED)'),
            'dni' => 'users.dni',
            'empresa' => 'empresas.nombre',
            'rol' => 'users.rol',
            'categoria' => 'categorias.nombre',
            'turno' => 'users.turno',
            'estado' => 'users.estado',
        ];

        $query = User::query()->select('users.*');

        // 🔹 FILTROS

        if ($request->filled('id')) {
            $query->where('users.id', $request->id);
        }

        if ($request->filled('nombre_completo')) {
            $like = $this->escapeLike($request->input('nombre_completo'));
            $query->whereRaw(
                "CONCAT_WS(' ', users.name, users.primer_apellido, users.segundo_apellido) LIKE ? ESCAPE '\\\\'",
                [$like]
            );
        }

        if ($request->filled('email')) {
            $query->where('users.email', 'like', $this->escapeLike($request->input('email')));
        }

        if ($request->filled('movil_personal')) {
            $query->where('users.movil_personal', 'like', $this->escapeLike($request->input('movil_personal')));
        }

        if ($request->filled('movil_empresa')) {
            $query->where('users.movil_empresa', 'like', $this->escapeLike($request->input('movil_empresa')));
        }

        if ($request->filled('numero_corto')) {
            // aunque sea numérico, permitimos contains
            $query->whereRaw('CAST(users.numero_corto AS CHAR) LIKE ? ESCAPE \'\\\\\'', [$this->escapeLike($request->input('numero_corto'))]);
        }

        if ($request->filled('dni')) {
            $query->where('users.dni', 'like', $this->escapeLike($request->input('dni')));
        }

        // Empresa: por ID exacto…
        if ($request->filled('empresa_id')) {
            $query->where('users.empresa_id', $request->empresa_id);
        }
        // …o por nombre contains (param 'empresa')
        if ($request->filled('empresa')) {
            $like = $this->escapeLike($request->empresa);
            $query->whereHas('empresa', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Categoría: por ID…
        if ($request->filled('categoria_id')) {
            $query->where('users.categoria_id', $request->categoria_id);
        }
        // …o por nombre (param 'categoria')
        if ($request->filled('categoria')) {
            $like = $this->escapeLike($request->categoria);
            $query->whereHas('categoria', function ($q) use ($like) {
                $q->whereRaw("nombre LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        // Turno y rol con contains (cámbialo a '=' si deben ser exactos)
        if ($request->filled('turno')) {
            $query->where('users.turno', 'like', $this->escapeLike($request->input('turno')));
        }
        if ($request->filled('rol')) {
            $query->where('users.rol', 'like', $this->escapeLike($request->input('rol')));
        }

        // 🔹 ORDENAMIENTO
        $sort = $request->input('sort');
        $order = in_array(strtolower($request->input('order', 'asc')), ['asc', 'desc']) ? strtolower($request->input('order', 'asc')) : 'asc';

        // Si el orden implica campos de tablas relacionadas, hacemos join (sin romper el select users.*)
        if ($sort === 'empresa') {
            $query->leftJoin('empresas', 'empresas.id', '=', 'users.empresa_id');
        }
        if ($sort === 'categoria') {
            $query->leftJoin('categorias', 'categorias.id', '=', 'users.categoria_id');
        }

        if (isset($ordenables[$sort])) {
            $query->orderBy($ordenables[$sort], $order);
        } else {
            // orden por defecto
            $query->orderBy('users.created_at', 'desc');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $auth = auth()->user();

        if (!$auth) {
            return redirect()->route('login');
        }

        // si no es oficina, lo llevamos a su propia ficha
        if ($auth->rol !== 'oficina') {
            return redirect()->route('users.show', $auth->id);
        }

        // A partir de aquí, solo oficina: tabla de usuarios
        $usuariosConectados = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count();

        $obras = collect();
        $obrasHierrosPacoReyes = collect();

        $categorias = Categoria::orderBy('nombre')->get();
        $empresas = Empresa::orderBy('nombre')->get();
        $maquinas = collect();
        $roles = User::distinct()->pluck('rol')->filter()->sort();
        $turnos = User::distinct()->pluck('turno')->filter()->sort();
        $totalSolicitudesPendientes = VacacionesSolicitud::where('estado', 'pendiente')->count();

        $ordenables = [
            'id' => $this->getOrdenamiento('id', 'ID'),
            'nombre_completo' => $this->getOrdenamiento('nombre_completo', 'Nombre'),
            'email' => $this->getOrdenamiento('email', 'Email'),
            'numero_corto' => $this->getOrdenamiento('numero_corto', 'Nº Corporativo'),
            'dni' => $this->getOrdenamiento('dni', 'DNI'),
            'empresa' => $this->getOrdenamiento('empresa', 'Empresa'),
            'rol' => $this->getOrdenamiento('rol', 'Rol'),
            'categoria' => $this->getOrdenamiento('categoria', 'Categoría'),
            'turno' => $this->getOrdenamiento('turno', 'Turno'),
            'estado' => $this->getOrdenamiento('estado', 'Estado'),
        ];

        // Obtener usuarios según filtros (sin paginar aún)
        $usuarios = $this->aplicarFiltros($request)->with('categoria', 'empresa')->get();

        // Filtrado por estado de conexión en colección
        if ($request->filled('estado')) {
            if ($request->estado === 'activo') {
                $usuarios = $usuarios->filter(fn($u) => $u->isOnline());
            } elseif ($request->estado === 'inactivo') {
                $usuarios = $usuarios->filter(fn($u) => !$u->isOnline());
            }
        }

        // Paginar manualmente la colección
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $request->input('per_page', 10);
        $offset = ($currentPage - 1) * $perPage;

        $registrosUsuarios = new LengthAwarePaginator(
            $usuarios->slice($offset, $perPage)->values(),
            $usuarios->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filtrosActivos = $this->filtrosActivos($request);

        // OJO: aquí NO pasamos 'user', 'resumen', ni 'horasMensuales'
        return view('User.index', compact(
            'registrosUsuarios',
            'usuariosConectados',
            'obras',
            'empresas',
            'categorias',
            'maquinas',
            'roles',
            'turnos',
            'filtrosActivos',
            'ordenables',
            'totalSolicitudesPendientes',
            'obrasHierrosPacoReyes'
        ));
    }



    public function show($user)
    {
        $auth = auth()->user();
        $id = $user instanceof User ? $user->id : $user;

        if ($auth->rol !== 'oficina' && (int) $auth->id !== (int) $id) {
            return back()->with('error', 'No tienes permiso para ver este perfil.');
        }

        // Cargar solo las relaciones necesarias para la ficha (NO asignacionesTurnos - el calendario las carga via AJAX)
        $user = User::with(['categoria', 'empresa', 'departamentos', 'incorporacion'])->findOrFail($id);

        // Cargar nombres de turnos con caché (raramente cambian)
        $turnosNombres = cache()->remember('turnos_nombres_config', 3600, function () {
            return Turno::activos()->ordenados()->pluck('nombre')->map(fn($n) => ['nombre' => $n])->values()->toArray();
        });

        // Obtener resumen y conteo de vacaciones en una sola consulta optimizada
        $resumen = $this->getResumenAsistencia($user);

        // Horas mensuales - consulta optimizada
        $horasMensuales = $this->getHorasMensuales($user);

        $esOficina = $auth->rol === 'oficina';

        // Precargar eventos del mes actual para evitar AJAX inicial
        $initialEvents = $this->getEventosRangoOptimizado($user, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $config = [
            'userId' => $user->id,
            'locale' => 'es',
            'csrfToken' => csrf_token(),
            'routes' => [
                'eventosUrl' => route('users.verEventos-turnos', $user->id),
                'resumenUrl' => route('users.verResumen-asistencia', ['user' => $user->id]),
                'vacacionesStoreUrl' => route('vacaciones.solicitar'),
                'vacacionesDeleteUrl' => url('/vacaciones/solicitud'),
                'storeUrl' => route('asignaciones-turnos.store'),
                'destroyUrl' => route('asignaciones-turnos.destroyByPost'),
                'vacationDataUrl' => route('usuarios.getVacationData', ['user' => $user->id]),
                'fichajesRangoUrl' => route('usuarios.fichajes-rango', ['id' => $user->id]),
                'revisionFichajeStoreUrl' => route('revision-fichaje.store'),
                'misSolicitudesPendientesUrl' => route('vacaciones.misSolicitudesPendientes'),
                'eliminarSolicitudUrl' => url('/vacaciones/solicitud'),
                'eliminarDiasSolicitudUrl' => route('vacaciones.eliminarDiasSolicitud'),
                'eliminarEstadoVacacionesUrl' => route('vacaciones.eliminarEvento'),
            ],
            'enableListMonth' => true,
            'mobileBreakpoint' => 768,
            'permissions' => [
                'canRequestVacations' => !$esOficina,
                'canEditHours' => $esOficina,
                'canAssignShifts' => $esOficina,
                'canAssignStates' => $esOficina,
            ],
            'turnos' => $turnosNombres,
            'fechaIncorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            // Usar el conteo del resumen en lugar de consulta adicional
            'diasVacacionesAsignados' => $resumen['diasVacaciones'],
            // Eventos precargados para evitar AJAX inicial
            'initialEvents' => $initialEvents,
        ];

        // Variable turnos para compatibilidad con la vista (ya no se usa para iterar)
        $turnos = collect();

        return view('User.show', compact(
            'user',
            'turnos',
            'resumen',
            'horasMensuales',
            'config'
        ));
    }

    /**
     * Buscador rápido de usuarios para navegar de un perfil a otro (solo oficina).
     */
    public function buscar(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $usuarios = User::where(function ($query) use ($q) {
                $query->whereRaw("CONCAT(name, ' ', COALESCE(primer_apellido,''), ' ', COALESCE(segundo_apellido,'')) LIKE ?", ["%{$q}%"]);
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'primer_apellido', 'segundo_apellido', 'imagen']);

        return response()->json($usuarios->map(fn($u) => [
            'id' => $u->id,
            'nombre' => $u->nombre_completo,
            'imagen' => $u->ruta_imagen,
        ]));
    }

    private function getHorasMensuales(User $user): array
    {
        $inicioMes = Carbon::now()->startOfMonth()->toDateString();
        $hoy = Carbon::now()->toDateString();
        $finMes = Carbon::now()->endOfMonth()->toDateString();

        // Consulta optimizada: solo campos necesarios
        $asignacionesMes = AsignacionTurno::where('user_id', $user->id)
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->where('estado', 'activo')
            ->select('fecha', 'entrada', 'salida')
            ->get();

        $horasTrabajadas = 0;
        $diasConErrores = 0;
        $diasHastaHoy = 0;
        $totalAsignacionesMes = $asignacionesMes->count();

        foreach ($asignacionesMes as $asignacion) {
            $fechaStr = $asignacion->fecha instanceof \Carbon\Carbon
                ? $asignacion->fecha->toDateString()
                : $asignacion->fecha;

            if ($fechaStr <= $hoy) {
                $diasHastaHoy++;
            }

            if ($asignacion->entrada && $asignacion->salida) {
                $horaEntrada = Carbon::parse($asignacion->entrada);
                $horaSalida = Carbon::parse($asignacion->salida);
                $horasDia = $horaSalida->diffInMinutes($horaEntrada) / 60;
                $horasTrabajadas += max($horasDia, 8);
            } elseif ($fechaStr < $hoy) {
                $diasConErrores++;
            }
        }

        return [
            'horas_trabajadas' => $horasTrabajadas,
            'horas_deberia_llevar' => $diasHastaHoy * 8,
            'dias_con_errores' => $diasConErrores,
            'horas_planificadas_mes' => $totalAsignacionesMes * 8,
        ];
    }

    protected function getColoresTurnosYEstado(): array
    {
        // Colores base para turnos
        $coloresBase = [
            'mañana' => [
                'bg' => '#008000',
                'border' => $this->darkenColor('#008000'),
                'text' => '#FFFFFF'
            ],
            'tarde' => [
                'bg' => '#0000FF',
                'border' => $this->darkenColor('#0000FF'),
                'text' => '#FFFFFF'
            ],
            'noche' => [
                'bg' => '#FFFF00',
                'border' => $this->darkenColor('#FFFF00'),
                'text' => '#000000'
            ]
        ];

        // Estados manuales
        $estados = [
            'vacaciones' => [
                'bg' => '#f87171',
                'border' => '#dc2626',
                'text' => '#FFFFFF'
            ],
            'baja' => [
                'bg' => '#a855f7',
                'border' => '#9333ea',
                'text' => '#FFFFFF'
            ],
            'festivo' => [
                'bg' => '#ff0000', // Rojo para festivos
                'border' => '#b91c1c',
                'text' => 'white'
            ],
            'falta_justificada' => [
                'bg' => '#808080',
                'border' => '#4b5563',
                'text' => '#FFFFFF'
            ],
            'falta_injustificada' => [
                'bg' => '#000000',
                'border' => '#000000',
                'text' => '#FFFFFF'
            ]
        ];

        // Obtener todos los turnos desde la base de datos
        $turnos = Turno::all();

        // Asignar colores a los turnos registrados, con claves en minúsculas
        $turnosColoreados = $turnos->mapWithKeys(function ($turno) use ($coloresBase) {
            $clave = strtolower($turno->nombre);
            return [
                $clave => $coloresBase[$clave] ?? [
                    'bg' => '#708090',
                    'border' => $this->darkenColor('#708090'),
                    'text' => '#FFFFFF'
                ]
            ];
        });

        return array_merge($turnosColoreados->toArray(), $estados);
    }

    protected function getEventosTurnos($user)
    {
        $coloresTurnos = $this->getColoresTurnosYEstado();

        return $user->asignacionesTurnos->flatMap(function ($asignacion) use ($coloresTurnos) {
            $eventos = [];

            $fecha = Carbon::parse($asignacion->fecha)->toDateString(); // mejor solo fecha para allDay

            // 🎯 Evento de turno
            if ($asignacion->turno) {
                $turnoNombre = $asignacion->turno->nombre;
                $colorTurno = $coloresTurnos[$turnoNombre] ?? [
                    'bg' => '#0275d8',
                    'border' => '#025aa5',
                    'text' => '#FFFFFF'
                ];

                $eventos[] = [
                    'id' => 'turno-' . $asignacion->id,
                    'title' => ucfirst($turnoNombre),
                    'start' => $fecha,
                    'backgroundColor' => $colorTurno['bg'],
                    'borderColor' => $colorTurno['border'],
                    'textColor' => $colorTurno['text'],
                    'allDay' => true,
                    'extendedProps' => [
                        'asignacion_id' => $asignacion->id,
                        'fecha' => $asignacion->fecha,
                        'entrada' => $asignacion->entrada,
                        'salida' => $asignacion->salida,
                        'es_turno' => true,
                        'obra_nombre' => $asignacion->obra?->obra,
                        'turno_nombre' => $asignacion->turno?->nombre,
                    ],
                ];
            }

            // 🎯 Evento de estado
            if ($asignacion->estado && strtolower($asignacion->estado) !== 'activo') {
                $estadoNombre = ucfirst($asignacion->estado);
                $colorEstado = $coloresTurnos[$asignacion->estado] ?? [
                    'bg' => '#f87171',
                    'border' => '#dc2626',
                    'text' => '#FFFFFF'
                ];

                $eventos[] = [
                    'id' => 'estado-' . $asignacion->id,
                    'title' => $estadoNombre,
                    'start' => $fecha,
                    'backgroundColor' => $colorEstado['bg'],
                    'borderColor' => $colorEstado['border'],
                    'textColor' => $colorEstado['text'],
                    'allDay' => true,
                    'extendedProps' => [
                        'asignacion_id' => $asignacion->id,
                        'fecha' => $asignacion->fecha,
                        'entrada' => $asignacion->entrada,
                        'salida' => $asignacion->salida,
                        'es_turno' => false
                    ],
                ];
            }

            return $eventos;
        });
    }

    protected function getEventosFichajes($user)
    {
        return $user->asignacionesTurnos->flatMap(function ($asignacion) {
            $eventos = [];
            $soloFecha = Carbon::parse($asignacion->fecha)->format('Y-m-d');

            // Helper para formatear hora
            $formatHora = fn($hora) => $hora ? substr(trim($hora), 0, 5) : null;

            $entrada1 = $formatHora($asignacion->entrada);
            $salida1 = $formatHora($asignacion->salida);
            $entrada2 = $formatHora($asignacion->entrada2);
            $salida2 = $formatHora($asignacion->salida2);

            // Evento único de fichajes con todas las jornadas
            if ($entrada1 || $salida1 || $entrada2 || $salida2) {
                $eventos[] = [
                    'id' => "fichajes-{$asignacion->id}",
                    'start' => $soloFecha,
                    'allDay' => true,
                    'display' => 'block',
                    'order' => 10,
                    'classNames' => ['fichaje-evento'],
                    'backgroundColor' => 'transparent',
                    'borderColor' => 'transparent',
                    'extendedProps' => [
                        'tipo' => 'fichajes',
                        'asignacion_id' => $asignacion->id,
                        'fecha' => $soloFecha,
                        'entrada1' => $entrada1,
                        'salida1' => $salida1,
                        'entrada2' => $entrada2,
                        'salida2' => $salida2,
                        'tieneSegundaJornada' => ($entrada2 || $salida2) ? true : false,
                    ],
                ];
            }

            return $eventos;
        });
    }

    /**
     * Función para oscurecer un color en hexadecimal.
     */
    private function darkenColor($hex, $percent = 20)
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) !== 6) {
            return '#000000'; // Fallback a negro si formato inválido
        }
        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];

        foreach ($rgb as &$value) {
            $value = max(0, min(255, $value - ($value * ($percent / 100))));
        }

        return sprintf("#%02X%02X%02X", $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit($id)
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // Buscar el usuario que se quiere editar
        $user = User::findOrFail($id);

        // 🔒 Verificar si el usuario autenticado pertenece al departamento de programador o administrador (solo para nota informativa)
        $puedeEditarDniEmail = $authUser->departamentos()->whereIn('nombre', ['Programador', 'Administrador'])->exists();
        $sesiones = Session::where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($sesion) {
                return [
                    'id' => $sesion->id,
                    'ip_address' => $sesion->ip_address,
                    'user_agent' => $sesion->user_agent,
                    'ultima_actividad' => \Carbon\Carbon::createFromTimestamp($sesion->last_activity)->format('d/m/Y H:i:s'),
                    'actual' => $sesion->id === FacadeSession::getId(), // comparado con el del admin, puede omitirse
                ];
            });
        return view('profile.edit', compact('user', 'sesiones'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request, $id)
    {
        // Obtener el usuario autenticado
        $authUser = auth()->user();

        // 🔒 Verificar si el usuario autenticado pertenece al departamento de programador o administrador
        $puedeEditarDniEmail = $authUser->departamentos()->whereIn('nombre', ['Programador', 'Administrador'])->exists();

        // Buscar el usuario que se quiere actualizar
        $user = User::findOrFail($id);

        // 🔒 Si NO es programador/administrador y está intentando editar DNI o email, denegar
        if (!$puedeEditarDniEmail && ($request->has('dni') || $request->has('email'))) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar DNI y email. Solo los departamentos de Programador y Administrador pueden editar estos campos.'
                ], 403);
            }
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para editar DNI y email. Solo los departamentos de Programador y Administrador pueden editar estos campos.');
        }

        // Validación inline para peticiones JSON
        if ($request->wantsJson()) {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'primer_apellido' => 'nullable|string|max:255',
                'segundo_apellido' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email,' . $id,
                'movil_personal' => 'nullable|string|max:20',
                'movil_empresa' => 'nullable|string|max:20',
                'numero_corto' => 'nullable|string|max:4',
                'dni' => 'nullable|string|max:20',
                'empresa_id' => 'nullable|exists:empresas,id',
                'rol' => 'nullable|string|in:operario,oficina,transportista,visitante',
                'categoria_id' => 'nullable|exists:categorias,id',
                'maquina_id' => 'nullable|exists:maquinas,id',
                'turno' => 'nullable|string|in:diurno,nocturno,mañana,flexible',
            ]);

            $user->fill($validated);
        } else {
            // Usar ProfileUpdateRequest para formularios normales
            $validated = $request->validate((new ProfileUpdateRequest())->rules());
            $user->fill($validated);
        }

        if ($request->filled('email') && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Responder según el tipo de petición
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'user' => $user
            ]);
        }

        return Redirect::route('profile.edit', ['id' => $id])->with('status', 'profile-updated');
    }

    public function subirImagen(Request $request)
    {
        $request->validate([
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        // Si se pasa user_id, usar ese usuario (para oficina editando otro perfil)
        // Si no, usar el usuario autenticado
        $userId = $request->input('user_id', auth()->id());
        $user = User::find($userId);

        // Verificar permisos: solo el propio usuario o usuarios de oficina/admin pueden cambiar la foto
        $authUser = auth()->user();
        if ($authUser->id != $userId && !in_array($authUser->rol, ['oficina', 'admin', 'administrador'])) {
            return back()->with('error', 'No tienes permisos para cambiar esta foto.');
        }

        if ($request->hasFile('imagen')) {
            $archivo = $request->file('imagen');

            // Borrar anterior si existe
            if ($user->imagen) {
                Storage::disk('public')->delete("perfiles/{$user->imagen}");
            }

            $driver = null;
            if (extension_loaded('gd')) {
                $driver = new GdDriver();
            } elseif (extension_loaded('imagick')) {
                $driver = new ImagickDriver();
            }

            if ($driver) {
                // Normalizar y optimizar imagen de perfil (300x300 JPG)
                $manager = new ImageManager($driver);
                $imagen = $manager->read($archivo)->cover(300, 300)->toJpeg(85);
                $nombreArchivo = 'perfil_' . $user->id . '_' . uniqid() . '.jpg';
                Storage::disk('public')->put("perfiles/{$nombreArchivo}", $imagen->toString());
            } else {
                // Fallback sin drivers de imagen: guardar el archivo tal cual.
                $ext = strtolower($archivo->getClientOriginalExtension() ?: 'jpg');
                $nombreArchivo = 'perfil_' . $user->id . '_' . uniqid() . '.' . $ext;
                Storage::disk('public')->putFileAs('perfiles', $archivo, $nombreArchivo);
            }

            $user->imagen = $nombreArchivo;
            $user->save();
        }

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    public function actualizarUsuario(Request $request, $id)
    {
        try {
            // 🔒 Verificar que el usuario autenticado pertenece al departamento de programador o administrador para editar DNI y email
            $authUser = auth()->user();
            $puedeEditarDniEmail = $authUser->departamentos()->whereIn('nombre', ['Programador', 'Administrador'])->exists();

            // ✅ Validar los datos con mensajes personalizados
            $validationRules = [
                'name' => 'required|string|max:50',
                'primer_apellido' => 'nullable|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'movil_personal' => 'nullable|string|max:255',
                'movil_empresa' => 'nullable|string|max:255',
                'numero_corto' => [
                    'nullable',
                    'digits:4', // exactamente 4 dígitos
                    'unique:users,numero_corto,' . $id,
                ],
                'empresa_id' => 'nullable|exists:empresas,id',
                'rol' => 'required|string|max:50',
                'categoria_id' => 'nullable|exists:categorias,id',
                'maquina_id' => 'nullable|exists:maquinas,id',
                'turno' => 'nullable|string|in:nocturno,diurno,mañana,flexible',
            ];

            // Solo programador o administrador puede editar email y dni
            if ($puedeEditarDniEmail) {
                $validationRules['email'] = 'required|email|max:255|unique:users,email,' . $id;
                $validationRules['dni'] = [
                    'nullable',
                    'string',
                    'size:9',
                    'regex:/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z])$/',
                    'unique:users,dni,' . $id,
                ];
            }

            $request->validate($validationRules, [
                'numero_corto.digits' => 'El número corporativo debe tener exactamente 4 cifras.',
                'numero_corto.unique' => 'Este número corporativo ya está asignado a otro usuario.',
            ]);

            // 🔎 Buscar el usuario
            $usuario = User::find($id);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }

            // 💡 Normalización
            $nombre = ucfirst(mb_strtolower($request->name));
            $apellido1 = $request->primer_apellido ? ucfirst(mb_strtolower($request->primer_apellido)) : null;
            $apellido2 = $request->segundo_apellido ? ucfirst(mb_strtolower($request->segundo_apellido)) : null;
            $movil_personal = $request->movil_personal ? str_replace(' ', '', $request->movil_personal) : null;
            $movil_empresa = $request->movil_empresa ? str_replace(' ', '', $request->movil_empresa) : null;
            $numero_corto = $request->numero_corto ? str_pad(preg_replace('/\D/', '', $request->numero_corto), 4, '0', STR_PAD_LEFT) : null;

            // Preparar datos de actualización
            $datosActualizar = [
                'name' => $nombre,
                'primer_apellido' => $apellido1,
                'segundo_apellido' => $apellido2,
                'movil_personal' => $movil_personal,
                'movil_empresa' => $movil_empresa,
                'numero_corto' => $numero_corto,
                'empresa_id' => $request->empresa_id,
                'rol' => $request->rol,
                'categoria_id' => $request->categoria_id,
                'maquina_id' => $request->maquina_id,
                'turno' => $request->turno,
                'updated_by' => auth()->id(),
            ];

            // Solo programador o administrador puede actualizar email y dni
            if ($puedeEditarDniEmail) {
                $datosActualizar['email'] = strtolower($request->email);
                $datosActualizar['dni'] = $request->dni ? strtoupper($request->dni) : null;
            }

            // ✅ Actualización
            $resultado = $usuario->update($datosActualizar);

            if (!$resultado) {
                return response()->json(['error' => 'No se pudo actualizar el usuario.'], 500);
            }

            // 🔄 Actualizar asignaciones_turno desde hoy hasta fin de año
            AsignacionTurno::where('user_id', $usuario->id)
                ->whereDate('fecha', '>=', Carbon::today())
                ->whereDate('fecha', '<=', Carbon::createFromDate(null, 12, 31))
                ->where('turno_id', '!=', 10)
                ->update(['maquina_id' => $usuario->maquina_id]);

            return response()->json(['success' => 'Usuario actualizado correctamente.']);
        } catch (ValidationException $e) {
            Log::error('Error de validación al actualizar usuario ID ' . $id, [
                'errores' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Excepción al actualizar usuario ID ' . $id, [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }

    public function generarTurnos(User $user)
    {
        // Obtener ID de la agrupacion de turno (plantilla)
        $agrupacionId = request()->input('agrupacion_turno_id');

        // Verificar que la agrupacion existe y esta activa
        $agrupacion = AgrupacionTurno::where('id', $agrupacionId)->where('activo', true)->first();
        if (!$agrupacion) {
            return redirect()->back()->with('error', 'Debe seleccionar una plantilla de turno valida.');
        }

        $obraId = request()->input('obra_id');

        // Cargar los dias de la agrupacion
        $diasConfig = $agrupacion->dias()->with('turno')->get()->keyBy('dia_semana');

        // Rango: desde mañana hasta fin de año
        $inicio = Carbon::now()->addDay()->startOfDay();
        $fin = Carbon::now()->endOfYear();

        // Festivos desde BD por rango
        $festivosArray = Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // Dias ya marcados como vacaciones
        $turnoVacacionesId = Turno::where('nombre', 'vacaciones')->value('id');
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where(function($q) use ($turnoVacacionesId) {
                $q->where('estado', 'vacaciones')
                  ->orWhere('turno_id', $turnoVacacionesId);
            })
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        $asignacionesCreadas = 0;

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr = $fecha->toDateString();
            $diaSemana = $fecha->dayOfWeek;

            // Saltar festivos y vacaciones
            if (in_array($fechaStr, $festivosArray, true) || in_array($fechaStr, $diasVacaciones, true)) {
                continue;
            }

            // Obtener configuracion del dia de la semana desde la plantilla
            $diaConfig = $diasConfig->get($diaSemana);

            // Si no hay turno configurado para este dia, no se trabaja
            if (!$diaConfig || !$diaConfig->turno_id) {
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            // No sobrescribir si ese dia se marco con turno 'festivo'
            if ($asignacion && optional($asignacion->turno)->nombre === 'festivo') {
                continue;
            }

            if ($asignacion) {
                $asignacion->update([
                    'turno_id' => $diaConfig->turno_id,
                    'obra_id' => $obraId,
                ]);
            } else {
                AsignacionTurno::create([
                    'user_id' => $user->id,
                    'fecha' => $fechaStr,
                    'turno_id' => $diaConfig->turno_id,
                    'obra_id' => $obraId,
                    'estado' => 'activo',
                ]);
            }
            $asignacionesCreadas++;
        }

        return redirect()->back()->with('success', "Plantilla '{$agrupacion->nombre}' asignada a {$user->name}. Se generaron {$asignacionesCreadas} turnos hasta fin de año.");
    }

    public function eventosTurnos(User $user)
    {
        $colores = $this->getColoresTurnosYEstado();
        $user->load('asignacionesTurnos.turno', 'asignacionesTurnos.obra');

        $eventos = collect();

        // 1. Estados (vacaciones, baja, etc.)
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->estado && strtolower($asig->estado) !== 'activo') {
                $nombre = strtolower($asig->estado);
                $color = $colores[$nombre] ?? ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'];

                // Formatear fecha como string YYYY-MM-DD para evitar problemas de timezone
                $fechaStr = Carbon::parse($asig->fecha)->format('Y-m-d');

                $eventos->push([
                    'id' => 'estado-' . $asig->id,
                    'title' => ucfirst($nombre),
                    'start' => $fechaStr,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada' => $asig->entrada,
                        'salida' => $asig->salida,
                        'es_turno' => false,
                        'estado' => $nombre,
                        'anio_vacacional' => $asig->anio_vacacional,
                        'obra_id' => $asig->obra_id,
                        'obra_nombre' => $asig->obra?->obra,
                    ],
                ]);
            }
        }

        // 2. Turnos - usar color de la tabla turnos
        foreach ($user->asignacionesTurnos as $asig) {
            if ($asig->turno) {
                // Usar el color del turno desde la BD, con fallback a azul
                $colorTurno = $asig->turno->color ?? '#3b82f6';
                $colorTexto = $asig->turno->color_texto ?? '#ffffff';

                // Generar color de borde más oscuro
                $borderColor = $this->darkenColor($colorTurno, 20);

                // Formatear fecha como string YYYY-MM-DD para evitar problemas de timezone
                $fechaStr = Carbon::parse($asig->fecha)->format('Y-m-d');

                // Mostrar horas del turno en lugar del nombre
                $horaInicio = $asig->turno->hora_inicio ? substr($asig->turno->hora_inicio, 0, 5) : null;
                $horaFin = $asig->turno->hora_fin ? substr($asig->turno->hora_fin, 0, 5) : null;
                $tituloTurno = ($horaInicio && $horaFin) ? "{$horaInicio}-{$horaFin}" : ucfirst($asig->turno->nombre);

                $eventos->push([
                    'id' => 'turno-' . $asig->id,
                    'title' => $tituloTurno,
                    'start' => $fechaStr,
                    'allDay' => true,
                    'backgroundColor' => $colorTurno,
                    'borderColor' => $borderColor,
                    'textColor' => $colorTexto,
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada' => $asig->entrada,
                        'salida' => $asig->salida,
                        'es_turno' => true,
                        'obra_id' => $asig->obra_id,
                        'obra_nombre' => $asig->obra?->obra,
                        'turno_nombre' => $asig->turno->nombre,
                    ],
                ]);
            }
        }

        // 3. Fichajes (entrada/salida con hora)
        $eventos = $eventos->merge($this->getEventosFichajes($user));

        // 4. Festivos
        $eventos = $eventos->merge(Festivo::eventosCalendario());

        // 5. Vacaciones pendientes/denegadas (incluyendo fines de semana y festivos)
        $vacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->whereIn('estado', ['pendiente', 'denegada'])
            ->get()
            ->flatMap(function ($solicitud) {
                $title = $solicitud->estado === 'pendiente' ? 'V. pendiente' : 'V. denegadas';
                $color = $solicitud->estado === 'pendiente' ? '#fcdde8' : '#000000';
                $textColor = $solicitud->estado === 'pendiente' ? 'black' : 'white';

                return collect(CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin)->toArray())
                    ->map(function ($fecha) use ($title, $color, $textColor, $solicitud) {
                        $fechaStr = $fecha->format('Y-m-d');
                        return [
                            'id' => 'vac-' . $solicitud->id . '-' . $fechaStr,
                            'title' => $title,
                            'start' => $fechaStr,
                            'allDay' => true,
                            'backgroundColor' => $color,
                            'borderColor' => $color,
                            'textColor' => $textColor,
                            'extendedProps' => [
                                'solicitud_id' => $solicitud->id,
                                'estado' => $solicitud->estado,
                                'fecha_inicio' => $solicitud->fecha_inicio,
                                'fecha_fin' => $solicitud->fecha_fin,
                                'es_solicitud_vacaciones' => true,
                            ],
                        ];
                    });
            })->values();

        $eventos = $eventos->merge($vacaciones);

        return response()->json($eventos);
    }

    /**
     * Obtiene eventos optimizados para un rango de fechas (precarga inicial)
     */
    private function getEventosRangoOptimizado(User $user, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $eventos = [];
        $colores = $this->getColoresTurnosYEstado();

        // Consulta optimizada: solo asignaciones del rango con relaciones necesarias
        $asignaciones = AsignacionTurno::with(['turno', 'obra'])
            ->where('user_id', $user->id)
            ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->get();

        foreach ($asignaciones as $asig) {
            $fechaStr = Carbon::parse($asig->fecha)->format('Y-m-d');

            // Estados (vacaciones, baja, etc.)
            if ($asig->estado && strtolower($asig->estado) !== 'activo') {
                $nombre = strtolower($asig->estado);
                $color = $colores[$nombre] ?? ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#ffffff'];

                $eventos[] = [
                    'id' => 'estado-' . $asig->id,
                    'title' => ucfirst($nombre),
                    'start' => $fechaStr,
                    'allDay' => true,
                    'backgroundColor' => $color['bg'],
                    'borderColor' => $color['border'],
                    'textColor' => $color['text'],
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada' => $asig->entrada,
                        'salida' => $asig->salida,
                        'es_turno' => false,
                        'estado' => $nombre,
                        'anio_vacacional' => $asig->anio_vacacional,
                        'obra_id' => $asig->obra_id,
                        'obra_nombre' => $asig->obra?->obra,
                    ],
                ];
            }

            // Turnos
            if ($asig->turno) {
                $colorTurno = $asig->turno->color ?? '#3b82f6';
                $colorTexto = $asig->turno->color_texto ?? '#ffffff';
                $borderColor = $this->darkenColor($colorTurno, 20);

                $horaInicio = $asig->turno->hora_inicio ? substr($asig->turno->hora_inicio, 0, 5) : null;
                $horaFin = $asig->turno->hora_fin ? substr($asig->turno->hora_fin, 0, 5) : null;
                $tituloTurno = ($horaInicio && $horaFin) ? "{$horaInicio}-{$horaFin}" : ucfirst($asig->turno->nombre);

                $eventos[] = [
                    'id' => 'turno-' . $asig->id,
                    'title' => $tituloTurno,
                    'start' => $fechaStr,
                    'allDay' => true,
                    'backgroundColor' => $colorTurno,
                    'borderColor' => $borderColor,
                    'textColor' => $colorTexto,
                    'extendedProps' => [
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada' => $asig->entrada,
                        'salida' => $asig->salida,
                        'es_turno' => true,
                        'obra_id' => $asig->obra_id,
                        'obra_nombre' => $asig->obra?->obra,
                        'turno_nombre' => $asig->turno->nombre,
                    ],
                ];
            }

            // Fichajes
            $formatHora = fn($hora) => $hora ? substr(trim($hora), 0, 5) : null;
            $entrada1 = $formatHora($asig->entrada);
            $salida1 = $formatHora($asig->salida);
            $entrada2 = $formatHora($asig->entrada2 ?? null);
            $salida2 = $formatHora($asig->salida2 ?? null);

            if ($entrada1 || $salida1 || $entrada2 || $salida2) {
                $eventos[] = [
                    'id' => "fichajes-{$asig->id}",
                    'start' => $fechaStr,
                    'allDay' => true,
                    'display' => 'block',
                    'order' => 10,
                    'classNames' => ['fichaje-evento'],
                    'backgroundColor' => 'transparent',
                    'borderColor' => 'transparent',
                    'extendedProps' => [
                        'tipo' => 'fichajes',
                        'asignacion_id' => $asig->id,
                        'fecha' => $fechaStr,
                        'entrada1' => $entrada1,
                        'salida1' => $salida1,
                        'entrada2' => $entrada2,
                        'salida2' => $salida2,
                        'tieneSegundaJornada' => ($entrada2 || $salida2) ? true : false,
                    ],
                ];
            }
        }

        // Festivos del rango
        $festivos = Festivo::whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->get()
            ->map(fn($f) => [
                'id' => 'festivo-' . $f->id,
                'title' => $f->nombre ?? 'Festivo',
                'start' => Carbon::parse($f->fecha)->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => '#ff0000',
                'borderColor' => '#b91c1c',
                'textColor' => 'white',
                'extendedProps' => ['es_festivo' => true],
            ])
            ->toArray();

        $eventos = array_merge($eventos, $festivos);

        // Vacaciones pendientes/denegadas del rango
        $vacaciones = VacacionesSolicitud::where('user_id', $user->id)
            ->whereIn('estado', ['pendiente', 'denegada'])
            ->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                    ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                    ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                        $q2->where('fecha_inicio', '<=', $fechaInicio)
                            ->where('fecha_fin', '>=', $fechaFin);
                    });
            })
            ->get();

        foreach ($vacaciones as $solicitud) {
            $title = $solicitud->estado === 'pendiente' ? 'V. pendiente' : 'V. denegadas';
            $color = $solicitud->estado === 'pendiente' ? '#fcdde8' : '#000000';
            $textColor = $solicitud->estado === 'pendiente' ? 'black' : 'white';

            foreach (CarbonPeriod::create($solicitud->fecha_inicio, $solicitud->fecha_fin) as $fecha) {
                if ($fecha->between($fechaInicio, $fechaFin)) {
                    $fechaStr = $fecha->format('Y-m-d');
                    $eventos[] = [
                        'id' => 'vac-' . $solicitud->id . '-' . $fechaStr,
                        'title' => $title,
                        'start' => $fechaStr,
                        'allDay' => true,
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'textColor' => $textColor,
                        'extendedProps' => [
                            'solicitud_id' => $solicitud->id,
                            'estado' => $solicitud->estado,
                            'fecha_inicio' => $solicitud->fecha_inicio,
                            'fecha_fin' => $solicitud->fecha_fin,
                            'es_solicitud_vacaciones' => true,
                        ],
                    ];
                }
            }
        }

        return $eventos;
    }

    public function destroy(Request $request, $id)
    {
        try {

            // Buscar el usuario a eliminar
            $user = User::findOrFail($id);

            // Validar la contraseña del administrador
            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors(['userDeletion.password' => 'La contraseña proporcionada es incorrecta.']);
            }

            // Eliminar usuario
            $user->delete();

            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

    public function cerrarSesionesDeUsuario(User $user)
    {

        $cerradas = Session::where('user_id', $user->id)->delete();

        $user->forceFill(['remember_token' => null])->save();

        return back()->with('success', "🛑 Se han cerrado $cerradas sesión(es) activas del usuario {$user->nombre_completo}.");
    }

    public function despedirUsuario(Request $request, User $user)
    {
        DB::transaction(function () use ($user) {

            /* —————————  SEGURIDAD ————————— */
            // Desactivar la cuenta
            $user->update([
                'estado' => 'despedido',      // o tu campo equivalente
                'fecha_baja' => now(),
            ]);

            // Cerrar todas las sesiones guardadas (si usas una tabla custom)
            Session::where('user_id', $user->id)->delete();
            $user->forceFill(['remember_token' => null])->save();

            // Revocar tokens API (Sanctum/Passport)
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            /* —————————  PLANIFICACIÓN ————————— */
            // Borrar o reasignar turnos futuros
            AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', '>=', today())
                ->delete();
        });

        return redirect()->route('users.index')->with('success', '👋 Usuario despedido correctamente.');
    }

    private function getResumenAsistencia(User $user): array
    {
        $inicioAño = Carbon::now()->startOfYear();

        $conteos = AsignacionTurno::select('estado', DB::raw('count(*) as total'))
            ->where('user_id', $user->id)
            ->where('fecha', '>=', $inicioAño)
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // Desglose de vacaciones por año (anio_vacacional-aware) para actualización in-line
        $anioActual = Carbon::now()->year;
        $anioAnterior = $anioActual - 1;
        $disfrutadasActual = $user->vacacionesImputadasEnAnio($anioActual);
        $disfrutadasAnterior = $user->vacacionesImputadasEnAnio($anioAnterior);
        $topeActual = $user->vacaciones_correspondientes ?? 30;
        $topeAnterior = $user->topeVacacionesEnAnio($anioAnterior);

        return [
            'diasVacaciones' => $conteos['vacaciones'] ?? 0,
            'faltasInjustificadas' => $conteos['injustificada'] ?? 0,
            'faltasJustificadas' => $conteos['justificada'] ?? 0,
            'diasBaja' => $conteos['baja'] ?? 0,
            'vacaciones' => [
                'actual' => [
                    'anio' => $anioActual,
                    'totales' => $topeActual,
                    'disfrutadas' => $disfrutadasActual,
                    'disponibles' => max(0, $topeActual - $disfrutadasActual),
                ],
                'anterior' => [
                    'anio' => $anioAnterior,
                    'totales' => $topeAnterior,
                    'disfrutadas' => $disfrutadasAnterior,
                    'pendientes' => max(0, $topeAnterior - $disfrutadasAnterior),
                ],
            ],
        ];
    }

    public function resumenAsistencia(User $user)
    {
        return response()->json($this->getResumenAsistencia($user));
    }

    public function getOperarios(OperarioService $operarioService)
    {
        $operarios = $operarioService->getTodosOperarios();

        return response()->json($operarios);
    }

    /**
     * Obtiene operarios agrupados por empresa para el diálogo de generar turnos.
     * Respuesta dinámica: se adapta automáticamente cuando se añaden nuevas empresas.
     */
    public function getOperariosAgrupados(Request $request, OperarioService $operarioService)
    {
        $fecha = $request->input('fecha');
        $maquinaId = $request->input('maquina_id');

        // El servicio devuelve todos los datos necesarios en una sola llamada optimizada
        $datos = $operarioService->getDatosParaGenerarTurnos($fecha, (int) $maquinaId);

        return response()->json($datos);
    }

    public function generarTurnosCalendario(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'maquina_id' => 'required|exists:maquinas,id',
            'fecha_inicio' => 'required|date',
            'alcance' => 'required|in:un_dia,dos_semanas,resto_año',
            'turno_inicio' => 'nullable|in:mañana,tarde',
            'turno_detectado' => 'nullable|in:mañana,tarde,noche',
        ]);

        $user = User::with(['categoria'])->findOrFail($validated['user_id']);

        // Obtener la máquina y su obra_id
        $maquina = Maquina::findOrFail($validated['maquina_id']);
        $obraId = $maquina->obra_id;

        // Obtener colores basados en obra_id (mismo sistema que actualizarPuesto)
        $coloresEventos = [
            1 => ['bg' => '#93C5FD', 'border' => '#60A5FA'], // azul claro
            2 => ['bg' => '#6EE7B7', 'border' => '#34D399'], // verde claro
            3 => ['bg' => '#FDBA74', 'border' => '#F59E0B'], // naranja claro
        ];
        $colorEvento = $coloresEventos[$obraId] ?? ['bg' => '#3b82f6', 'border' => '#3b82f6'];

        // IDs de turnos
        $turnoMañanaId = Turno::where('nombre', 'mañana')->value('id');
        $turnoTardeId = Turno::where('nombre', 'tarde')->value('id');
        $turnoNocheId = Turno::where('nombre', 'noche')->value('id');

        // Determinar rango de fechas
        $inicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
        if ($validated['alcance'] === 'un_dia') {
            $fin = $inicio->copy();
        } elseif ($validated['alcance'] === 'dos_semanas') {
            // Calcular el viernes de la semana siguiente
            // Primero obtenemos el viernes de esta semana, luego sumamos una semana
            $fin = $inicio->copy()->modify('friday this week')->addWeek();
        } else {
            $fin = Carbon::parse($validated['fecha_inicio'])->endOfYear();
        }

        // Obtener festivos del rango
        $festivosArray = Festivo::whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // Obtener días de vacaciones
        $diasVacaciones = AsignacionTurno::where('user_id', $user->id)
            ->where('estado', 'vacaciones')
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())
            ->all();

        // Determinar turno inicial
        // Si viene turno_detectado (desde el clic en el calendario), usarlo directamente
        if (!empty($validated['turno_detectado'])) {
            $turnoDetectado = $validated['turno_detectado'];
            $turnoAsignado = match ($turnoDetectado) {
                'mañana' => $turnoMañanaId,
                'tarde' => $turnoTardeId,
                'noche' => $turnoNocheId,
                default => $turnoMañanaId,
            };
        } elseif ($user->turno == 'diurno') {
            $turnoInicial = $validated['turno_inicio'] ?? 'mañana';
            if (!in_array($turnoInicial, ['mañana', 'tarde'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe seleccionar un turno válido para comenzar (mañana o tarde).'
                ], 400);
            }
            $turnoAsignado = $turnoInicial === 'mañana' ? $turnoMañanaId : $turnoTardeId;
        } elseif ($user->turno == 'nocturno') {
            $turnoAsignado = $turnoNocheId;
        } elseif ($user->turno == 'mañana') {
            $turnoAsignado = $turnoMañanaId;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene un turno asignado.'
            ], 400);
        }

        $turnosCreados = 0;
        $eventosCreados = [];

        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $fechaStr = $fecha->toDateString();
            $esViernes = $fecha->dayOfWeek === Carbon::FRIDAY;

            // Saltar sábados, domingos, festivos y vacaciones
            $esNoLaborable =
                in_array($fecha->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                || in_array($fechaStr, $festivosArray, true)
                || in_array($fechaStr, $diasVacaciones, true);

            if ($esNoLaborable) {
                // Mantener la rotación de viernes aunque se salte el día
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            $asignacion = AsignacionTurno::where('user_id', $user->id)
                ->whereDate('fecha', $fechaStr)
                ->first();

            // No sobrescribir si ese día se marcó con turno 'festivo'
            if ($asignacion && optional($asignacion->turno)->nombre === 'festivo') {
                if ($user->turno === 'diurno' && $esViernes) {
                    $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
                }
                continue;
            }

            if ($asignacion) {
                $asignacion->update([
                    'turno_id' => $turnoAsignado,
                    'maquina_id' => $validated['maquina_id'],
                    'obra_id' => $obraId,
                ]);
                $asignacionActualizada = $asignacion->fresh(['turno', 'obra']);
            } else {
                $asignacionActualizada = AsignacionTurno::create([
                    'user_id' => $user->id,
                    'fecha' => $fechaStr,
                    'turno_id' => $turnoAsignado,
                    'maquina_id' => $validated['maquina_id'],
                    'obra_id' => $obraId,
                    'estado' => 'activo',
                ]);
                $asignacionActualizada->load(['turno', 'obra']);
            }

            // Mapeo visual usando TurnoMapper
            $turnoModel = $asignacionActualizada->turno;
            $slot = TurnoMapper::getSlotParaTurnoModel($turnoModel, $fechaStr);

            // Agregar evento creado a la lista (estructura normalizada)
            $eventosCreados[] = [
                'id' => 'turno-' . $asignacionActualizada->id,
                'title' => $user->name . ' ' . ($user->primer_apellido ?? ''),
                'start' => $slot['start'],
                'end' => $slot['end'],
                'resourceId' => $validated['maquina_id'],
                'backgroundColor' => $colorEvento['bg'],
                'borderColor' => $colorEvento['border'],
                'textColor' => '#000000',
                'extendedProps' => [
                    'user_id' => $user->id,
                    'categoria_nombre' => $user->categoria->nombre ?? null,
                    'turno' => $turnoModel->nombre ?? null,
                    'entrada' => $asignacionActualizada->entrada,
                    'salida' => $asignacionActualizada->salida,
                    'foto' => $user->ruta_imagen,
                    'es_festivo' => false,
                ],
            ];

            $turnosCreados++;

            // Rotación de viernes (para diurno)
            if ($user->turno === 'diurno' && $esViernes) {
                $turnoAsignado = ($turnoAsignado === $turnoMañanaId) ? $turnoTardeId : $turnoMañanaId;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Turnos generados correctamente',
            'turnos_creados' => $turnosCreados,
            'eventos' => $eventosCreados,
        ]);
    }
    /**
     * Obtener datos de vacaciones de un usuario para cálculo frontend
     * Incluye desglose por año para el período de gracia (1 enero - 31 marzo)
     * @param User $user
     * @param Request $request - Puede incluir ?fecha=YYYY-MM-DD para calcular relativo a esa fecha
     */
    public function getVacationData(User $user, Request $request)
    {
        // Solo oficina o el propio usuario
        if (auth()->user()->rol !== 'oficina' && auth()->id() !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Cargar relación incorporacion para obtener fecha_incorporacion_efectiva
        $user->load('incorporacion');

        // Usar la fecha clickeada si se proporciona, sino la fecha actual
        $fechaReferencia = $request->input('fecha') ? Carbon::parse($request->input('fecha')) : Carbon::now();
        $clickYear = (int) $fechaReferencia->format('Y');
        $previousYear = $clickYear - 1;

        // === CON VACACIONES FUTURAS (todas las del año) ===

        // Días de vacaciones usados en total (SIN LÍMITE de fecha para contar las futuras también)
        $diasAsignadosTotal = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->count();

        // Días usados del año anterior al clickado (todas las vacaciones del año anterior)
        $diasAsignadosAnterior = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $previousYear)
            ->count();

        // Días usados del año clickado (TODAS las vacaciones del año, incluyendo futuras)
        $diasAsignadosActual = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->count();

        // Días usados durante el período de gracia del año clickado (1 ene - 31 mar)
        // TODAS las del período, incluyendo futuras si las hay
        $diasUsadosPeriodoGracia = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '<=', 3) // enero, febrero, marzo
            ->count();

        // Días usados después del período de gracia (1 abril en adelante)
        $diasUsadosPostGracia = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '>', 3) // abril en adelante
            ->count();

        // === SIN VACACIONES FUTURAS (solo hasta la fecha clickeada) ===

        // Días usados hasta la fecha clickeada
        $diasUsadosHastaFecha = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereDate('fecha', '<=', $fechaReferencia->toDateString())
            ->count();

        // Días usados del periodo de gracia hasta la fecha clickeada
        $diasUsadosPeriodoGraciaHastaFecha = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '<=', 3)
            ->whereDate('fecha', '<=', $fechaReferencia->toDateString())
            ->count();

        // Días usados post-gracia hasta la fecha clickeada
        $diasUsadosPostGraciaHastaFecha = $user->asignacionesTurnos()
            ->where('estado', 'vacaciones')
            ->whereYear('fecha', $clickYear)
            ->whereMonth('fecha', '>', 3)
            ->whereDate('fecha', '<=', $fechaReferencia->toDateString())
            ->count();

        return response()->json([
            'fecha_incorporacion' => $user->fecha_incorporacion_efectiva ? $user->fecha_incorporacion_efectiva->format('Y-m-d') : null,
            'dias_asignados' => $diasAsignadosTotal,
            'dias_asignados_anterior' => $diasAsignadosAnterior,
            'dias_asignados_actual' => $diasAsignadosActual,
            'dias_usados_periodo_gracia' => $diasUsadosPeriodoGracia,
            'dias_usados_post_gracia' => $diasUsadosPostGracia,
            // Sin vacaciones futuras
            'dias_usados_hasta_fecha' => $diasUsadosHastaFecha,
            'dias_usados_periodo_gracia_hasta_fecha' => $diasUsadosPeriodoGraciaHastaFecha,
            'dias_usados_post_gracia_hasta_fecha' => $diasUsadosPostGraciaHastaFecha,
            'year_anterior' => $previousYear,
            'year_actual' => $clickYear,
            'fecha_referencia' => $fechaReferencia->toDateString(),
        ]);
    }

    /**
     * Obtener fichajes de un usuario para un rango de fechas
     * Usado por el sistema de solicitud de revision de fichajes
     */
    public function getFichajesRango(Request $request, $userId)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        // Solo el propio usuario, oficina, programador o responsable del departamento pueden ver esto
        $user = User::findOrFail($userId);
        $tienePermiso = Auth::id() == $userId
            || in_array(Auth::user()->rol, ['oficina', 'programador'])
            || $user->departamentos()->where('responsable_id', Auth::id())->exists();

        if (!$tienePermiso) {
            return response()->json(['error' => 'No tienes permisos.'], 403);
        }

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        $fichajes = [];

        foreach ($periodo as $fecha) {
            // Saltar fines de semana
            if ($fecha->isWeekend()) {
                continue;
            }

            $fechaStr = $fecha->toDateString();

            $asignacion = AsignacionTurno::with('turno')
                ->where('user_id', $userId)
                ->whereDate('fecha', $fechaStr)
                ->first();

            $fichajes[] = [
                'fecha' => $fechaStr,
                'turno' => $asignacion?->turno?->nombre ?? null,
                'entrada' => $asignacion?->entrada ? substr($asignacion->entrada, 0, 5) : null,
                'salida' => $asignacion?->salida ? substr($asignacion->salida, 0, 5) : null,
                'entrada2' => $asignacion?->entrada2 ? substr($asignacion->entrada2, 0, 5) : null,
                'salida2' => $asignacion?->salida2 ? substr($asignacion->salida2, 0, 5) : null,
                'completo' => $asignacion && $asignacion->entrada && $asignacion->salida,
            ];
        }

        return response()->json([
            'user_id' => $userId,
            'user_nombre' => $user->nombre_completo,
            'fichajes' => $fichajes,
        ]);
    }
}
