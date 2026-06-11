<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\AlertaAdjunto;
use App\Models\AlertaAdjuntoFirma;
use App\Models\AlertaLeida;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentoAlertaController extends Controller
{
    public function index(Request $request)
    {
        $query = AlertaAdjunto::with(['alerta.usuario1', 'firmas.user']);

        // Filtro por nombre de documento
        if ($request->filled('nombre')) {
            $query->where('nombre_original', 'like', '%' . $request->nombre . '%');
        }

        // Filtro por tipo de documento (nombre exacto del pluck)
        if ($request->filled('tipo')) {
            $query->where('nombre_original', $request->tipo);
        }

        // Filtro por requiere firma
        if ($request->filled('requiere_firma')) {
            $query->where('requiere_firma', $request->requiere_firma === '1');
        }

        // Filtro por emisor
        if ($request->filled('emisor')) {
            $query->whereHas('alerta.usuario1', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->emisor . '%')
                  ->orWhere('primer_apellido', 'like', '%' . $request->emisor . '%');
            });
        }

        // Filtro por destino
        if ($request->filled('destino')) {
            $query->whereHas('alerta', function ($q) use ($request) {
                $q->where('destino', 'like', '%' . $request->destino . '%');
            });
        }

        // Filtro por fecha
        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->fecha);
        }

        // Ordenamiento
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $allowedSorts = ['id', 'nombre_original', 'requiere_firma', 'created_at'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->input('per_page', 25);
        $documentos = $query->paginate($perPage)->appends($request->except('page'));
        $documentos->getCollection()->transform(function (AlertaAdjunto $doc) {
            $usuariosObjetivo = $this->resolveUsuariosObjetivo($doc->alerta);
            $firmasUsuarioIds = $doc->firmas->pluck('user_id')->unique();
            $usuariosSinFirmaAsignada = $usuariosObjetivo->filter(
                fn(User $user) => !$firmasUsuarioIds->contains($user->id)
            )->values();

            $doc->setRelation('usuariosSinFirmaAsignada', $usuariosSinFirmaAsignada);

            return $doc;
        });

        // Tipos de documento para el filtro select (pluck de nombres únicos)
        $tiposDocumento = AlertaAdjunto::distinct()->pluck('nombre_original')->sort()->values();
        $usuariosActivos = User::query()
            ->with('categoria:id,nombre')
            ->orderBy('name')
            ->get(['id', 'name', 'primer_apellido', 'segundo_apellido', 'rol', 'categoria_id', 'imagen']);
        $rolesDisponibles = User::query()
            ->whereNotNull('rol')
            ->distinct()
            ->orderBy('rol')
            ->pluck('rol')
            ->values();
        $categoriasDisponibles = Categoria::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        // Filtros activos
        $filtrosActivos = $this->buildFiltrosActivos($request);

        return view('documentos-alertas.index', compact(
            'documentos',
            'tiposDocumento',
            'usuariosActivos',
            'rolesDisponibles',
            'categoriasDisponibles',
            'filtrosActivos',
            'sort',
            'order',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'adjuntos' => 'required|array|min:1|max:5',
            'adjuntos.*' => 'file|mimes:pdf|max:10240',
            'mensaje' => 'nullable|string|max:2000',
            'requiere_firma' => 'nullable|boolean',
            'destino_tipo' => ['required', Rule::in(['todos', 'rol', 'categoria', 'usuario'])],
            'rol' => 'nullable|string|max:100',
            'categoria_id' => 'nullable|integer',
            'destinatario_id' => 'nullable|integer|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $usuariosDestino = !empty($data['user_ids'])
            ? User::query()->whereIn('id', $data['user_ids'])->get()
            : $this->resolveUsuariosDestinoDesdeFormulario($data);
        if ($usuariosDestino->isEmpty()) {
            return back()->withErrors(['adjuntos' => 'No hay usuarios destino con la selección indicada.'])->withInput();
        }

        $requiereFirma = (bool) ($data['requiere_firma'] ?? false);
        $archivos = $request->file('adjuntos') ?? [];
        $nombresNormalizados = [];
        foreach ($archivos as $archivo) {
            $normalizado = mb_strtolower(trim((string) $archivo->getClientOriginalName()));
            if (in_array($normalizado, $nombresNormalizados, true)) {
                return back()->withErrors(['adjuntos' => 'No se permiten archivos con el mismo nombre en la misma subida.'])->withInput();
            }
            $nombresNormalizados[] = $normalizado;
        }

        $alerta = Alerta::create([
            'mensaje' => $data['mensaje'] ?: 'Documento subido desde /documentos-alertas',
            'user_id_1' => Auth::id(),
            'user_id_2' => session()->get('companero_id', null),
            'destino' => $this->mapDestino($data),
            'destinatario' => $this->mapDestinatario($data),
            'destinatario_id' => $data['destino_tipo'] === 'usuario' ? $data['destinatario_id'] : null,
        ]);

        foreach ($usuariosDestino as $destinatario) {
            AlertaLeida::firstOrCreate(
                ['alerta_id' => $alerta->id, 'user_id' => $destinatario->id],
                ['leida_en' => null]
            );
        }

        foreach ($archivos as $archivo) {
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = strtolower($archivo->getClientOriginalExtension() ?: 'pdf');
            $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
            $baseLimpia = Str::slug($base) ?: 'documento';
            $nombreFisico = $baseLimpia . '_' . now()->format('Ymd_His_u') . '_' . Str::lower(Str::random(8)) . '.' . $extension;
            $ruta = $archivo->storeAs('alertas/adjuntos', $nombreFisico, 'public');

            $adjunto = AlertaAdjunto::create([
                'alerta_id' => $alerta->id,
                'nombre_original' => $nombreOriginal,
                'ruta' => $ruta,
                'requiere_firma' => $requiereFirma,
            ]);

            if ($requiereFirma) {
                foreach ($usuariosDestino as $destinatario) {
                    AlertaAdjuntoFirma::firstOrCreate(
                        ['alerta_adjunto_id' => $adjunto->id, 'user_id' => $destinatario->id],
                        ['firmado' => false]
                    );
                }
            }
        }

        return redirect()->route('documentos-alertas.index')->with('success', 'Documentos subidos y enviados correctamente.');
    }

    public function asignarFirmasFaltantes(Request $request, AlertaAdjunto $documento)
    {
        if (!$documento->requiere_firma) {
            return back()->withErrors(['documento' => 'Este documento no requiere firma.']);
        }

        $usuariosObjetivo = $this->resolveUsuariosObjetivo($documento->alerta);
        $firmasUsuarioIds = $documento->firmas()->pluck('user_id');
        $usuariosFaltantes = $usuariosObjetivo->whereNotIn('id', $firmasUsuarioIds)->values();

        if ($usuariosFaltantes->isEmpty()) {
            return back()->with('success', 'No hay usuarios nuevos pendientes de asignar.');
        }

        $idsPermitidos = $usuariosFaltantes->pluck('id')->all();
        $data = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $idsSeleccionados = collect($data['user_ids'] ?? [])->map(fn($id) => (int) $id)->all();
        if (empty($idsSeleccionados)) {
            $idsSeleccionados = $idsPermitidos;
        }

        $idsFinales = array_values(array_intersect($idsSeleccionados, $idsPermitidos));
        if (empty($idsFinales)) {
            return back()->withErrors(['documento' => 'No se seleccionaron usuarios válidos para asignar.']);
        }

        foreach ($idsFinales as $userId) {
            AlertaAdjuntoFirma::firstOrCreate(
                ['alerta_adjunto_id' => $documento->id, 'user_id' => $userId],
                ['firmado' => false]
            );

            if ($documento->alerta_id) {
                AlertaLeida::firstOrCreate(
                    ['alerta_id' => $documento->alerta_id, 'user_id' => $userId],
                    ['leida_en' => null]
                );
            }
        }

        return redirect()->route('documentos-alertas.index')->with('success', 'Firmas activadas para los usuarios seleccionados.');
    }

    public function revocarFirma(AlertaAdjuntoFirma $firma)
    {
        if (!$firma->firmado) {
            return back()->withErrors(['firma' => 'La firma ya estaba pendiente.']);
        }

        if (!empty($firma->firma_ruta) && Storage::disk('public')->exists($firma->firma_ruta)) {
            Storage::disk('public')->delete($firma->firma_ruta);
        }

        $firma->update([
            'firmado' => false,
            'firmado_dia' => null,
            'firma_ruta' => null,
        ]);

        return redirect()->route('documentos-alertas.index')->with('success', 'Firma revocada. El usuario debe firmar de nuevo el documento.');
    }

    private function buildFiltrosActivos(Request $request): array
    {
        $filtros = [];

        if ($request->filled('nombre')) {
            $filtros[] = 'Nombre: <strong>' . e($request->nombre) . '</strong>';
        }
        if ($request->filled('tipo')) {
            $filtros[] = 'Tipo: <strong>' . e($request->tipo) . '</strong>';
        }
        if ($request->filled('requiere_firma')) {
            $filtros[] = 'Requiere firma: <strong>' . ($request->requiere_firma === '1' ? 'Sí' : 'No') . '</strong>';
        }
        if ($request->filled('emisor')) {
            $filtros[] = 'Emisor: <strong>' . e($request->emisor) . '</strong>';
        }
        if ($request->filled('destino')) {
            $filtros[] = 'Destino: <strong>' . e($request->destino) . '</strong>';
        }
        if ($request->filled('fecha')) {
            $filtros[] = 'Fecha: <strong>' . e($request->fecha) . '</strong>';
        }

        return $filtros;
    }

    private function resolveUsuariosDestinoDesdeFormulario(array $data)
    {
        return match ($data['destino_tipo']) {
            'todos' => User::query()->get(),
            'rol' => User::query()->where('rol', $data['rol'] ?? '')->get(),
            'categoria' => User::query()->where('categoria_id', $data['categoria_id'] ?? 0)->get(),
            'usuario' => User::query()->where('id', $data['destinatario_id'] ?? 0)->get(),
        };
    }

    private function mapDestino(array $data): ?string
    {
        return match ($data['destino_tipo']) {
            'todos' => 'todos',
            'rol' => $data['rol'] ?? null,
            default => null,
        };
    }

    private function mapDestinatario(array $data): ?string
    {
        return match ($data['destino_tipo']) {
            'categoria' => (string) ($data['categoria_id'] ?? ''),
            default => null,
        };
    }

    private function resolveUsuariosObjetivo(?Alerta $alerta)
    {
        if (!$alerta) {
            return collect();
        }

        $porAsignacion = User::query()
            ->whereIn(
                'id',
                AlertaLeida::where('alerta_id', $alerta->id)->pluck('user_id')
            )
            ->get();

        if ($alerta->destino === 'todos') {
            return User::query()->get()->unique('id')->values();
        }

        if (!empty($alerta->destino)) {
            return User::query()->where('rol', $alerta->destino)->get()->unique('id')->values();
        }

        if (!empty($alerta->destinatario)) {
            return User::query()->where('categoria_id', $alerta->destinatario)->get()->unique('id')->values();
        }

        if (!empty($alerta->destinatario_id)) {
            return User::query()->where('id', $alerta->destinatario_id)->get()->unique('id')->values();
        }

        return $porAsignacion->unique('id')->values();
    }
}
