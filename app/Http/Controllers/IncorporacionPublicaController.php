<?php

namespace App\Http\Controllers;

use App\Models\Incorporacion;
use App\Models\IncorporacionFormacion;
use App\Models\IncorporacionLog;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\IncorporacionCompletada;
use App\Models\Departamento;

class IncorporacionPublicaController extends Controller
{
    public function show($token)
    {
        $incorporacion = Incorporacion::where('token', $token)->firstOrFail();

        // Si ya completó los datos, mostrar página de confirmación
        if ($incorporacion->datos_completados_at) {
            return view('incorporaciones.publica-completada', compact('incorporacion'));
        }

        // Si está cancelada, mostrar mensaje
        if ($incorporacion->estado === Incorporacion::ESTADO_CANCELADA) {
            return view('incorporaciones.publica-cancelada', compact('incorporacion'));
        }

        // Determinar tipos de formación según empresa
        $tiposFormacion = $incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR
            ? IncorporacionFormacion::TIPOS_HPR
            : IncorporacionFormacion::TIPOS_HIERROS;

        // Obtener archivos temporales guardados (si hubo error previo)
        $sessionKey = "incorporacion_tmp_{$token}";
        $archivosTmp = [
            'dni_frontal' => session()->get("{$sessionKey}.dni_frontal"),
            'dni_trasero' => session()->get("{$sessionKey}.dni_trasero"),
            'certificado_bancario' => session()->get("{$sessionKey}.certificado_bancario"),
            'formacion_curso_20h' => session()->get("{$sessionKey}.formacion_curso_20h"),
            'formacion_curso_6h' => session()->get("{$sessionKey}.formacion_curso_6h"),
            'formacion_generica' => session()->get("{$sessionKey}.formacion_generica"),
            'formacion_especifica' => session()->get("{$sessionKey}.formacion_especifica"),
            'formacion_otros' => session()->get("{$sessionKey}.formacion_otros", []),
        ];

        return view('incorporaciones.publica', compact('incorporacion', 'tiposFormacion', 'archivosTmp'));
    }

    public function store(Request $request, $token)
    {
        $incorporacion = Incorporacion::where('token', $token)->firstOrFail();

        // Verificar que no esté ya completada
        if ($incorporacion->datos_completados_at) {
            return redirect()->route('incorporacion.publica', $token)
                ->with('error', 'Este formulario ya fue completado anteriormente.');
        }

        // Guardar archivos temporalmente antes de validar para no perderlos en caso de error
        $archivosTmp = $this->guardarArchivosTemporales($request, $token);

        // Limpiar espacios del teléfono y número de afiliación antes de validar
        $request->merge([
            'telefono' => preg_replace('/\s+/', '', $request->telefono ?? ''),
            'numero_afiliacion_ss' => preg_replace('/\s+/', '', $request->numero_afiliacion_ss ?? ''),
        ]);

        // Validación base - con imágenes o PDF del DNI
        // Los archivos son opcionales si ya están guardados temporalmente
        $tieneDniFrontalTmp = isset($archivosTmp['dni_frontal']) || session()->has("incorporacion_tmp_{$token}.dni_frontal");
        $tieneDniTraseroTmp = isset($archivosTmp['dni_trasero']) || session()->has("incorporacion_tmp_{$token}.dni_trasero");
        $tieneCertBancarioTmp = isset($archivosTmp['certificado_bancario']) || session()->has("incorporacion_tmp_{$token}.certificado_bancario");

        $rules = [
            'dni_frontal' => ($tieneDniFrontalTmp ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf|max:15360',
            'dni_trasero' => ($tieneDniTraseroTmp ? 'nullable' : 'required') . '|file|mimes:jpg,jpeg,png,pdf|max:15360',
            'dni' => ['required', 'string', 'size:9', 'regex:/^([0-9]{8}[A-Z]|[XYZ][0-9]{7}[A-Z])$/i'],
            'nombre_dni' => 'required|string|max:100',
            'primer_apellido_dni' => 'required|string|max:100',
            'segundo_apellido_dni' => 'nullable|string|max:100',
            'numero_afiliacion_ss' => ['required', 'string', 'size:12', 'regex:/^[0-9]{12}$/'],
            'email' => 'required|email|max:255',
            'telefono' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'certificado_bancario' => ($tieneCertBancarioTmp ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ];

        // Validación de formación según empresa (todos opcionales)
        if ($incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR) {
            $rules['formacion_curso_20h'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360';
            $rules['formacion_curso_6h'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360';
            $rules['formacion_otros'] = 'nullable|array';
            $rules['formacion_otros.*'] = 'file|mimes:pdf,jpg,jpeg,png|max:15360';
            $rules['formacion_otros_nombres'] = 'nullable|array';
            $rules['formacion_otros_nombres.*'] = 'nullable|string|max:255';
        } else {
            $rules['formacion_generica'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360';
            $rules['formacion_especifica'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360';
        }

        $messages = [
            'dni_frontal.required' => 'El archivo del frente del DNI/NIE es obligatorio.',
            'dni_frontal.file' => 'El frente del DNI/NIE debe ser un archivo.',
            'dni_frontal.mimes' => 'El frente del DNI/NIE debe ser JPG, PNG o PDF.',
            'dni_frontal.max' => 'El archivo del frente del DNI/NIE no puede superar 15MB.',
            'dni_trasero.required' => 'El archivo del reverso del DNI/NIE es obligatorio.',
            'dni_trasero.file' => 'El reverso del DNI/NIE debe ser un archivo.',
            'dni_trasero.mimes' => 'El reverso del DNI/NIE debe ser JPG, PNG o PDF.',
            'dni_trasero.max' => 'El archivo del reverso del DNI/NIE no puede superar 15MB.',
            'dni.required' => 'El DNI/NIE es obligatorio.',
            'dni.size' => 'El DNI/NIE debe tener exactamente 9 caracteres.',
            'dni.regex' => 'El formato del DNI/NIE no es válido (DNI: 8 números + letra, NIE: X/Y/Z + 7 números + letra).',
            'nombre_dni.required' => 'El nombre es obligatorio.',
            'nombre_dni.max' => 'El nombre no puede superar 100 caracteres.',
            'primer_apellido_dni.required' => 'El primer apellido es obligatorio.',
            'primer_apellido_dni.max' => 'El primer apellido no puede superar 100 caracteres.',
            'segundo_apellido_dni.max' => 'El segundo apellido no puede superar 100 caracteres.',
            'numero_afiliacion_ss.required' => 'El número de afiliación es obligatorio.',
            'numero_afiliacion_ss.size' => 'El número de afiliación debe tener exactamente 12 dígitos.',
            'numero_afiliacion_ss.regex' => 'El número de afiliación debe contener solo números.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe tener 9 dígitos.',
            'certificado_bancario.required' => 'El certificado bancario es obligatorio.',
            'certificado_bancario.file' => 'El certificado bancario debe ser un archivo.',
            'certificado_bancario.mimes' => 'El certificado bancario debe ser PDF, JPG o PNG.',
            'certificado_bancario.max' => 'El certificado bancario no puede superar 15MB.',
            'formacion_curso_20h.mimes' => 'El curso de 20H debe ser PDF, JPG o PNG.',
            'formacion_curso_20h.max' => 'El curso de 20H no puede superar 15MB.',
            'formacion_curso_6h.mimes' => 'El curso de 6H Ferralla debe ser PDF, JPG o PNG.',
            'formacion_curso_6h.max' => 'El curso de 6H Ferralla no puede superar 15MB.',
            'formacion_otros.*.mimes' => 'Los archivos de otros cursos deben ser PDF, JPG o PNG.',
            'formacion_otros.*.max' => 'Los archivos de otros cursos no pueden superar 15MB.',
            'formacion_otros_nombres.*.string' => 'El nombre del curso debe ser texto.',
            'formacion_otros_nombres.*.max' => 'El nombre del curso no puede superar 255 caracteres.',
            'formacion_generica.mimes' => 'La formación genérica debe ser PDF, JPG o PNG.',
            'formacion_generica.max' => 'La formación genérica no puede superar 15MB.',
            'formacion_especifica.mimes' => 'La formación específica debe ser PDF, JPG o PNG.',
            'formacion_especifica.max' => 'La formación específica no puede superar 15MB.',
        ];

        $validated = $request->validate($rules, $messages);

        $sessionKey = "incorporacion_tmp_{$token}";

        // DNI en mayúsculas
        $validated['dni'] = strtoupper($validated['dni']);

        // Usar los campos del formulario
        $validated['nombre_final'] = $validated['nombre_dni'];
        $validated['primer_apellido_final'] = $validated['primer_apellido_dni'];
        $validated['segundo_apellido_final'] = $validated['segundo_apellido_dni'] ?? null;

        Log::info('Incorporación - Datos procesados', [
            'dni' => $validated['dni'],
            'nombre' => $validated['nombre_dni'],
            'primer_apellido' => $validated['primer_apellido_dni'],
            'segundo_apellido' => $validated['segundo_apellido_dni'] ?? null,
        ]);

        DB::beginTransaction();

        try {
            // Carpeta temporal basada en la incorporación (el usuario se crea cuando el CEO aprueba)
            $carpetaUsuario = $this->generarNombreCarpetaIncorporacion($incorporacion, $validated);

            // Guardar imágenes del DNI (desde request o desde temporal)
            $nombreDniFrontal = $this->guardarArchivoFinal(
                $request->file('dni_frontal'),
                session()->get("{$sessionKey}.dni_frontal"),
                'dni_frontal',
                $carpetaUsuario
            );

            $nombreDniTrasero = $this->guardarArchivoFinal(
                $request->file('dni_trasero'),
                session()->get("{$sessionKey}.dni_trasero"),
                'dni_trasero',
                $carpetaUsuario
            );

            // Guardar certificado bancario
            $nombreCert = $this->guardarArchivoFinal(
                $request->file('certificado_bancario'),
                session()->get("{$sessionKey}.certificado_bancario"),
                'certificado_bancario',
                $carpetaUsuario
            );

            // Actualizar datos personales (el usuario se crea cuando el CEO aprueba)
            $datosActualizacion = [
                'dni' => strtoupper($validated['dni']),
                'numero_afiliacion_ss' => $validated['numero_afiliacion_ss'],
                'email' => strtolower($validated['email']),
                'telefono' => $validated['telefono'],
                'certificado_bancario' => $nombreCert,
                'datos_completados_at' => now(),
                'estado' => Incorporacion::ESTADO_DATOS_RECIBIDOS,
            ];

            // Actualizar nombre y apellidos con los datos del formulario (verificados por el usuario)
            $datosActualizacion['name'] = ucwords(strtolower($validated['nombre_final']));
            $datosActualizacion['primer_apellido'] = ucwords(strtolower($validated['primer_apellido_final']));
            $datosActualizacion['segundo_apellido'] = !empty($validated['segundo_apellido_final'])
                ? ucwords(strtolower($validated['segundo_apellido_final']))
                : null;

            $incorporacion->update($datosActualizacion);

            // Guardar documentos de formación (desde request o desde temporal)
            if ($incorporacion->empresa_destino === Incorporacion::EMPRESA_HPR) {
                $this->guardarFormacionConTemporal($incorporacion, $request->file('formacion_curso_20h'), session()->get("{$sessionKey}.formacion_curso_20h"), 'curso_20h_generico', null, $carpetaUsuario);
                $this->guardarFormacionConTemporal($incorporacion, $request->file('formacion_curso_6h'), session()->get("{$sessionKey}.formacion_curso_6h"), 'curso_6h_ferralla', null, $carpetaUsuario);

                // Otros cursos (múltiples)
                $formacionOtrosTmp = session()->get("{$sessionKey}.formacion_otros", []);
                if ($request->hasFile('formacion_otros') || !empty($formacionOtrosTmp)) {
                    $nombres = $request->input('formacion_otros_nombres', []);
                    $archivos = $request->file('formacion_otros') ?? [];

                    // Primero procesar archivos nuevos del request
                    foreach ($archivos as $index => $archivo) {
                        $nombre = $nombres[$index] ?? 'Otro curso ' . ($index + 1);
                        $this->guardarFormacion($incorporacion, $archivo, 'otros_cursos', $nombre, $carpetaUsuario);
                    }

                    // Luego procesar archivos temporales que no fueron reemplazados
                    foreach ($formacionOtrosTmp as $index => $datosTmp) {
                        if (!isset($archivos[$index]) && isset($datosTmp['ruta']) && Storage::exists($datosTmp['ruta'])) {
                            $nombre = $nombres[$index] ?? 'Otro curso ' . ($index + 1);
                            $this->guardarFormacionDesdeTemporal($incorporacion, $datosTmp, 'otros_cursos', $nombre, $carpetaUsuario);
                        }
                    }
                }
            } else {
                $this->guardarFormacionConTemporal($incorporacion, $request->file('formacion_generica'), session()->get("{$sessionKey}.formacion_generica"), 'formacion_generica_puesto', null, $carpetaUsuario);
                $this->guardarFormacionConTemporal($incorporacion, $request->file('formacion_especifica'), session()->get("{$sessionKey}.formacion_especifica"), 'formacion_especifica_puesto', null, $carpetaUsuario);
            }

            // Registrar log
            $incorporacion->registrarLog(
                IncorporacionLog::ACCION_DATOS_COMPLETADOS,
                'El candidato ha completado el formulario de incorporación. Usuario creado automáticamente.',
                null,
                [
                    'dni' => $incorporacion->dni,
                    'email' => $incorporacion->email,
                    'telefono' => $incorporacion->telefono,
                    'user_id' => $usuario->id,
                ]
            );

            DB::commit();

            // Limpiar archivos temporales después del éxito
            $this->limpiarArchivosTemporales($token);

            // Enviar correo a departamentos de programador y recursos humanos
            $this->notificarDepartamentos($incorporacion);

            return redirect()->route('incorporacion.publica', $token)
                ->with('success', 'Datos enviados correctamente. Gracias por completar el formulario.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar incorporación: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());

            $mensajeError = 'Error: ' . $e->getMessage();

            // En desarrollo mostrar más detalles
            if (config('app.debug')) {
                $mensajeError .= ' (Línea: ' . $e->getLine() . ' en ' . basename($e->getFile()) . ')';
            }

            return redirect()->route('incorporacion.publica', $token)
                ->with('error', $mensajeError)
                ->withInput();
        }
    }

    /**
     * Crear usuario automáticamente a partir de los datos de incorporación
     * Prioriza los datos extraídos del DNI por OCR sobre los datos iniciales
     */
    private function crearUsuario(Incorporacion $incorporacion, array $datos): User
    {
        $dni = strtoupper($datos['dni']);

        // Verificar si ya existe un usuario con este DNI (sin el scope de activos)
        $usuarioExistente = User::withoutGlobalScopes()->where('dni', $dni)->first();
        if ($usuarioExistente) {
            // Si existe pero está inactivo, reactivarlo
            if ($usuarioExistente->estado !== 'activo') {
                $usuarioExistente->update(['estado' => 'activo']);
            }
            return $usuarioExistente;
        }

        // Obtener empresa_id basado en empresa_destino
        $empresaId = $this->obtenerEmpresaId($incorporacion->empresa_destino);

        // Obtener categoría por defecto (primera disponible)
        $categoriaId = Categoria::first()?->id ?? 1;

        // Contraseña: DNI sin la letra (los 8 primeros caracteres numéricos)
        $password = preg_replace('/[^0-9]/', '', $dni);

        // Usar los datos del formulario (el usuario los ha verificado/corregido)
        $nombre = $datos['nombre_final'];
        $primerApellido = $datos['primer_apellido_final'];
        $segundoApellido = $datos['segundo_apellido_final'];

        Log::info('Creando usuario con datos del formulario', [
            'nombre' => $nombre,
            'primer_apellido' => $primerApellido,
            'segundo_apellido' => $segundoApellido,
        ]);

        return User::create([
            'name' => ucwords(strtolower($nombre)),
            'primer_apellido' => ucwords(strtolower($primerApellido)),
            'segundo_apellido' => $segundoApellido ? ucwords(strtolower($segundoApellido)) : null,
            'dni' => $dni,
            'email' => strtolower($datos['email']),
            'movil_personal' => $datos['telefono'],
            'password' => Hash::make($password),
            'empresa_id' => $empresaId,
            'categoria_id' => $categoriaId,
            'estado' => 'activo',
            // rol y turno se dejan null para que se asignen manualmente desde users
        ]);
    }

    /**
     * Obtener el ID de empresa basado en el valor de empresa_destino
     */
    private function obtenerEmpresaId(string $empresaDestino): int
    {
        if ($empresaDestino === Incorporacion::EMPRESA_HPR) {
            return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hpr servicios%'])->value('id') ?? 1;
        }

        return Empresa::whereRaw("LOWER(nombre) LIKE ?", ['%hierros paco reyes%'])->value('id') ?? 1;
    }

    /**
     * Generar nombre de carpeta basada en la incorporación (antes de crear el usuario)
     */
    private function generarNombreCarpetaIncorporacion(Incorporacion $incorporacion, array $datos): string
    {
        $nombre = Str::slug($datos['nombre_final'], '_');
        $apellido = Str::slug($datos['primer_apellido_final'], '_');

        return "{$nombre}_{$apellido}_inc{$incorporacion->id}";
    }

    private function guardarFormacion(Incorporacion $incorporacion, $archivo, $tipo, $nombre = null, $carpetaUsuario = null)
    {
        if (!$archivo) return;

        $nombreArchivo = $tipo . '_' . time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();

        // Guardar en carpeta privada del usuario
        if ($carpetaUsuario) {
            $archivo->storeAs("private/documentos/{$carpetaUsuario}", $nombreArchivo);
        } else {
            // Fallback al método anterior (no debería usarse)
            $archivo->storeAs('incorporaciones', $nombreArchivo, 'public');
        }

        $incorporacion->formaciones()->create([
            'tipo' => $tipo,
            'nombre' => $nombre,
            'archivo' => $nombreArchivo,
        ]);
    }

    /**
     * Notificar a los departamentos de programador y recursos humanos
     */
    private function notificarDepartamentos(Incorporacion $incorporacion): void
    {
        try {
            // Obtener emails de usuarios en departamentos de programador y recursos humanos
            $emails = User::whereHas('departamentos', function ($query) {
                $query->whereIn('nombre', ['programador', 'recursos humanos', 'Programador', 'Recursos Humanos', 'RRHH', 'rrhh']);
            })
            ->where('estado', 'activo')
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->toArray();

            if (empty($emails)) {
                Log::warning('No se encontraron destinatarios para notificar la incorporación completada.');
                return;
            }

            Mail::to($emails)->send(new IncorporacionCompletada($incorporacion));

            Log::info('Notificación de incorporación enviada a: ' . implode(', ', $emails));

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación de incorporación: ' . $e->getMessage());
        }
    }

    /**
     * Guardar archivos temporalmente para no perderlos en caso de error de validación
     */
    private function guardarArchivosTemporales(Request $request, string $token): array
    {
        $archivosTmp = [];
        $sessionKey = "incorporacion_tmp_{$token}";
        $camposArchivo = [
            'dni_frontal',
            'dni_trasero',
            'certificado_bancario',
            'formacion_curso_20h',
            'formacion_curso_6h',
            'formacion_generica',
            'formacion_especifica',
        ];

        foreach ($camposArchivo as $campo) {
            if ($request->hasFile($campo) && $request->file($campo)->isValid()) {
                $archivo = $request->file($campo);
                $nombreTmp = $campo . '_' . time() . '_' . Str::random(8) . '.' . $archivo->getClientOriginalExtension();
                $rutaTmp = $archivo->storeAs("temp/incorporaciones/{$token}", $nombreTmp);

                $archivosTmp[$campo] = [
                    'ruta' => $rutaTmp,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'extension' => $archivo->getClientOriginalExtension(),
                    'mime' => $archivo->getMimeType(),
                ];

                // Guardar en sesión
                session()->put("{$sessionKey}.{$campo}", $archivosTmp[$campo]);
            }
        }

        // Manejar archivos múltiples (formacion_otros)
        if ($request->hasFile('formacion_otros')) {
            $archivosTmp['formacion_otros'] = [];
            foreach ($request->file('formacion_otros') as $index => $archivo) {
                if ($archivo && $archivo->isValid()) {
                    $nombreTmp = "formacion_otros_{$index}_" . time() . '_' . Str::random(8) . '.' . $archivo->getClientOriginalExtension();
                    $rutaTmp = $archivo->storeAs("temp/incorporaciones/{$token}", $nombreTmp);

                    $archivosTmp['formacion_otros'][$index] = [
                        'ruta' => $rutaTmp,
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'extension' => $archivo->getClientOriginalExtension(),
                        'mime' => $archivo->getMimeType(),
                    ];
                }
            }
            if (!empty($archivosTmp['formacion_otros'])) {
                session()->put("{$sessionKey}.formacion_otros", $archivosTmp['formacion_otros']);
            }
        }

        return $archivosTmp;
    }

    /**
     * Obtener archivo desde temporal o desde request
     * Retorna la ruta del archivo temporal si existe, o null si debe usarse el del request
     */
    private function obtenerArchivoTemporal(string $token, string $campo): ?string
    {
        $sessionKey = "incorporacion_tmp_{$token}.{$campo}";
        $datos = session()->get($sessionKey);

        if ($datos && isset($datos['ruta']) && Storage::exists($datos['ruta'])) {
            return $datos['ruta'];
        }

        return null;
    }

    /**
     * Limpiar archivos temporales después de procesar exitosamente
     */
    private function limpiarArchivosTemporales(string $token): void
    {
        $sessionKey = "incorporacion_tmp_{$token}";

        // Eliminar archivos del storage
        $directorio = "temp/incorporaciones/{$token}";
        if (Storage::exists($directorio)) {
            Storage::deleteDirectory($directorio);
        }

        // Limpiar sesión
        session()->forget($sessionKey);
    }

    /**
     * Guardar archivo final desde request o desde temporal
     */
    private function guardarArchivoFinal($archivoRequest, ?array $datosTmp, string $prefijo, string $carpetaUsuario): ?string
    {
        // Si hay archivo nuevo en el request, usarlo
        if ($archivoRequest && $archivoRequest->isValid()) {
            $nombreArchivo = $prefijo . '_' . time() . '.' . $archivoRequest->getClientOriginalExtension();
            $archivoRequest->storeAs("private/documentos/{$carpetaUsuario}", $nombreArchivo);
            return $nombreArchivo;
        }

        // Si hay archivo temporal, moverlo a la ubicación final
        if ($datosTmp && isset($datosTmp['ruta']) && Storage::exists($datosTmp['ruta'])) {
            $nombreArchivo = $prefijo . '_' . time() . '.' . $datosTmp['extension'];
            Storage::move($datosTmp['ruta'], "private/documentos/{$carpetaUsuario}/{$nombreArchivo}");
            return $nombreArchivo;
        }

        return null;
    }

    /**
     * Guardar formación con soporte para archivos temporales
     */
    private function guardarFormacionConTemporal(Incorporacion $incorporacion, $archivoRequest, ?array $datosTmp, string $tipo, ?string $nombre, string $carpetaUsuario): void
    {
        // Si hay archivo nuevo en el request, usarlo
        if ($archivoRequest && $archivoRequest->isValid()) {
            $this->guardarFormacion($incorporacion, $archivoRequest, $tipo, $nombre, $carpetaUsuario);
            return;
        }

        // Si hay archivo temporal, usarlo
        if ($datosTmp && isset($datosTmp['ruta']) && Storage::exists($datosTmp['ruta'])) {
            $this->guardarFormacionDesdeTemporal($incorporacion, $datosTmp, $tipo, $nombre, $carpetaUsuario);
        }
    }

    /**
     * Guardar formación desde archivo temporal
     */
    private function guardarFormacionDesdeTemporal(Incorporacion $incorporacion, array $datosTmp, string $tipo, ?string $nombre, string $carpetaUsuario): void
    {
        if (!isset($datosTmp['ruta']) || !Storage::exists($datosTmp['ruta'])) {
            return;
        }

        $nombreArchivo = $tipo . '_' . time() . '_' . Str::random(5) . '.' . $datosTmp['extension'];

        // Mover archivo temporal a ubicación final
        Storage::move($datosTmp['ruta'], "private/documentos/{$carpetaUsuario}/{$nombreArchivo}");

        // Crear registro de formación
        $incorporacion->formaciones()->create([
            'tipo' => $tipo,
            'nombre' => $nombre,
            'archivo' => $nombreArchivo,
        ]);
    }
}
