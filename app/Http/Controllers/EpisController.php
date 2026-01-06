<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Epi;
use App\Models\EpiCompra;
use App\Models\EpiCompraItem;
use App\Models\EpiUsuario;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EpisController extends Controller
{
    private function mapAsignacion(EpiUsuario $asignacion): array
    {
        $fechaAsignacion = $asignacion->entregado_en ?? $asignacion->created_at;
        $fechaDevolucion = $asignacion->devuelto_en;

        return [
            'id' => $asignacion->id,
            'user_id' => $asignacion->user_id,
            'epi_id' => $asignacion->epi_id,
            'cantidad' => (int) $asignacion->cantidad,
            'fecha_asignacion' => optional($fechaAsignacion)->toIso8601String(),
            'entregado_en' => optional($asignacion->entregado_en)->toIso8601String(),
            'devuelto_en' => optional($fechaDevolucion)->toIso8601String(),
            'notas' => $asignacion->notas,
            'epi' => $asignacion->epi ? [
                'id' => $asignacion->epi->id,
                'codigo' => $asignacion->epi->codigo,
                'nombre' => $asignacion->epi->nombre,
                'categoria' => $asignacion->epi->categoria,
                'descripcion' => $asignacion->epi->descripcion,
                'imagen_path' => $asignacion->epi->imagen_path,
                'imagen_url' => $asignacion->epi->imagen_path ? route('epis.imagen', $asignacion->epi) : null,
                'activo' => (bool) $asignacion->epi->activo,
            ] : null,
        ];
    }

    private function mapCompra(EpiCompra $compra, bool $withItems = false): array
    {
        $items = $withItems
            ? $compra->items->map(function (EpiCompraItem $item) {
                return [
                    'id' => $item->id,
                    'compra_id' => $item->compra_id,
                    'epi_id' => $item->epi_id,
                    'cantidad' => (int) $item->cantidad,
                    'precio_unitario' => $item->precio_unitario !== null ? (float) $item->precio_unitario : null,
                    'epi' => $item->epi ? [
                        'id' => $item->epi->id,
                        'codigo' => $item->epi->codigo,
                        'nombre' => $item->epi->nombre,
                        'categoria' => $item->epi->categoria,
                        'imagen_url' => $item->epi->imagen_path ? route('epis.imagen', $item->epi) : null,
                        'activo' => (bool) $item->epi->activo,
                    ] : null,
                ];
            })->values()
            : null;

        $productos = $withItems ? $compra->items->count() : null;
        $unidades = $withItems ? (int) $compra->items->sum('cantidad') : null;
        $total = $withItems
            ? (float) $compra->items->reduce(function ($carry, EpiCompraItem $item) {
                $price = $item->precio_unitario ?? 0;
                return $carry + ((float) $price * (int) $item->cantidad);
            }, 0.0)
            : null;

        return [
            'id' => $compra->id,
            'estado' => $compra->estado,
            'comprada_en' => optional($compra->comprada_en)->toIso8601String(),
            'created_at' => optional($compra->created_at)->toIso8601String(),
            'updated_at' => optional($compra->updated_at)->toIso8601String(),
            'ticket_url' => $compra->ticket_path ? route('epis.compras.ticket', $compra) : null,
            'productos' => $productos,
            'unidades' => $unidades,
            'total' => $total,
            'items' => $items,
        ];
    }

    public function index(Request $request)
    {
        $categorias = Categoria::select('id', 'nombre')->orderBy('nombre')->get();
        $empresas = Empresa::select('id', 'nombre')->orderBy('nombre')->get();

        return view('epis.index', [
            'categorias' => $categorias,
            'empresas' => $empresas,
        ]);
    }

    public function apiUsers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $epi = trim((string) $request->query('epi', ''));
        $empresaId = $request->query('empresa_id');
        $categoriaId = $request->query('categoria_id');
        $empresaId = ($empresaId === null || $empresaId === '') ? null : (int) $empresaId;
        $categoriaId = ($categoriaId === null || $categoriaId === '') ? null : (int) $categoriaId;
        $epiProvided = $epi !== '';
        $epiTokens = $epi !== '' ? preg_split('/\s+/', $epi, -1, PREG_SPLIT_NO_EMPTY) : [];

        $users = User::query()
            ->when($empresaId !== null, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($categoriaId !== null, function ($query) use ($categoriaId) {
                $query->where('categoria_id', $categoriaId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('primer_apellido', 'like', "%{$q}%")
                        ->orWhere('segundo_apellido', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('dni', 'like', "%{$q}%")
                        ->orWhere('movil_personal', 'like', "%{$q}%");
                });
            })
            ->when($epi !== '', function ($query) use ($epiTokens) {
                $query->withExists([
                    'episAsignaciones as epi_match' => function ($asignaciones) use ($epiTokens) {
                        $asignaciones
                            ->whereNull('devuelto_en')
                            ->whereHas('epi', function ($epis) use ($epiTokens) {
                                foreach ($epiTokens as $token) {
                                    $epis->where(function ($sub) use ($token) {
                                        $sub->where('nombre', 'like', "%{$token}%")
                                            ->orWhere('codigo', 'like', "%{$token}%")
                                            ->orWhere('categoria', 'like', "%{$token}%");
                                    });
                                }
                            });
                    },
                ]);
            })
            ->with(['empresa:id,nombre', 'categoria:id,nombre'])
            ->withSum(
                [
                    'episAsignaciones as epis_en_posesion' => function ($query) {
                        $query->whereNull('devuelto_en');
                    }
                ],
                'cantidad'
            )
            ->orderByDesc('epis_en_posesion')
            ->orderBy('primer_apellido')
            ->orderBy('segundo_apellido')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($epiProvided) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'primer_apellido' => $user->primer_apellido,
                    'segundo_apellido' => $user->segundo_apellido,
                    'nombre_completo' => $user->nombre_completo,
                    'dni' => $user->dni,
                    'email' => $user->email,
                    'movil_personal' => $user->movil_personal,
                    'empresa_id' => $user->empresa_id,
                    'categoria_id' => $user->categoria_id,
                    'empresa' => $user->empresa ? [
                        'id' => $user->empresa->id,
                        'nombre' => $user->empresa->nombre,
                    ] : null,
                    'categoria' => $user->categoria ? [
                        'id' => $user->categoria->id,
                        'nombre' => $user->categoria->nombre,
                    ] : null,
                    'ruta_imagen' => $user->ruta_imagen,
                    'epis_en_posesion' => (int) ($user->epis_en_posesion ?? 0),
                    'tiene_epis' => ((int) ($user->epis_en_posesion ?? 0)) > 0,
                    'epi_match' => $epiProvided ? (bool) ($user->epi_match ?? false) : true,
                ];
            })
            ->values();

        $topUser = $users->firstWhere('tiene_epis', true);

        return response()->json([
            'users' => $users,
            'stats' => [
                'usuarios_con_epis' => $users->where('tiene_epis', true)->count(),
                'top' => $topUser
                    ? ['user' => $topUser, 'cantidad' => (int) ($topUser['epis_en_posesion'] ?? 0)]
                    : null,
            ],
        ]);
    }

    public function apiEpis()
    {
        $epis = Epi::query()
            ->orderBy('nombre')
            ->get()
            ->map(function (Epi $epi) {
                return [
                    'id' => $epi->id,
                    'codigo' => $epi->codigo,
                    'nombre' => $epi->nombre,
                    'categoria' => $epi->categoria,
                    'descripcion' => $epi->descripcion,
                    'imagen_path' => $epi->imagen_path,
                    'imagen_url' => $epi->imagen_path ? route('epis.imagen', $epi) : null,
                    'activo' => (bool) $epi->activo,
                ];
            })
            ->values();

        return response()->json(['epis' => $epis]);
    }

    public function apiUserAsignaciones(User $user)
    {
        $all = EpiUsuario::query()
            ->where('user_id', $user->id)
            ->with('epi')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $mappedAll = $all->map(fn(EpiUsuario $asignacion) => $this->mapAsignacion($asignacion))->values();

        $enPosesion = $mappedAll->filter(fn($a) => empty($a['devuelto_en']))->values();
        $historial = $mappedAll->filter(fn($a) => !empty($a['devuelto_en']))->values();
        $recent = $mappedAll
            ->sortByDesc(fn($a) => $a['fecha_asignacion'] ?? '')
            ->take(10)
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'dni' => $user->dni,
                'email' => $user->email,
                'movil_personal' => $user->movil_personal,
                'ruta_imagen' => $user->ruta_imagen,
            ],
            'asignaciones' => $mappedAll,
            'en_posesion' => $enPosesion,
            'historial' => $historial,
            'recent' => $recent,
            'total_en_posesion' => (int) $enPosesion->sum('cantidad'),
        ]);
    }

    public function apiUserMovimientos(User $user, Request $request)
    {
        $per = (int) $request->query('per', 10);
        $per = max(1, min(50, $per));

        $page = max(1, (int) $request->query('page', 1));

        $paginator = EpiUsuario::query()
            ->where('user_id', $user->id)
            ->with('epi')
            ->orderByDesc(DB::raw('COALESCE(entregado_en, created_at)'))
            ->paginate($per, ['*'], 'page', $page);

        $items = $paginator->getCollection()
            ->map(fn(EpiUsuario $asignacion) => $this->mapAsignacion($asignacion))
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'dni' => $user->dni,
                'email' => $user->email,
                'movil_personal' => $user->movil_personal,
                'ruta_imagen' => $user->ruta_imagen,
            ],
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function apiCompras(Request $request)
    {
        $date = $request->query('date'); // YYYY-MM-DD
        $epiId = $request->integer('epi_id');

        $compras = EpiCompra::query()
            ->with(['items.epi'])
            ->when($date, function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            })
            ->when($epiId, function ($query) use ($epiId) {
                $query->whereHas('items', function ($q) use ($epiId) {
                    $q->where('epi_id', $epiId);
                });
            })
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn(EpiCompra $c) => $this->mapCompra($c, true))
            ->values();

        return response()->json(['compras' => $compras]);
    }

    public function apiCompra(EpiCompra $compra)
    {
        $compra->load(['items.epi']);
        return response()->json(['compra' => $this->mapCompra($compra, true)]);
    }

    public function apiCrearCompra(Request $request)
    {
        $data = $request->validate([
            'estado' => ['nullable', 'string', 'in:pendiente,comprada'],
            'items' => ['required'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $items = $data['items'];
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if (!is_array($items) || count($items) < 1) {
            return response()->json(['ok' => false, 'message' => 'Items inválidos.'], 422);
        }

        $validator = validator(['items' => $items], [
            'items' => ['required', 'array', 'min:1'],
            'items.*.epi_id' => ['required', 'integer', 'exists:epis,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ]);
        $validator->validate();

        return DB::transaction(function () use ($request, $data) {
            $compra = EpiCompra::create([
                'user_id' => auth()->id(),
                'estado' => $data['estado'] ?? 'pendiente',
                'comprada_en' => ($data['estado'] ?? 'pendiente') === 'comprada' ? now() : null,
            ]);

            $items = $data['items'];
            if (is_string($items)) {
                $items = json_decode($items, true);
            }
            foreach ($items as $item) {
                EpiCompraItem::create([
                    'compra_id' => $compra->id,
                    'epi_id' => $item['epi_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => array_key_exists('precio_unitario', $item) ? $item['precio_unitario'] : null,
                ]);
            }

            if ($request->hasFile('ticket')) {
                $compra->ticket_path = $request->file('ticket')->store('epis/compras', 'public');
                $compra->save();
            }

            $compra->load(['items.epi']);

            return response()->json(['ok' => true, 'compra' => $this->mapCompra($compra, true)]);
        });
    }

    public function apiActualizarCompra(EpiCompra $compra, Request $request)
    {
        $data = $request->validate([
            'estado' => ['nullable', 'string', 'in:pendiente,comprada'],
            'items' => ['nullable'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        return DB::transaction(function () use ($request, $compra, $data) {
            if (isset($data['estado'])) {
                $compra->estado = $data['estado'];
                $compra->comprada_en = $data['estado'] === 'comprada' ? ($compra->comprada_en ?? now()) : null;
            }

            if (isset($data['items'])) {
                $items = $data['items'];
                if (is_string($items)) {
                    $items = json_decode($items, true);
                }
                if (!is_array($items)) {
                    return response()->json(['ok' => false, 'message' => 'Items inválidos.'], 422);
                }

                $validator = validator(['items' => $items], [
                    'items' => ['required', 'array'],
                    'items.*.epi_id' => ['required', 'integer', 'exists:epis,id'],
                    'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
                    'items.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
                ]);
                $validator->validate();

                $incoming = collect($items)->keyBy('epi_id');

                $existing = $compra->items()->get()->keyBy('epi_id');

                foreach ($incoming as $epiId => $payload) {
                    $existingItem = $existing->get($epiId);
                    if ($existingItem) {
                        $existingItem->cantidad = $payload['cantidad'];
                        $existingItem->precio_unitario = array_key_exists('precio_unitario', $payload) ? $payload['precio_unitario'] : null;
                        $existingItem->save();
                    } else {
                        EpiCompraItem::create([
                            'compra_id' => $compra->id,
                            'epi_id' => $epiId,
                            'cantidad' => $payload['cantidad'],
                            'precio_unitario' => array_key_exists('precio_unitario', $payload) ? $payload['precio_unitario'] : null,
                        ]);
                    }
                }

                $toDelete = $existing->keys()->diff($incoming->keys());
                if ($toDelete->isNotEmpty()) {
                    $compra->items()->whereIn('epi_id', $toDelete->all())->delete();
                }
            }

            if ($request->hasFile('ticket')) {
                if ($compra->ticket_path) {
                    Storage::disk('public')->delete($compra->ticket_path);
                }
                $compra->ticket_path = $request->file('ticket')->store('epis/compras', 'public');
            }

            $compra->save();
            $compra->load(['items.epi']);

            return response()->json(['ok' => true, 'compra' => $this->mapCompra($compra, true)]);
        });
    }

    public function ticketCompra(EpiCompra $compra)
    {
        if (!$compra->ticket_path) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($compra->ticket_path)) {
            abort(404);
        }

        return response()->file($disk->path($compra->ticket_path));
    }

    public function imagen(Epi $epi)
    {
        if (!$epi->imagen_path) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($epi->imagen_path)) {
            abort(404);
        }

        return response()->file($disk->path($epi->imagen_path));
    }

    public function storeEpi(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255', 'unique:epis,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ]);

        $epi = new Epi();
        $epi->fill($data);
        $epi->activo = (bool) ($data['activo'] ?? true);

        if ($request->hasFile('imagen')) {
            $epi->imagen_path = $request->file('imagen')->store('epis', 'public');
        }

        $epi->save();

        return response()->json([
            'ok' => true,
            'epi' => [
                'id' => $epi->id,
                'codigo' => $epi->codigo,
                'nombre' => $epi->nombre,
                'categoria' => $epi->categoria,
                'descripcion' => $epi->descripcion,
                'imagen_path' => $epi->imagen_path,
                'imagen_url' => $epi->imagen_path ? route('epis.imagen', $epi) : null,
                'activo' => (bool) $epi->activo,
            ],
        ]);
    }

    public function updateEpi(Request $request, Epi $epi)
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255', Rule::unique('epis', 'codigo')->ignore($epi->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ]);

        $epi->fill($data);
        $epi->activo = (bool) ($data['activo'] ?? false);

        if ($request->hasFile('imagen')) {
            if ($epi->imagen_path) {
                Storage::disk('public')->delete($epi->imagen_path);
            }
            $epi->imagen_path = $request->file('imagen')->store('epis', 'public');
        }

        $epi->save();

        return response()->json([
            'ok' => true,
            'epi' => [
                'id' => $epi->id,
                'codigo' => $epi->codigo,
                'nombre' => $epi->nombre,
                'categoria' => $epi->categoria,
                'descripcion' => $epi->descripcion,
                'imagen_path' => $epi->imagen_path,
                'imagen_url' => $epi->imagen_path ? route('epis.imagen', $epi) : null,
                'activo' => (bool) $epi->activo,
            ],
        ]);
    }

    public function destroyEpi(Epi $epi)
    {
        if ($epi->asignaciones()->exists()) {
            $epi->activo = false;
            $epi->save();

            return response()->json([
                'ok' => true,
                'action' => 'deactivated',
                'epi' => [
                    'id' => $epi->id,
                    'activo' => (bool) $epi->activo,
                ],
            ]);
        }

        if ($epi->imagen_path) {
            Storage::disk('public')->delete($epi->imagen_path);
        }

        $epi->delete();

        return response()->json(['ok' => true, 'action' => 'deleted']);
    }

    public function asignarAUsuario(Request $request, User $user)
    {
        $data = $request->validate([
            'epi_id' => ['required', 'integer', Rule::exists('epis', 'id')->where('activo', true)],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'notas' => ['nullable', 'string'],
        ]);

        EpiUsuario::create([
            'user_id' => $user->id,
            'epi_id' => $data['epi_id'],
            'cantidad' => $data['cantidad'],
            'entregado_en' => now(),
            'notas' => $data['notas'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function devolverAsignacion(User $user, EpiUsuario $asignacion)
    {
        if ((int) $asignacion->user_id !== (int) $user->id) {
            abort(404);
        }

        if ($asignacion->devuelto_en) {
            return response()->json(['ok' => true, 'already_returned' => true]);
        }

        $data = request()->validate([
            'cantidad' => ['nullable', 'integer', 'min:1'],
        ]);

        $cantidadADevolver = (int) ($data['cantidad'] ?? $asignacion->cantidad);
        $cantidadADevolver = max(1, $cantidadADevolver);
        $cantidadDisponible = (int) $asignacion->cantidad;

        if ($cantidadADevolver > $cantidadDisponible) {
            return response()->json([
                'ok' => false,
                'message' => 'Cantidad a devolver inválida.',
            ], 422);
        }

        if ($cantidadADevolver < $cantidadDisponible) {
            // Devolución parcial: reducimos la asignación activa y creamos un registro devuelto
            $asignacion->cantidad = $cantidadDisponible - $cantidadADevolver;
            $asignacion->save();

            EpiUsuario::create([
                'user_id' => $asignacion->user_id,
                'epi_id' => $asignacion->epi_id,
                'cantidad' => $cantidadADevolver,
                'entregado_en' => $asignacion->entregado_en,
                'devuelto_en' => now(),
                'notas' => $asignacion->notas,
            ]);

            return response()->json(['ok' => true, 'partial' => true]);
        }

        $asignacion->devuelto_en = now();
        $asignacion->save();

        return response()->json(['ok' => true]);
    }

    public function actualizarFechasAsignacion(User $user, EpiUsuario $asignacion, Request $request)
    {
        if ((int) $asignacion->user_id !== (int) $user->id) {
            abort(404);
        }

        $data = $request->validate([
            'fecha_entrega' => ['nullable', 'date'],
            'fecha_devolucion' => ['nullable', 'date'],
        ]);

        if (!array_key_exists('fecha_entrega', $data) && !array_key_exists('fecha_devolucion', $data)) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes indicar alguna fecha para actualizar.',
            ], 422);
        }

        if (array_key_exists('fecha_entrega', $data)) {
            $asignacion->entregado_en = $data['fecha_entrega']
                ? Carbon::parse($data['fecha_entrega'])->startOfDay()
                : null;
        }

        if (array_key_exists('fecha_devolucion', $data)) {
            $asignacion->devuelto_en = $data['fecha_devolucion']
                ? Carbon::parse($data['fecha_devolucion'])->startOfDay()
                : null;
        }

        $asignacion->save();

        return response()->json([
            'ok' => true,
            'asignacion' => $this->mapAsignacion($asignacion),
        ]);
    }

    public function importarDesdeExcel(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $file = $data['file'];
        $spreadsheet = IOFactory::load($file->getRealPath());

        $totalAsignaciones = 0;
        $usuariosProcesados = [];
        $errores = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $nombre = trim((string) $sheet->getCell('B1')->getValue());
            $apellidos = trim((string) $sheet->getCell('B2')->getValue());
            $fechaRaw = $sheet->getCell('B5')->getValue();

            if ($nombre === '' || $apellidos === '') {
                $errores[] = "Hoja {$sheet->getTitle()}: falta nombre o apellidos (B1/B2).";
                continue;
            }

            $fechaEntrega = $this->excelValueToCarbon($fechaRaw);
            if (!$fechaEntrega) {
                $errores[] = "Hoja {$sheet->getTitle()}: fecha de entrega invÃ¡lida (B5).";
                continue;
            }

            $user = $this->buscarUsuarioPorNombre($nombre, $apellidos);
            if (!$user) {
                $errores[] = "Hoja {$sheet->getTitle()}: usuario no encontrado ({$nombre} {$apellidos}).";
                continue;
            }

            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            for ($col = 2; $col <= $highestColumn; $col++) { // Columna B en adelante
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $epiNombre = trim((string) $sheet->getCell($colLetter . '9')->getValue());
                if ($epiNombre === '' || Str::startsWith(Str::lower($epiNombre), 'epi')) {
                    continue;
                }

                // Cantidad estÃ¡ en fila 10 (correcciÃ³n)
                $cantidadRaw = $sheet->getCell($colLetter . '10')->getCalculatedValue();
                $cantidad = is_numeric($cantidadRaw) ? (int) $cantidadRaw : null;
                if (!$cantidad || $cantidad <= 0) {
                    continue;
                }

                // Notas: tomar valor de fila 11; si viene vacÃ­o, intentar fila 8
                $nota = trim((string) $sheet->getCell($colLetter . '11')->getValue());
                if ($nota === '') {
                    $nota = trim((string) $sheet->getCell($colLetter . '8')->getValue());
                }

                $epi = Epi::firstOrCreate(
                    ['nombre' => $epiNombre],
                    ['activo' => true]
                );

                EpiUsuario::create([
                    'user_id' => $user->id,
                    'epi_id' => $epi->id,
                    'cantidad' => $cantidad,
                    'entregado_en' => $fechaEntrega,
                    'devuelto_en' => null,
                    'notas' => $nota !== '' ? $nota : null,
                ]);

                $totalAsignaciones++;
            }

            $usuariosProcesados[] = $user->nombre_completo ?? $user->name ?? "{$nombre} {$apellidos}";
        }

        return response()->json([
            'ok' => empty($errores),
            'asignaciones_creadas' => $totalAsignaciones,
            'usuarios' => $usuariosProcesados,
            'errores' => $errores,
            'message' => "ImportaciÃ³n completada. Asignaciones creadas: {$totalAsignaciones}.",
        ]);
    }

    private function buscarUsuarioPorNombre(string $nombre, string $apellidos): ?User
    {
        $nombreNorm = $this->normalizarNombre($nombre);
        $apellidosNorm = $this->normalizarNombre($apellidos);
        $apellidoTokens = array_filter(explode(' ', $apellidosNorm));

        return User::all()->first(function (User $u) use ($nombreNorm, $apellidosNorm, $apellidoTokens) {
            $full = trim(($u->name ?? '') . ' ' . ($u->primer_apellido ?? '') . ' ' . ($u->segundo_apellido ?? ''));
            $fullNorm = $this->normalizarNombre($full);

            if ($fullNorm === trim($nombreNorm . ' ' . $apellidosNorm)) {
                return true; // match exact nombre + apellidos
            }

            $userNombre = $this->normalizarNombre($u->name ?? '');
            $userApellido1 = $this->normalizarNombre($u->primer_apellido ?? '');
            $userApellido2 = $this->normalizarNombre($u->segundo_apellido ?? '');

            // Requerir coincidencia de nombre
            if ($userNombre !== $nombreNorm) {
                return false;
            }

            // Coincidencia flexible: si el primer token del apellido del Excel coincide con primer_apellido del usuario
            $primerToken = $apellidoTokens[0] ?? '';
            if ($primerToken !== '' && $primerToken === $userApellido1) {
                // Si hay segundo token en Excel, que coincida con segundo_apellido si existe
                if (isset($apellidoTokens[1])) {
                    return $apellidoTokens[1] === $userApellido2;
                }
                return true; // solo primer apellido en Excel
            }

            // Fallback: todos los tokens de apellidos del Excel estÃ¡n contenidos en la representaciÃ³n de apellidos del usuario
            $userApellidosNorm = trim($userApellido1 . ' ' . $userApellido2);
            foreach ($apellidoTokens as $token) {
                if ($token === '') {
                    continue;
                }
                if (!Str::contains($userApellidosNorm, $token)) {
                    return false;
                }
            }
            return !empty($apellidoTokens);
        });
    }

    private function normalizarNombre(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/\\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function excelValueToCarbon($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
