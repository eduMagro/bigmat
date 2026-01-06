<?php

namespace App\Http\Controllers;

use App\Models\Incorporacion;
use App\Models\IncorporacionDocumento;
use App\Models\IncorporacionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\IncorporacionPendienteCeoMail;
use App\Mail\IncorporacionAprobadaCeoMail;
use App\Models\Empresa;
use App\Models\Categoria;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class IncorporacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Incorporacion::with(['creador', 'formaciones', 'documentos'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('empresa')) {
            $query->where('empresa_destino', $request->empresa);
        }

        if ($request->boolean('no_asignado')) {
            $query->whereNull('user_id');
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('primer_apellido', 'like', "%{$buscar}%")
                    ->orWhere('segundo_apellido', 'like', "%{$buscar}%")
                    ->orWhere('email_provisional', 'like', "%{$buscar}%")
                    ->orWhere('dni', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $incorporaciones = $query->paginate(15);

        // Estadísticas
        $stats = [
            'pendientes' => Incorporacion::where('estado', 'pendiente')->count(),
            'datos_recibidos' => Incorporacion::where('estado', 'datos_recibidos')->count(),
            'en_proceso' => Incorporacion::where('estado', 'en_proceso')->count(),
            'completadas' => Incorporacion::where('estado', 'completada')->count(),
        ];

        $empresas = Empresa::orderBy('nombre')->get();
        return view('incorporaciones.index', compact('incorporaciones', 'stats', 'empresas'));
    }

    public function create()
    {
        $empresas = Empresa::orderBy('nombre')->get();
        return view('incorporaciones.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'empresa_destino' => 'required|exists:empresas,id',
            'name' => 'required|string|max:255',
            'primer_apellido' => 'nullable|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'telefono_provisional' => 'required|string|max:20',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['token'] = Str::random(64);

        $incorporacion = Incorporacion::create($validated);

        // Registrar log
        $nombreCompleto = trim($validated['name'] . ' ' . $validated['primer_apellido'] . ' ' . ($validated['segundo_apellido'] ?? ''));
        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_CREADA,
            'Incorporación creada para ' . $nombreCompleto
        );

        return redirect()
            ->route('incorporaciones.show', $incorporacion)
            ->with('success', 'Incorporación creada correctamente. Puedes enviar el enlace al candidato.');
    }

    public function show(Incorporacion $incorporacion)
    {
        $incorporacion->load(['formaciones', 'documentos.subidoPor', 'logs.usuario', 'creador']);

        // Tipos de formación que pueden venir del formulario público
        $tiposFormacion = ['curso_20h_generico', 'curso_6h_ferralla', 'formacion_puesto'];

        // Preparar checklist de documentos
        $documentosPost = [];
        foreach (Incorporacion::DOCUMENTOS_POST as $tipo => $nombre) {
            // Caso especial: formacion_puesto o contrato_trabajo permiten múltiples archivos
            if (in_array($tipo, ['formacion_puesto', 'contrato_trabajo'])) {
                $docs = $incorporacion->documentos->where('tipo', $tipo);
                $formacionesPublicas = $incorporacion->formaciones->where('tipo', $tipo);

                $max = $tipo === 'formacion_puesto' ? Incorporacion::MAX_FORMACION_PUESTO : 9999;

                $documentosPost[$tipo] = [
                    'nombre' => $nombre,
                    'documentos' => $docs, // Múltiples documentos
                    'formaciones' => $formacionesPublicas, // Múltiples formaciones del formulario público
                    'completado' => $docs->count() > 0 || $formacionesPublicas->count() > 0,
                    'multiple' => true,
                    'max' => $max,
                    'puede_anadir' => ($docs->count() + $formacionesPublicas->count()) < $max,
                    'total_archivos' => $docs->count() + $formacionesPublicas->count(),
                ];
                continue;
            }

            // Primero buscar en documentos post-incorporación
            $doc = $incorporacion->documentos->where('tipo', $tipo)->first();

            // Si es un tipo de formación y no hay documento post, buscar en formaciones del formulario público
            $formacionPublica = null;
            if (!$doc && in_array($tipo, $tiposFormacion)) {
                $formacionPublica = $incorporacion->formaciones->where('tipo', $tipo)->first();
            }

            $documentosPost[$tipo] = [
                'nombre' => $nombre,
                'documento' => $doc,
                'formacion' => $formacionPublica, // Documento subido desde formulario público
                'completado' => $doc ? $doc->completado : ($formacionPublica ? true : false),
                'multiple' => false,
            ];
        }

        return view('incorporaciones.show', compact('incorporacion', 'documentosPost'));
    }

    public function update(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'puesto' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'primer_apellido' => 'nullable|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'email_provisional' => 'nullable|email|max:255',
            'telefono_provisional' => 'nullable|string|max:20',
        ]);

        $validated['updated_by'] = auth()->id();

        $incorporacion->update($validated);

        return redirect()
            ->route('incorporaciones.show', $incorporacion)
            ->with('success', 'Incorporación actualizada correctamente.');
    }

    public function updateFechaIncorporacion(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'fecha_incorporacion' => 'required|date',
        ]);

        $incorporacion->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fecha de incorporación actualizada',
        ]);
    }

    public function destroy(Incorporacion $incorporacion)
    {
        // Obtener carpeta del usuario
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaCarpeta = "private/documentos/{$carpetaUsuario}";

        // Eliminar toda la carpeta del usuario si existe
        if (Storage::exists($rutaCarpeta)) {
            Storage::deleteDirectory($rutaCarpeta);
        }

        $incorporacion->delete();

        return redirect()
            ->route('incorporaciones.index')
            ->with('success', 'Incorporación eliminada correctamente.');
    }

    public function subirDocumento(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(Incorporacion::DOCUMENTOS_POST)),
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notas' => 'nullable|string|max:500',
        ]);

        // Caso especial: formacion_puesto y contrato_trabajo permiten múltiples archivos
        if (in_array($validated['tipo'], ['formacion_puesto', 'contrato_trabajo'])) {
            $max = $validated['tipo'] === 'formacion_puesto' ? Incorporacion::MAX_FORMACION_PUESTO : 9999;
            $totalActual = $incorporacion->documentos()->where('tipo', $validated['tipo'])->count();

            if ($totalActual >= $max) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has alcanzado el límite de ' . $max . ' archivos para este tipo de documento',
                ], 422);
            }
        }

        // Guardar archivo en carpeta privada del usuario
        $archivo = $request->file('archivo');
        $nombreArchivo = $validated['tipo'] . '_' . time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();

        // Obtener carpeta del usuario
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $archivo->storeAs("private/documentos/{$carpetaUsuario}", $nombreArchivo);

        // Crear documento (para múltiples siempre crear nuevo, para otros actualizar o crear)
        if (in_array($validated['tipo'], ['formacion_puesto', 'contrato_trabajo'])) {
            $documento = $incorporacion->documentos()->create([
                'tipo' => $validated['tipo'],
                'archivo' => $nombreArchivo,
                'notas' => $validated['notas'] ?? null,
                'completado' => true,
                'completado_at' => now(),
                'subido_por' => auth()->id(),
            ]);
        } else {
            $documento = $incorporacion->documentos()->updateOrCreate(
                ['tipo' => $validated['tipo']],
                [
                    'archivo' => $nombreArchivo,
                    'notas' => $validated['notas'] ?? null,
                    'completado' => true,
                    'completado_at' => now(),
                    'subido_por' => auth()->id(),
                ]
            );
        }

        // Registrar log
        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_DOCUMENTO_SUBIDO,
            'Documento subido: ' . Incorporacion::DOCUMENTOS_POST[$validated['tipo']]
        );

        // Verificar si todos los documentos están completos
        if ($incorporacion->porcentajeDocumentosPost() === 100) {
            $incorporacion->update(['estado' => Incorporacion::ESTADO_COMPLETADA]);
            $incorporacion->registrarLog(
                IncorporacionLog::ACCION_ESTADO_CAMBIADO,
                'Estado cambiado a Completada (todos los documentos subidos)'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Documento subido correctamente',
            'documento' => $documento,
            'porcentaje' => $incorporacion->porcentajeDocumentosPost(),
        ]);
    }

    public function eliminarDocumento(Request $request, Incorporacion $incorporacion, $tipo)
    {
        // Para formacion_puesto o contrato_trabajo, se puede pasar un ID específico
        if (in_array($tipo, ['formacion_puesto', 'contrato_trabajo']) && $request->has('documento_id')) {
            $documento = $incorporacion->documentos()
                ->where('tipo', $tipo)
                ->where('id', $request->input('documento_id'))
                ->first();
        } else {
            $documento = $incorporacion->documentos()->where('tipo', $tipo)->first();
        }

        if (!$documento) {
            return response()->json(['success' => false, 'message' => 'Documento no encontrado'], 404);
        }

        // Eliminar archivo de la carpeta privada
        if ($documento->archivo) {
            $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
            Storage::delete("private/documentos/{$carpetaUsuario}/" . $documento->archivo);
        }

        $documento->delete();

        // Si estaba completada, cambiar a en_proceso
        if ($incorporacion->estado === Incorporacion::ESTADO_COMPLETADA) {
            $incorporacion->update(['estado' => Incorporacion::ESTADO_EN_PROCESO]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documento eliminado correctamente',
            'porcentaje' => $incorporacion->fresh()->porcentajeDocumentosPost(),
        ]);
    }

    public function cambiarEstado(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'estado' => 'required|in:pendiente,datos_recibidos,en_proceso,completada,cancelada',
        ]);

        $estadoAnterior = $incorporacion->estado;
        $incorporacion->update([
            'estado' => $validated['estado'],
            'updated_by' => auth()->id(),
        ]);

        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_ESTADO_CAMBIADO,
            "Estado cambiado de {$estadoAnterior} a {$validated['estado']}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'estado' => $incorporacion->estado_badge,
        ]);
    }

    public function marcarEnlaceEnviado(Incorporacion $incorporacion)
    {
        $incorporacion->update([
            'enlace_enviado_at' => now(),
        ]);

        $incorporacion->registrarLog(
            IncorporacionLog::ACCION_ENLACE_ENVIADO,
            'Enlace marcado como enviado'
        );

        return response()->json([
            'success' => true,
            'message' => 'Enlace marcado como enviado',
        ]);
    }

    public function copiarEnlace(Incorporacion $incorporacion)
    {
        return response()->json([
            'success' => true,
            'url' => $incorporacion->url_formulario,
        ]);
    }

    /**
     * Aprobar incorporación por RRHH (entrevista realizada, se propone al CEO)
     */
    public function aprobarRrhh(Incorporacion $incorporacion)
    {
        // Verificar que existan usuarios CEO antes de aprobar
        $ceos = User::withoutGlobalScopes()
            ->whereHas('categoria', function ($query) {
                $query->whereRaw('LOWER(nombre) LIKE ?', ['%ceo%']);
            })
            ->where('estado', 'activo')
            ->whereNotNull('email')
            ->get();

        if ($ceos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún usuario con categoría CEO para notificar. Configure un usuario con categoría CEO antes de aprobar.',
            ], 422);
        }

        $incorporacion->update([
            'aprobado_rrhh' => true,
            'aprobado_rrhh_at' => now(),
            'aprobado_rrhh_by' => auth()->id(),
        ]);

        $incorporacion->registrarLog(
            'aprobacion_rrhh',
            'Incorporación aprobada por RRHH. Entrevista realizada y propuesto al CEO.'
        );

        // Enviar email al CEO
        $this->enviarEmailACeo($incorporacion, $ceos);

        return response()->json([
            'success' => true,
            'message' => 'Incorporación aprobada. Se ha notificado al CEO para su validación.',
        ]);
    }

    /**
     * Revocar aprobación de RRHH
     */
    public function revocarRrhh(Incorporacion $incorporacion)
    {
        $incorporacion->update([
            'aprobado_rrhh' => false,
            'aprobado_rrhh_at' => null,
            'aprobado_rrhh_by' => null,
        ]);

        $incorporacion->registrarLog(
            'revocacion_rrhh',
            'Aprobación de RRHH revocada.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Aprobación revocada.',
        ]);
    }

    /**
     * Aprobar incorporación por CEO
     */
    public function aprobarCeo(Incorporacion $incorporacion)
    {
        // Verificar que existan usuarios en el departamento RRHH antes de aprobar
        $usuariosRrhh = User::withoutGlobalScopes()
            ->where('estado', 'activo')
            ->whereNotNull('email')
            ->whereHas('departamentos', function ($q) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%rrhh%'])
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%recursos humanos%'])
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%human%']);
            })
            ->get();

        if ($usuariosRrhh->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún usuario en el departamento de RRHH para notificar. Configure usuarios en el departamento RRHH antes de aprobar.',
            ], 422);
        }

        // Verificar que la incorporación tenga los datos necesarios para crear el usuario
        if (!$incorporacion->dni || !$incorporacion->email) {
            return response()->json([
                'success' => false,
                'message' => 'La incorporación no tiene los datos completos (DNI o email). El candidato debe completar el formulario primero.',
            ], 422);
        }

        // Crear el usuario si no existe aún
        $usuario = null;
        if (!$incorporacion->user_id) {
            $usuario = $this->crearUsuarioDesdeIncorporacion($incorporacion);
            $incorporacion->update(['user_id' => $usuario->id]);
            $incorporacion->registrarLog('usuario_creado', "Usuario creado: {$usuario->nombre_completo} (ID: {$usuario->id})");
        }

        $incorporacion->update([
            'aprobado_ceo' => true,
            'aprobado_ceo_at' => now(),
            'aprobado_ceo_by' => auth()->id(),
        ]);

        $incorporacion->registrarLog(
            'aprobacion_ceo',
            'Incorporación aprobada por CEO. Trabajador autorizado para incorporarse.'
        );

        // Enviar email a usuarios de RRHH
        $this->enviarEmailARrhh($incorporacion, $usuariosRrhh);

        $mensaje = 'Incorporación aprobada. Se ha notificado a RRHH.';
        if ($usuario) {
            $mensaje .= " Usuario creado: {$usuario->nombre_completo}";
        }

        return response()->json([
            'success' => true,
            'message' => $mensaje,
        ]);
    }

    /**
     * Crear usuario a partir de los datos de la incorporación
     */
    private function crearUsuarioDesdeIncorporacion(Incorporacion $incorporacion): User
    {
        $dni = strtoupper($incorporacion->dni);

        // Verificar si ya existe un usuario con este DNI
        $usuarioExistente = User::withoutGlobalScopes()->where('dni', $dni)->first();
        if ($usuarioExistente) {
            if ($usuarioExistente->estado !== 'activo') {
                $usuarioExistente->update(['estado' => 'activo']);
            }
            return $usuarioExistente;
        }

        // empresa_destino ya almacena el ID de la empresa
        $empresaId = $incorporacion->empresa_destino;

        // Obtener categoría por defecto
        $categoriaId = Categoria::first()?->id ?? 1;

        // Contraseña: DNI sin la letra (los 8 primeros caracteres numéricos)
        $password = preg_replace('/[^0-9]/', '', $dni);

        Log::info('Creando usuario desde aprobación CEO', [
            'incorporacion_id' => $incorporacion->id,
            'nombre' => $incorporacion->name,
            'dni' => $dni,
        ]);

        return User::create([
            'name' => $incorporacion->name,
            'primer_apellido' => $incorporacion->primer_apellido,
            'segundo_apellido' => $incorporacion->segundo_apellido,
            'dni' => $dni,
            'email' => strtolower($incorporacion->email),
            'movil_personal' => $incorporacion->telefono,
            'password' => Hash::make($password),
            'empresa_id' => $empresaId,
            'categoria_id' => $categoriaId,
            'estado' => 'activo',
        ]);
    }

    /**
     * Revocar aprobación de CEO
     */
    public function revocarCeo(Incorporacion $incorporacion)
    {
        $incorporacion->update([
            'aprobado_ceo' => false,
            'aprobado_ceo_at' => null,
            'aprobado_ceo_by' => null,
        ]);

        $incorporacion->registrarLog(
            'revocacion_ceo',
            'Aprobación de CEO revocada.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Aprobación de CEO revocada.',
        ]);
    }

    /**
     * Eliminar archivo de incorporación (DNI, certificado bancario, formación)
     */
    public function eliminarArchivo(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:dni_frontal,dni_trasero,certificado_bancario,formacion',
            'formacion_id' => 'nullable|integer',
        ]);

        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $tipo = $validated['tipo'];

        if ($tipo === 'formacion') {
            // Eliminar documento de formación
            $formacion = $incorporacion->formaciones()->find($validated['formacion_id']);
            if (!$formacion) {
                return response()->json(['success' => false, 'message' => 'Documento no encontrado'], 404);
            }

            if ($formacion->archivo) {
                Storage::delete("private/documentos/{$carpetaUsuario}/" . $formacion->archivo);
            }

            $nombreDoc = $formacion->tipo_nombre;
            $formacion->delete();

            $incorporacion->registrarLog(
                'archivo_eliminado',
                "Documento de formación eliminado: {$nombreDoc}"
            );

        } else {
            // Eliminar DNI o certificado bancario
            $archivo = $incorporacion->{$tipo};
            if (!$archivo) {
                return response()->json(['success' => false, 'message' => 'Archivo no encontrado'], 404);
            }

            Storage::delete("private/documentos/{$carpetaUsuario}/" . $archivo);

            $incorporacion->update([$tipo => null]);

            $nombres = [
                'dni_frontal' => 'DNI Frontal',
                'dni_trasero' => 'DNI Trasero',
                'certificado_bancario' => 'Certificado Bancario',
            ];

            $incorporacion->registrarLog(
                'archivo_eliminado',
                "Archivo eliminado: {$nombres[$tipo]}"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado correctamente',
        ]);
    }

    /**
     * Resubir/actualizar un archivo del candidato (DNI frontal, trasero, certificado bancario)
     */
    public function resubirArchivo(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'campo' => 'required|in:dni_frontal,dni_trasero,certificado_bancario',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $campo = $validated['campo'];
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);

        // Eliminar archivo anterior si existe
        $archivoAnterior = $incorporacion->{$campo};
        if ($archivoAnterior) {
            Storage::delete("private/documentos/{$carpetaUsuario}/" . $archivoAnterior);
        }

        // Guardar nuevo archivo
        $archivo = $request->file('archivo');
        $nombreArchivo = $campo . '_' . time() . '.' . $archivo->getClientOriginalExtension();
        $archivo->storeAs("private/documentos/{$carpetaUsuario}", $nombreArchivo);

        // Actualizar incorporación
        $incorporacion->update([$campo => $nombreArchivo]);

        // Registrar log
        $nombres = [
            'dni_frontal' => 'DNI Frontal',
            'dni_trasero' => 'DNI Trasero',
            'certificado_bancario' => 'Certificado Bancario',
        ];

        $incorporacion->registrarLog(
            'archivo_actualizado',
            "Archivo actualizado: {$nombres[$campo]}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Documento actualizado correctamente',
        ]);
    }

    /**
     * Actualizar un campo de la incorporación (número afiliación SS, etc.)
     */
    public function actualizarCampo(Request $request, Incorporacion $incorporacion)
    {
        $validated = $request->validate([
            'campo' => 'required|in:numero_afiliacion_ss,user_id',
            'valor' => 'nullable',
        ]);

        $campo = $validated['campo'];
        $valor = $validated['valor'];

        if ($campo === 'user_id') {
            if ($valor) {
                // Verificar que el usuario existe
                $user = User::withoutGlobalScopes()->find($valor);
                if (!$user) {
                    return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
                }
                $incorporacion->update(['user_id' => $valor]);
                $incorporacion->registrarLog('actualizacion_campo', "Usuario asignado: {$user->nombre_completo}");
            } else {
                $incorporacion->update(['user_id' => null]);
                $incorporacion->registrarLog('actualizacion_campo', "Usuario desasignado");
            }
        } else {
            // Validaciones específicas por campo
            if ($campo === 'numero_afiliacion_ss') {
                if (!preg_match('/^[0-9]{12}$/', $valor)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El número de afiliación debe tener 12 dígitos',
                    ], 422);
                }
            }

            $incorporacion->update([$campo => $valor]);

            $nombres = [
                'numero_afiliacion_ss' => 'Número de Afiliación SS',
            ];

            $incorporacion->registrarLog(
                'actualizacion_campo',
                "Campo actualizado: " . ($nombres[$campo] ?? $campo)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Campo actualizado correctamente',
        ]);
    }

    /**
     * Buscar usuarios para asignar a la incorporación
     */
    public function buscarUsuarios(Request $request)
    {
        $query = $request->get('q');

        $users = User::withoutGlobalScopes()
            ->where('estado', 'activo')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('primer_apellido', 'like', "%{$query}%")
                    ->orWhere('segundo_apellido', 'like', "%{$query}%")
                    ->orWhere('dni', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'nombre_completo' => $user->nombre_completo,
                    'dni' => $user->dni,
                    'email' => $user->email,
                    'imagen_url' => $user->ruta_imagen,
                    'puesto' => $user->categoria?->nombre ?? 'Sin categoría'
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Descargar archivo privado de incorporación
     */
    public function descargarArchivo(Incorporacion $incorporacion, string $archivo)
    {
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaArchivo = "private/documentos/{$carpetaUsuario}/{$archivo}";

        if (!Storage::exists($rutaArchivo)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::download($rutaArchivo);
    }

    /**
     * Ver archivo privado de incorporación (inline)
     */
    public function verArchivo(Incorporacion $incorporacion, string $archivo)
    {
        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaArchivo = "private/documentos/{$carpetaUsuario}/{$archivo}";

        if (!Storage::exists($rutaArchivo)) {
            abort(404, 'Archivo no encontrado');
        }

        $mimeType = Storage::mimeType($rutaArchivo);

        return response(Storage::get($rutaArchivo))
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $archivo . '"');
    }

    /**
     * Obtener la carpeta del usuario para una incorporación
     */
    private function obtenerCarpetaUsuario(Incorporacion $incorporacion): string
    {
        // Si tiene usuario vinculado, usar su carpeta
        if ($incorporacion->user_id) {
            $usuario = User::withoutGlobalScopes()->find($incorporacion->user_id);
            if ($usuario) {
                $nombre = Str::slug($usuario->name, '_');
                $apellido = Str::slug($usuario->primer_apellido, '_');
                return "{$nombre}_{$apellido}_{$usuario->id}";
            }
        }

        // Fallback: usar el ID de la incorporación
        return "incorporacion_{$incorporacion->id}";
    }

    /**
     * Descargar ZIP con toda la documentación del trabajador
     */
    public function descargarZip(Incorporacion $incorporacion)
    {
        $incorporacion->load(['formaciones', 'documentos']);

        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaCarpeta = "private/documentos/{$carpetaUsuario}";

        // Nombre del archivo ZIP
        $nombreCompleto = Str::slug($incorporacion->name . '_' . $incorporacion->primer_apellido . '_' . ($incorporacion->segundo_apellido ?? ''), '_');
        $nombreZip = "documentacion_{$nombreCompleto}.zip";

        // Crear ZIP temporal
        $zipPath = storage_path("app/temp/{$nombreZip}");

        // Asegurar que existe el directorio temp
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP');
        }

        $archivosAgregados = 0;

        // 1. DNI Frontal
        if ($incorporacion->dni_frontal) {
            $rutaArchivo = storage_path("app/{$rutaCarpeta}/{$incorporacion->dni_frontal}");
            if (file_exists($rutaArchivo)) {
                $extension = pathinfo($incorporacion->dni_frontal, PATHINFO_EXTENSION);
                $zip->addFile($rutaArchivo, "01_DNI/dni_frontal.{$extension}");
                $archivosAgregados++;
            }
        }

        // 2. DNI Trasero
        if ($incorporacion->dni_trasero) {
            $rutaArchivo = storage_path("app/{$rutaCarpeta}/{$incorporacion->dni_trasero}");
            if (file_exists($rutaArchivo)) {
                $extension = pathinfo($incorporacion->dni_trasero, PATHINFO_EXTENSION);
                $zip->addFile($rutaArchivo, "01_DNI/dni_trasero.{$extension}");
                $archivosAgregados++;
            }
        }

        // 3. Certificado bancario
        if ($incorporacion->certificado_bancario) {
            $rutaArchivo = storage_path("app/{$rutaCarpeta}/{$incorporacion->certificado_bancario}");
            if (file_exists($rutaArchivo)) {
                $extension = pathinfo($incorporacion->certificado_bancario, PATHINFO_EXTENSION);
                $zip->addFile($rutaArchivo, "02_Datos_Bancarios/certificado_bancario.{$extension}");
                $archivosAgregados++;
            }
        }

        // 4. Documentos de formación (del formulario público)
        foreach ($incorporacion->formaciones as $index => $formacion) {
            if ($formacion->archivo) {
                $rutaArchivo = storage_path("app/{$rutaCarpeta}/{$formacion->archivo}");
                if (file_exists($rutaArchivo)) {
                    $extension = pathinfo($formacion->archivo, PATHINFO_EXTENSION);
                    $nombreArchivo = Str::slug($formacion->tipo_nombre, '_');
                    if ($formacion->nombre) {
                        $nombreArchivo .= '_' . Str::slug($formacion->nombre, '_');
                    }
                    $zip->addFile($rutaArchivo, "03_Formacion/{$nombreArchivo}.{$extension}");
                    $archivosAgregados++;
                }
            }
        }

        // 5. Documentos post-incorporación
        foreach ($incorporacion->documentos as $documento) {
            if ($documento->archivo && $documento->completado) {
                $rutaArchivo = storage_path("app/{$rutaCarpeta}/{$documento->archivo}");
                if (file_exists($rutaArchivo)) {
                    $extension = pathinfo($documento->archivo, PATHINFO_EXTENSION);
                    $nombreDoc = Incorporacion::DOCUMENTOS_POST[$documento->tipo] ?? $documento->tipo;
                    $nombreArchivo = Str::slug($nombreDoc, '_');
                    $zip->addFile($rutaArchivo, "04_Documentos_Empresa/{$nombreArchivo}.{$extension}");
                    $archivosAgregados++;
                }
            }
        }

        // 6. Generar fichero Excel con información del trabajador
        $excelPath = $this->generarFichaExcel($incorporacion);
        if ($excelPath && file_exists($excelPath)) {
            $zip->addFile($excelPath, "00_FICHA_TRABAJADOR.xlsx");
        }

        $zip->close();

        // Eliminar archivo Excel temporal
        if (isset($excelPath) && file_exists($excelPath)) {
            unlink($excelPath);
        }

        if ($archivosAgregados === 0) {
            unlink($zipPath);
            return back()->with('error', 'No hay documentos disponibles para descargar');
        }

        // Descargar y luego eliminar el archivo temporal
        return response()->download($zipPath, $nombreZip)->deleteFileAfterSend(true);
    }

    /**
     * Descargar contrato del usuario autenticado
     */
    public function descargarMiContrato()
    {
        $user = auth()->user();
        $incorporacion = $user->incorporacion;

        if (!$incorporacion) {
            return back()->with('error', 'No se encontró tu incorporación');
        }

        $documento = $incorporacion->documentos()->where('tipo', 'contrato_trabajo')->first();

        if (!$documento || !$documento->archivo) {
            return back()->with('error', 'No tienes contrato disponible para descargar');
        }

        $carpetaUsuario = $this->obtenerCarpetaUsuario($incorporacion);
        $rutaArchivo = "private/documentos/{$carpetaUsuario}/{$documento->archivo}";

        if (!Storage::exists($rutaArchivo)) {
            return back()->with('error', 'El archivo no existe');
        }

        return Storage::download($rutaArchivo, 'contrato_' . $user->dni . '.pdf');
    }

    /**
     * Generar ficha de información del trabajador en Excel
     */
    private function generarFichaExcel(Incorporacion $incorporacion): ?string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Ficha Trabajador');

            // Estilos
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ];

            $sectionStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B5563']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ];

            $labelStyle = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            ];

            $borderStyle = [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                ],
            ];

            // Configurar anchos de columna
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(40);

            $row = 1;

            // Título principal
            $sheet->setCellValue("A{$row}", 'FICHA DEL TRABAJADOR');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($headerStyle);
            $sheet->getRowDimension($row)->setRowHeight(30);
            $row += 2;

            // ===== DATOS PERSONALES =====
            $sheet->setCellValue("A{$row}", 'DATOS PERSONALES');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($sectionStyle);
            $row++;

            $datosPersonales = [
                'Nombre completo' => ['valor' => trim($incorporacion->name . ' ' . $incorporacion->primer_apellido . ' ' . ($incorporacion->segundo_apellido ?? '')), 'texto' => false],
                'DNI/NIE' => ['valor' => $incorporacion->dni ?? 'No disponible', 'texto' => true],
                'N. Afiliación SS' => ['valor' => $incorporacion->numero_afiliacion_ss ?? 'No disponible', 'texto' => true],
                'Email' => ['valor' => $incorporacion->email ?? 'No disponible', 'texto' => false],
                'Teléfono' => ['valor' => $incorporacion->telefono ?? $incorporacion->telefono_provisional ?? 'No disponible', 'texto' => true],
            ];

            foreach ($datosPersonales as $label => $data) {
                $sheet->setCellValue("A{$row}", $label);
                // Forzar formato texto para números largos (DNI, teléfono, N. Afiliación)
                if ($data['texto']) {
                    $sheet->setCellValueExplicit("B{$row}", $data['valor'], DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue("B{$row}", $data['valor']);
                }
                $sheet->getStyle("A{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($borderStyle);
                $row++;
            }

            $row++;

            // ===== INFORMACIÓN LABORAL =====
            $sheet->setCellValue("A{$row}", 'INFORMACIÓN LABORAL');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($sectionStyle);
            $row++;

            $datosLaborales = [
                'Empresa' => $incorporacion->empresa_nombre,
                'Puesto' => $incorporacion->puesto ?? 'No especificado',
                'Estado' => $incorporacion->estado_badge['texto'] ?? $incorporacion->estado,
            ];

            foreach ($datosLaborales as $label => $valor) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->setCellValue("B{$row}", $valor);
                $sheet->getStyle("A{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($borderStyle);
                $row++;
            }

            $row++;

            // ===== FECHAS =====
            $sheet->setCellValue("A{$row}", 'FECHAS');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($sectionStyle);
            $row++;

            $fechas = [
                'Fecha de creación' => $incorporacion->created_at?->format('d/m/Y H:i') ?? '-',
                'Datos completados' => $incorporacion->datos_completados_at?->format('d/m/Y H:i') ?? 'Pendiente',
            ];

            foreach ($fechas as $label => $valor) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->setCellValue("B{$row}", $valor);
                $sheet->getStyle("A{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($borderStyle);
                $row++;
            }

            $row++;

            // ===== DOCUMENTOS =====
            $sheet->setCellValue("A{$row}", 'DOCUMENTOS INCLUIDOS');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($sectionStyle);
            $row++;

            $documentos = [];
            if ($incorporacion->dni_frontal)
                $documentos[] = ['DNI Frontal', '✓'];
            if ($incorporacion->dni_trasero)
                $documentos[] = ['DNI Trasero', '✓'];
            if ($incorporacion->certificado_bancario)
                $documentos[] = ['Certificado Bancario', '✓'];

            foreach ($incorporacion->formaciones as $formacion) {
                $nombre = $formacion->tipo_nombre;
                if ($formacion->nombre)
                    $nombre .= " - " . $formacion->nombre;
                $documentos[] = [$nombre, '✓'];
            }

            foreach ($incorporacion->documentos as $documento) {
                if ($documento->completado) {
                    $nombre = Incorporacion::DOCUMENTOS_POST[$documento->tipo] ?? $documento->tipo;
                    $documentos[] = [$nombre, '✓'];
                }
            }

            foreach ($documentos as $doc) {
                $sheet->setCellValue("A{$row}", $doc[0]);
                $sheet->setCellValue("B{$row}", $doc[1]);
                $sheet->getStyle("A{$row}")->applyFromArray($labelStyle);
                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($borderStyle);
                $sheet->getStyle("B{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('00AA00'));
                $row++;
            }

            $row += 2;

            // Pie de página
            $sheet->setCellValue("A{$row}", 'Generado el: ' . now()->format('d/m/Y H:i:s'));
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('888888'));

            // Guardar archivo temporal
            $tempPath = storage_path('app/temp/ficha_' . uniqid() . '.xlsx');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return $tempPath;

        } catch (\Exception $e) {
            \Log::error('Error generando Excel: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enviar email al CEO cuando RRHH aprueba una incorporación
     */
    private function enviarEmailACeo(Incorporacion $incorporacion, $ceos): void
    {
        try {
            // Obtener todas las incorporaciones pendientes de aprobación CEO
            $incorporacionesPendientes = Incorporacion::where('aprobado_rrhh', true)
                ->where(function ($query) {
                    $query->where('aprobado_ceo', false)
                        ->orWhereNull('aprobado_ceo');
                })
                ->whereNotIn('estado', ['cancelada', 'completada'])
                ->orderBy('aprobado_rrhh_at', 'desc')
                ->get();

            $aprobadoPor = auth()->user()->nombre_completo ?? 'RRHH';

            foreach ($ceos as $ceo) {
                Mail::to($ceo->email)
                    ->send(new IncorporacionPendienteCeoMail(
                        $incorporacion,
                        $incorporacionesPendientes,
                        $aprobadoPor
                    ));
            }

            \Log::info('Email de incorporación pendiente enviado al CEO', [
                'incorporacion_id' => $incorporacion->id,
                'ceos_notificados' => $ceos->pluck('email')->toArray(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error enviando email al CEO: ' . $e->getMessage(), [
                'incorporacion_id' => $incorporacion->id,
            ]);
        }
    }

    /**
     * Enviar email a usuarios de RRHH cuando el CEO aprueba una incorporación
     */
    private function enviarEmailARrhh(Incorporacion $incorporacion, $usuariosRrhh): void
    {
        try {
            $aprobadoPor = auth()->user()->nombre_completo ?? 'CEO';

            // Cargar relaciones necesarias
            $incorporacion->load(['aprobadorRrhh']);

            foreach ($usuariosRrhh as $usuario) {
                Mail::to($usuario->email)
                    ->send(new IncorporacionAprobadaCeoMail($incorporacion, $aprobadoPor));
            }

            \Log::info('Email de incorporación aprobada enviado a RRHH', [
                'incorporacion_id' => $incorporacion->id,
                'rrhh_notificados' => $usuariosRrhh->pluck('email')->toArray(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error enviando email a RRHH: ' . $e->getMessage(), [
                'incorporacion_id' => $incorporacion->id,
            ]);
        }
    }
}
