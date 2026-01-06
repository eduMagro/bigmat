<?php

namespace App\Livewire;

use App\Models\AsignacionTurno;
use App\Models\User;
use App\Services\AlertaService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SubirJustificante extends Component
{
    use WithFileUploads;

    public $userId;
    public $archivo;
    public $fechaSeleccionada;
    public $textoExtraido = '';
    public $fechaDetectada = null;
    public $horasDetectadas = null;
    public $observaciones = '';
    public $procesando = false;
    public $mostrarResultados = false;
    public $error = '';
    public $asignacionesDisponibles = [];
    public $asignacionSeleccionada = null;
    public $fechaManual = null;
    public $ocrDisponible = false;
    public $justificantesExistentes = [];
    public $soloLectura = false;

    protected $messages = [
        'archivo.required' => 'Debes seleccionar un archivo.',
        'archivo.mimes' => 'El archivo debe ser PDF, JPG, JPEG o PNG.',
        'archivo.max' => 'El archivo no puede superar los 10MB.',
        'asignacionSeleccionada.required' => 'Debes seleccionar una asignación de turno.',
        'fechaManual.required' => 'Debes seleccionar una fecha.',
        'fechaManual.date' => 'La fecha no es válida.',
        'fechaManual.before_or_equal' => 'La fecha no puede ser futura.',
        'horasDetectadas.required' => 'Debes indicar las horas justificadas.',
        'horasDetectadas.numeric' => 'Las horas deben ser un número.',
        'horasDetectadas.min' => 'Las horas no pueden ser negativas.',
        'horasDetectadas.max' => 'Las horas no pueden superar 24.',
    ];

    protected function rules()
    {
        $rules = [
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'horasDetectadas' => 'required|numeric|min:0|max:24',
        ];

        // Si hay asignaciones disponibles, requerir selección; si no, requerir fecha manual
        if (count($this->asignacionesDisponibles) > 0) {
            $rules['asignacionSeleccionada'] = 'required';
        } else {
            $rules['fechaManual'] = 'required|date|before_or_equal:today';
        }

        return $rules;
    }

    public function mount($userId)
    {
        $this->userId = $userId;

        // Si es oficina viendo ficha de otro usuario, modo solo lectura
        $auth = auth()->user();
        $this->soloLectura = $auth->rol === 'oficina' && $auth->id != $userId;

        $this->cargarAsignacionesDisponibles();
        $this->cargarJustificantesExistentes();
        $this->ocrDisponible = $this->verificarOCRDisponible();
    }

    public function cargarJustificantesExistentes()
    {
        // Cargar asignaciones con justificante de los últimos 90 días
        $this->justificantesExistentes = AsignacionTurno::where('user_id', $this->userId)
            ->whereNotNull('justificante_ruta')
            ->where('fecha', '>=', now()->subDays(90))
            ->orderBy('fecha', 'desc')
            ->with(['turno', 'obra'])
            ->get()
            ->map(function ($asignacion) {
                return [
                    'id' => $asignacion->id,
                    'fecha' => $asignacion->fecha->format('Y-m-d'),
                    'fecha_formateada' => $asignacion->fecha->format('d/m/Y'),
                    'turno' => $asignacion->turno->nombre ?? 'Sin turno',
                    'obra' => $asignacion->obra->obra ?? 'Sin obra',
                    'estado' => $asignacion->estado,
                    'horas_justificadas' => $asignacion->horas_justificadas,
                    'observaciones' => $asignacion->justificante_observaciones,
                    'ruta' => $asignacion->justificante_ruta,
                    'subido_at' => $asignacion->justificante_subido_at?->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    public function cargarAsignacionesDisponibles()
    {
        // Cargar asignaciones de los últimos 60 días sin justificante
        $this->asignacionesDisponibles = AsignacionTurno::where('user_id', $this->userId)
            ->whereNull('justificante_ruta')
            ->where('fecha', '>=', now()->subDays(60))
            ->orderBy('fecha', 'desc')
            ->with(['turno', 'obra'])
            ->get()
            ->map(function ($asignacion) {
                return [
                    'id' => (string) $asignacion->id,
                    'fecha' => $asignacion->fecha->format('Y-m-d'),
                    'fecha_formateada' => $asignacion->fecha->format('d/m/Y'),
                    'turno' => $asignacion->turno->nombre ?? 'Sin turno',
                    'obra' => $asignacion->obra->obra ?? 'Sin obra',
                    'estado' => $asignacion->estado,
                ];
            })
            ->toArray();
    }

    protected function verificarOCRDisponible()
    {
        // Verificar si el paquete de Tesseract está instalado
        if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
            return false;
        }

        // Verificar si Tesseract está instalado en el sistema
        return $this->detectarTesseract() !== null;
    }

    public function updatedArchivo()
    {
        $this->validate(['archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);
        $this->procesarArchivo();
    }

    public function procesarArchivo()
    {
        $this->procesando = true;
        $this->error = '';
        $this->textoExtraido = '';
        $this->fechaDetectada = null;
        $this->horasDetectadas = null; // El usuario introduce las horas manualmente

        try {
            // Si OCR está disponible, intentar extraer texto
            if ($this->ocrDisponible) {
                $extension = $this->archivo->getClientOriginalExtension();
                $tempPath = $this->archivo->getRealPath();

                // Si es PDF, convertir primera página a imagen
                if (strtolower($extension) === 'pdf') {
                    $tempPath = $this->convertirPdfAImagen($tempPath);
                }

                // Ejecutar OCR con Tesseract
                $this->textoExtraido = $this->ejecutarOCR($tempPath);

                // Analizar el texto para extraer fecha y horas
                $this->analizarTexto($this->textoExtraido);
            }

            $this->mostrarResultados = true;

        } catch (\Exception $e) {
            // Si falla el OCR, permitir continuar en modo manual
            $this->mostrarResultados = true;
            if ($this->ocrDisponible) {
                $this->error = 'No se pudo procesar el OCR: ' . $e->getMessage() . '. Puedes continuar introduciendo los datos manualmente.';
            }
        }

        $this->procesando = false;
    }

    protected function convertirPdfAImagen($pdfPath)
    {
        // Usar Imagick para convertir PDF a imagen
        if (!extension_loaded('imagick')) {
            throw new \Exception('La extensión Imagick no está instalada. Necesaria para procesar PDFs.');
        }

        $imagick = new \Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath . '[0]'); // Primera página
        $imagick->setImageFormat('png');

        $tempImagePath = sys_get_temp_dir() . '/justificante_' . uniqid() . '.png';
        $imagick->writeImage($tempImagePath);
        $imagick->clear();
        $imagick->destroy();

        return $tempImagePath;
    }

    protected function ejecutarOCR($imagePath)
    {
        // Verificar si Tesseract está disponible
        $tesseractPath = $this->detectarTesseract();

        if (!$tesseractPath) {
            throw new \Exception('Tesseract OCR no está instalado o no se encuentra en el PATH del sistema.');
        }

        $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath);
        $ocr->executable($tesseractPath);

        // Intentar con español, si no está disponible usar inglés
        $tessdataPath = 'C:\Program Files\Tesseract-OCR\tessdata';
        if (file_exists($tessdataPath . '\spa.traineddata')) {
            $ocr->lang('spa');
        } else {
            $ocr->lang('eng');
        }

        return $ocr->run();
    }

    protected function detectarTesseract()
    {
        // Rutas comunes de Tesseract en Windows
        $posiblesRutas = [
            'C:\Program Files\Tesseract-OCR\tesseract.exe',
            'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
            'C:\Tesseract-OCR\tesseract.exe',
            'tesseract', // Si está en el PATH
        ];

        foreach ($posiblesRutas as $ruta) {
            if (file_exists($ruta) || $this->comandoExiste($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    protected function comandoExiste($comando)
    {
        $return = shell_exec(sprintf("where %s 2>nul", escapeshellarg($comando)));
        return !empty($return);
    }

    protected function analizarTexto($texto)
    {
        // Limpiar el texto de espacios extra para mejor detección
        $textoLimpio = preg_replace('/\s+/', ' ', $texto);

        // Buscar fechas en formato común español (dd/mm/yyyy, dd-mm-yyyy, etc.)
        // Permitir espacios opcionales entre los componentes
        $patronesFecha = [
            '/(\d{1,2})\s*[\/\-]\s*(\d{1,2})\s*[\/\-]\s*(\d{4})/',  // dd/mm/yyyy o dd-mm-yyyy con espacios
            '/(\d{1,2})\s+de\s+(\w+)\s+de\s+(\d{4})/i', // dd de mes de yyyy
            '/d[ií]a\s*:?\s*(\d{1,2})\s*[\/\-]\s*(\d{1,2})\s*[\/\-]\s*(\d{4})/i', // día: dd/mm/yyyy
        ];

        foreach ($patronesFecha as $patron) {
            if (preg_match($patron, $textoLimpio, $matches) || preg_match($patron, $texto, $matches)) {
                try {
                    if (count($matches) >= 4 && is_numeric($matches[2])) {
                        // Formato dd/mm/yyyy
                        $dia = (int) $matches[1];
                        $mes = (int) $matches[2];
                        $anio = (int) $matches[3];

                        // Validar que sea una fecha válida
                        if ($dia >= 1 && $dia <= 31 && $mes >= 1 && $mes <= 12 && $anio >= 2020 && $anio <= 2030) {
                            $this->fechaDetectada = Carbon::createFromDate($anio, $mes, $dia)->format('Y-m-d');
                            break;
                        }
                    } else if (count($matches) >= 4) {
                        // Formato con nombre de mes
                        $meses = [
                            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
                        ];
                        $mesTexto = strtolower($matches[2]);
                        if (isset($meses[$mesTexto])) {
                            $this->fechaDetectada = Carbon::createFromDate($matches[3], $meses[$mesTexto], $matches[1])
                                ->format('Y-m-d');
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Si se detectó fecha, intentar seleccionar automáticamente la asignación correspondiente
        if ($this->fechaDetectada) {
            // Primero buscar en las asignaciones disponibles (sin justificante)
            $encontrada = false;
            foreach ($this->asignacionesDisponibles as $key => $asignacion) {
                if ($asignacion['fecha'] === $this->fechaDetectada) {
                    $this->asignacionSeleccionada = $asignacion['id'];
                    $encontrada = true;
                    break;
                }
            }

            // Si no está en disponibles, buscar si existe alguna asignación para esa fecha (con o sin justificante)
            if (!$encontrada) {
                $asignacionExistente = AsignacionTurno::where('user_id', $this->userId)
                    ->whereDate('fecha', $this->fechaDetectada)
                    ->first();

                if ($asignacionExistente) {
                    // Añadir a las disponibles para que pueda seleccionarla
                    $asignacionArray = [
                        'id' => (string) $asignacionExistente->id,
                        'fecha' => $asignacionExistente->fecha->format('Y-m-d'),
                        'fecha_formateada' => $asignacionExistente->fecha->format('d/m/Y'),
                        'turno' => $asignacionExistente->turno->nombre ?? 'Sin turno',
                        'obra' => $asignacionExistente->obra->obra ?? 'Sin obra',
                        'estado' => $asignacionExistente->estado,
                    ];
                    array_unshift($this->asignacionesDisponibles, $asignacionArray);
                    $this->asignacionSeleccionada = (string) $asignacionExistente->id;
                    $encontrada = true;
                }
            }

            // Solo crear nueva asignación si realmente no existe ninguna para esa fecha
            if (!$encontrada) {
                try {
                    $nuevaAsignacion = AsignacionTurno::create([
                        'user_id' => $this->userId,
                        'fecha' => Carbon::parse($this->fechaDetectada),
                        'estado' => 'justificado',
                    ]);

                    $nuevaAsignacionArray = [
                        'id' => (string) $nuevaAsignacion->id,
                        'fecha' => $this->fechaDetectada,
                        'fecha_formateada' => Carbon::parse($this->fechaDetectada)->format('d/m/Y'),
                        'turno' => 'Sin turno',
                        'obra' => 'Sin obra',
                        'estado' => 'justificado',
                    ];

                    array_unshift($this->asignacionesDisponibles, $nuevaAsignacionArray);
                    $this->asignacionSeleccionada = (string) $nuevaAsignacion->id;
                } catch (\Exception $e) {
                    $this->error = 'Error al crear asignación: ' . $e->getMessage();
                }
            }
        }

        // Las horas se introducen manualmente por el usuario
    }

    public function guardarJustificante()
    {
        $this->validate();

        try {
            $user = User::find($this->userId);

            // Si hay asignación seleccionada, usarla; si no, crear una nueva con la fecha manual
            if ($this->asignacionSeleccionada) {
                $asignacion = AsignacionTurno::find($this->asignacionSeleccionada);

                if (!$asignacion) {
                    $this->error = 'No se encontró la asignación seleccionada.';
                    return;
                }
            } else {
                // Crear nueva asignación con la fecha manual
                $fecha = Carbon::parse($this->fechaManual);

                // Verificar si ya existe una asignación para esa fecha
                $asignacion = AsignacionTurno::where('user_id', $this->userId)
                    ->whereDate('fecha', $fecha)
                    ->first();

                if (!$asignacion) {
                    $asignacion = AsignacionTurno::create([
                        'user_id' => $this->userId,
                        'fecha' => $fecha,
                        'estado' => 'justificado',
                    ]);
                }
            }

            // Crear directorio si no existe
            $nombreCarpeta = str_replace(' ', '_', $user->name) . '_' . $user->id;
            $rutaBase = "documentos/{$nombreCarpeta}/justificantes_asistencia";

            // Guardar archivo
            $nombreArchivo = $asignacion->fecha->format('Y-m-d') . '_' . time() . '.' . $this->archivo->getClientOriginalExtension();
            $rutaArchivo = $this->archivo->storeAs($rutaBase, $nombreArchivo, 'public');

            // Actualizar asignación
            $datosActualizar = [
                'justificante_ruta' => $rutaArchivo,
                'horas_justificadas' => $this->horasDetectadas,
                'justificante_observaciones' => $this->observaciones ?: null,
                'justificante_subido_at' => now(),
            ];

            // Siempre cambiar a justificado al subir justificante
            $datosActualizar['estado'] = 'justificado';

            $asignacion->update($datosActualizar);

            // Enviar alertas a usuarios de RRHH y Producción
            $this->notificarDepartamentos($user, $asignacion);

            // Resetear formulario
            $this->reset(['archivo', 'textoExtraido', 'fechaDetectada', 'horasDetectadas', 'observaciones', 'mostrarResultados', 'asignacionSeleccionada', 'fechaManual']);
            $this->cargarAsignacionesDisponibles();
            $this->cargarJustificantesExistentes();

            session()->flash('justificante_success', 'Justificante guardado correctamente.');
            $this->dispatch('justificante-guardado');

        } catch (\Exception $e) {
            $this->error = 'Error al guardar el justificante: ' . $e->getMessage();
        }
    }

    /**
     * Notifica a los usuarios de RRHH y Producción sobre el nuevo justificante
     */
    protected function notificarDepartamentos(User $usuario, AsignacionTurno $asignacion): void
    {
        $alertaService = app(AlertaService::class);

        // Buscar usuarios de los departamentos RRHH y Producción
        $usuariosNotificar = User::whereHas('departamentos', function ($query) {
            $query->whereRaw('LOWER(nombre) IN (?, ?)', [
                'rrhh',
                'producción'
            ]);
        })
        ->where('estado', 'activo')
        ->where('id', '!=', $usuario->id) // No notificar al propio usuario
        ->get();

        // Crear mensaje de la alerta con enlace al justificante
        $fechaFormateada = $asignacion->fecha->format('d/m/Y');
        $enlaceJustificante = asset('storage/' . $asignacion->justificante_ruta);
        $mensaje = "{$usuario->name} ha subido un justificante para el día {$fechaFormateada} ({$this->horasDetectadas}h). <a href=\"{$enlaceJustificante}\" target=\"_blank\" class=\"text-blue-600 underline hover:text-blue-800\">Ver justificante</a>";

        // Enviar alerta a cada usuario
        foreach ($usuariosNotificar as $destinatario) {
            $alertaService->crearAlerta(
                emisorId: $usuario->id,
                destinatarioId: $destinatario->id,
                mensaje: $mensaje,
                tipo: 'justificante'
            );
        }
    }

    public function cancelar()
    {
        $this->reset(['archivo', 'textoExtraido', 'fechaDetectada', 'horasDetectadas', 'observaciones', 'mostrarResultados', 'asignacionSeleccionada', 'fechaManual', 'error']);
    }

    public function render()
    {
        return view('livewire.subir-justificante');
    }
}
