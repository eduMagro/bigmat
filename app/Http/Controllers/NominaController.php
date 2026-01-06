<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Nomina;
use App\Models\Empresa;
use App\Models\Modelo145;
use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\TasaSeguridadSocial;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
//Importacion PDF nominas
use App\Jobs\DividirNominasJob;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;
use App\Services\AlertaService;

use Illuminate\Support\Facades\Storage;


class NominaController extends Controller
{
    // --------------------- IMPORTACION NOMINASS

    public function dividirNominas(Request $request)
    {
        // 0) Validación de entrada
        $request->validate([
            'archivo'  => 'required|mimes:pdf|max:102400', // 100 MB
            'mes_anio' => 'required|date_format:Y-m',
        ]);

        // 1) Subida a carpeta temporal
        $rutaRelativa = $request->file('archivo')->store('private/temp');
        $rutaAbsoluta = storage_path('app/' . $rutaRelativa);

        // 2) Ampliar límites de ejecución
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        // @ini_set('memory_limit', '1024M');

        // 3) Obtener mes y año
        $fecha        = Carbon::createFromFormat('Y-m', $request->mes_anio);
        $mesEnEspañol = ucfirst($fecha->locale('es')->translatedFormat('F'));
        $anio         = $fecha->format('Y');

        // 4) Crear carpeta base para el lote
        $carpetaBaseRelativa = 'private/nominas/nominas_' . $anio . '/nomina_' . $mesEnEspañol . '_' . $anio;
        if (!Storage::exists($carpetaBaseRelativa)) {
            Storage::makeDirectory($carpetaBaseRelativa);
        }

        // 5) Mapa de DNI => Empresa (aunque ya no lo usamos para carpeta, se puede loguear)
        $usuarios = User::with('empresa')->get();
        $mapaDnis = [];
        foreach ($usuarios as $u) {
            if (!$u->dni) continue;
            $dni = strtoupper(preg_replace('/[^A-Z0-9]/', '', $u->dni));
            $empresa = $u->empresa->nombre ?? 'SIN_EMPRESA';
            $empresaNorm = preg_replace('/[^A-Z0-9_-]/', '_', strtoupper($empresa));
            $mapaDnis[$dni] = $empresaNorm;
        }

        // 6) Parsear PDF
        $parser = new Parser();
        try {
            $pdfParsed = $parser->parseFile($rutaAbsoluta);
        } catch (\Throwable $e) {
            Storage::delete($rutaRelativa);
            return back()->with('abort', 'No se pudo leer el PDF: ' . $e->getMessage());
        }

        $pages = $pdfParsed->getPages();

        /** @var array<string,Fpdi> $pdfPorDni */
        $pdfPorDni = [];
        $dnisNoEncontrados = [];

        foreach ($pages as $i => $page) {
            $textoPagina      = strtoupper($page->getText());
            $textoNormalizado = preg_replace('/\s+/', '', $textoPagina);

            // Buscar DNI en la página (9 caracteres antes de "NºAFILIACION")
            $pos = strpos($textoNormalizado, 'NºAFILIACION');
            $dniExtraido = null;
            if ($pos !== false && $pos >= 9) {
                $dniExtraido = substr($textoNormalizado, $pos - 9, 9);
                $dniExtraido = strtoupper(preg_replace('/[^A-Z0-9]/', '', $dniExtraido));
            }

            // Clave de agrupación: DNI o fallback
            $clave = $dniExtraido ?: ('nomina_' . str_pad($i + 1, 3, '0', STR_PAD_LEFT));

            // Registrar DNI no encontrado
            if (!$dniExtraido || !isset($mapaDnis[$dniExtraido])) {
                $dnisNoEncontrados[] = $dniExtraido ?: ('SIN_DNI_PAGINA_' . ($i + 1));
            }

            // Crear FPDI por clave si no existe
            if (!isset($pdfPorDni[$clave])) {
                $fpdi = new Fpdi();
                $fpdi->setSourceFile($rutaAbsoluta);
                $pdfPorDni[$clave] = $fpdi;
            }

            // Añadir página al PDF
            $fpdi = $pdfPorDni[$clave];
            $fpdi->AddPage();
            $tpl = $fpdi->importPage($i + 1);
            $fpdi->useTemplate($tpl);
        }

        // 7) Guardar un PDF por clave en la misma carpeta: nominas_mes_año/{CLAVE}_{MES}_{AÑO}.pdf
        foreach ($pdfPorDni as $clave => $fpdi) {
            // Nombre de archivo
            $nombreArchivo = $clave . '_' . $mesEnEspañol . '_' . $anio . '.pdf';

            // Ruta de guardado
            $rutaSalida = storage_path('app/' . $carpetaBaseRelativa . '/' . $nombreArchivo);

            try {
                $fpdi->Output($rutaSalida, 'F');
            } catch (\Throwable $e) {
                \Log::error('❌ Error guardando PDF ' . $nombreArchivo . ': ' . $e->getMessage());
            }
        }


        // 8) Crear alerta con DNIs no encontrados
        if (!empty($dnisNoEncontrados)) {
            $mensajeAlerta = 'Nóminas importadas (' . $mesEnEspañol . ' - ' . $anio . '). DNIs no encontrados: ' . implode(', ', $dnisNoEncontrados);

            try {
                $alerta = Alerta::create([
                    'user_id_1'       => auth()->id(),
                    'user_id_2'       => null,
                    'destino'         => null,
                    'destinatario'    => null,
                    'destinatario_id' => auth()->id(),
                    'mensaje'         => $mensajeAlerta,
                    'tipo'            => 'warning',
                ]);

                AlertaLeida::create([
                    'alerta_id' => $alerta->id,
                    'user_id'   => auth()->id(),
                    'leida_en'  => null,
                ]);
            } catch (\Throwable $e) {
                \Log::error('❌ Error creando alerta: ' . $e->getMessage());
            }
        }

        // 9) Notificar a usuarios de oficina y operarios que las nóminas están disponibles
        $this->notificarNominasImportadas($mesEnEspañol, $anio);

        // 10) Borrar archivo temporal
        Storage::delete($rutaRelativa);

        // 11) Devolver mensaje al usuario
        $totalClaves = count($pdfPorDni);
        $paginasTotales = count($pages);
        $mensaje = "Nóminas divididas correctamente: {$totalClaves} PDFs generados a partir de {$paginasTotales} página(s).";
        if (!empty($dnisNoEncontrados)) {
            $mensaje .= ' Se ha creado una alerta con los DNIs no encontrados.';
        }

        return back()->with('success', $mensaje);
    }

    public function descargarNominasMes(Request $request)
    {
        $request->validate([
            'mes_anio' => 'required|date_format:Y-m',
        ], [
            'mes_anio.required' => 'Debes seleccionar un mes y año para solicitar tu nómina.',
            'mes_anio.date_format' => 'El formato del mes y año no es válido. Por favor, selecciona una fecha correcta.',
        ]);

        // Obtener mes y año
        $fecha = Carbon::createFromFormat('Y-m-d', $request->mes_anio . '-01');
        $mes = ucfirst($fecha->locale('es')->translatedFormat('F'));
        $anio = $fecha->format('Y');

        // Usuario y DNI limpio
        $user = auth()->user();
        $dni = strtoupper(preg_replace('/[^A-Z0-9]/', '', $user->dni));

        // 🔒 SEGURIDAD: Verificar que el usuario tiene email configurado
        if (empty($user->email)) {
            \Log::warning('🚨 Intento de solicitar nómina sin email configurado', [
                'user_id' => $user->id,
                'dni' => $dni,
                'ip_address' => $request->ip(),
                'timestamp' => now(),
            ]);
            return back()->with('error', 'No tienes un correo electrónico configurado. Contacta con el departamento de programador.');
        }

        // Carpeta principal del mes
        $carpetaBase = storage_path("app/private/nominas/nominas_{$anio}/nomina_{$mes}_{$anio}");

        // Buscar en carpeta raíz
        $archivos = array_merge(
            glob($carpetaBase . '/' . $dni . '.pdf'),
            glob($carpetaBase . '/' . $dni . '_*.pdf'),
            glob($carpetaBase . '/' . $dni . '-*.pdf')
        );

        // Si no se encuentra en la raíz, buscar en carpeta por DNI
        if (empty($archivos)) {
            $carpetaDni = $carpetaBase . '/' . $dni;
            $archivos = array_merge(
                glob($carpetaDni . '/' . $dni . '.pdf'),
                glob($carpetaDni . '/' . $dni . '_*.pdf'),
                glob($carpetaDni . '/' . $dni . '-*.pdf')
            );
        }

        if (empty($archivos)) {
            // 🔍 LOG: Intento de acceso a nómina inexistente
            \Log::warning('🚨 Intento de acceso a nómina inexistente', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'dni' => $dni,
                'mes' => $mes,
                'anio' => $anio,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

            return back()->with('error', 'No se encontró ninguna nómina para el mes de ' . $mes . ' de ' . $anio . '. Si crees que esto es un error, contacta con el departamento de administración.');
        }

        $rutaArchivo = $archivos[0];

        // Nombre que verá el usuario en el email
        $nombreDescarga = 'Nomina_' . $user->nombre_completo . '_' . $mes . '_' . $anio . '.pdf';

        // 🔐 SEGURIDAD: Registrar acceso completo con IP y timestamp
        \Log::info('📧 Envío de nómina por email', [
            'user_id' => $user->id,
            'user_name' => $user->nombre_completo,
            'user_email' => $user->email,
            'dni' => $dni,
            'mes' => $mes,
            'anio' => $anio,
            'archivo' => basename($rutaArchivo),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
            'success' => false, // se actualizará después del envío
        ]);

        // 📧 Enviar nómina por email
        try {
            \Mail::to($user->email)->send(
                new \App\Mail\NominaEnviada(
                    $user->name, // Solo el nombre, no el nombre completo
                    $mes . ' ' . $anio,
                    $rutaArchivo,
                    $nombreDescarga
                )
            );

            // Actualizar log de éxito
            \Log::info('✅ Nómina enviada correctamente por email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'dni' => $dni,
                'mes' => $mes,
                'anio' => $anio,
                'ip_address' => $request->ip(),
                'timestamp' => now(),
                'success' => true,
            ]);

            // Registrar alerta de envío
            try {
                app(AlertaService::class)->crearAlerta(
                    emisorId: $user->id,
                    destinatarioId: $user->id,
                    mensaje: 'Se ha enviado tu nómina de ' . $mes . ' ' . $anio . ' a tu correo electrónico (' . $user->email . ')',
                    tipo: 'usuario'
                );
            } catch (\Throwable $e) {
                \Log::error('❌ Error creando alerta de envío de nómina: ' . $e->getMessage());
            }

            return back()->with('success', 'Tu nómina de ' . $mes . ' ' . $anio . ' ha sido enviada a tu correo electrónico: ' . $user->email);

        } catch (\Throwable $e) {
            // 🔥 ERROR: Falló el envío de email
            \Log::error('🔥 Error al enviar nómina por email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'dni' => $dni,
                'mes' => $mes,
                'anio' => $anio,
                'ip_address' => $request->ip(),
                'timestamp' => now(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', '❌ Ha ocurrido un error al enviar tu nómina por email. Por favor, inténtalo de nuevo más tarde o contacta con el departamento de administración.');
        }
    }




    // --------------------- GENERACION NOMINA
    public function index()
    {
        return view('nominas.index');
    }

    public function generarNominasMensuales(Request $request, ?int $mes = null, ?int $anio = null)
    {
        // Obtener mes y año desde el request o usar fecha actual
        if ($request->filled('fecha')) {
            [$anio, $mes] = explode('-', $request->input('fecha'));
        } else {
            $mes = $mes ?? now()->month;
            $anio = $anio ?? now()->year;
        }

        $horas_extra      = 0;
        $valor_hora_extra = 14;
        $plus_horas_extra = $horas_extra * $valor_hora_extra;

        DB::beginTransaction();

        try {
            // Cargar todos los trabajadores con sus relaciones necesarias
            $trabajadores = User::with(['convenio', 'modelo145'])->get();
            $idsConIncentivo = $request->input('incentivados', []);

            foreach ($trabajadores as $trabajador) {
                $modelo = $trabajador->modelo145;
                $convenio = $trabajador->convenio;

                if (!$convenio || !$modelo) continue;

                // Datos base del trabajador
                $salario_base = $convenio->salario_base / 12;
                $pluses = [
                    'actividad' => $convenio->plus_actividad / 12,
                    'asistencia' => $convenio->plus_asistencia / 12,
                    'transporte' => $convenio->plus_transporte / 12,
                    'dieta' => $convenio->plus_dieta / 12,
                    'turnicidad' => $convenio->plus_turnicidad / 12,
                    'prorrateo' => $convenio->prorrateo_pagasextras / 12,
                ];

                $liquidoDeseado = $convenio->liquido_minimo_pactado;
                $aplicaIncentivo = in_array($trabajador->id, $idsConIncentivo);
                $porcentajeSS_trabajador = $this->obtenerTotalCotizacionTrabajador();
                $porcentajeSS_empresa = $this->obtenerTotalCotizacionEmpresa();
                $minimos = $this->calcularMinimosDesdeModelo($modelo);

                // Primer cálculo del IRPF estimado
                // 1) Calcular bruto inicial y estimar IRPF
                $brutoBase          = $this->calcularBruto($salario_base, $pluses);
                $brutoAnualEstimado = $brutoBase * 12;
                $ssAnual            = $brutoAnualEstimado * ($porcentajeSS_trabajador / 100);
                $baseIRPF           = max($brutoAnualEstimado - $ssAnual - $minimos, 0);
                $cuotaIRPFAnual     = $this->calcularCuotaIRPF($baseIRPF);

                // IRPF acumulado en el año
                $nominasPrevias = Nomina::where('empleado_id', $trabajador->id)
                    ->whereYear('fecha', $anio)
                    ->whereMonth('fecha', '<', $mes)
                    ->get();
                $acumuladoIRPF    = $nominasPrevias->sum('irpf_mensual');
                $mesesRestantes   = 12 - count($nominasPrevias);
                $irpfMensual      = $mesesRestantes > 0
                    ? ($cuotaIRPFAnual - $acumuladoIRPF) / $mesesRestantes
                    : 0;

                // 2) Ajustar pluses
                $ajusteNecesario = $this->ajustarPlusesHastaMinimoLiquido(
                    $pluses,
                    $salario_base,
                    $liquidoDeseado,
                    $irpfMensual,
                    $porcentajeSS_trabajador
                );

                // 3) Recalcular bruto y neto base
                $brutoBase  = $this->calcularBruto($salario_base, $pluses);
                $ssMensual  = $brutoBase * ($porcentajeSS_trabajador / 100);
                $netoBase   = $brutoBase - $ssMensual - $irpfMensual;

                // 4) Incentivo productividad opcional
                $plus_productividad = 0;
                if ($aplicaIncentivo) {
                    $liquidoDeseadoConIncentivo = $netoBase + ($convenio->plus_productividad / 12);
                    $plus_productividad = $this->ajustarBrutoParaProductividad(
                        $brutoBase,
                        $netoBase,
                        $liquidoDeseadoConIncentivo,
                        $irpfMensual,
                        $porcentajeSS_trabajador
                    );
                }

                // 5) Cálculo final nómina
                $brutoFinal    = $brutoBase + $plus_productividad + $plus_horas_extra;
                $ss            = $brutoFinal * ($porcentajeSS_trabajador / 100);
                $netoFinal     = $brutoFinal - $ss - $irpfMensual;
                $costeEmpresa  = $brutoFinal * (1 + ($porcentajeSS_empresa / 100));
                $porcentajeIRPF = $brutoFinal > 0
                    ? round(($irpfMensual / $brutoFinal) * 100, 2)
                    : 0;

                // 6) Ahora sí calculamos SS y neto de la nómina de este mes
                $ss        = $brutoFinal * ($porcentajeSS_trabajador / 100);
                $netoFinal = $brutoFinal - $ss - $irpfMensual;
                // Guardar la nómina
                Nomina::create([
                    'empleado_id' => $trabajador->id,
                    'categoria_id' => $trabajador->categoria_id,
                    'dias_trabajados' => 30,
                    'salario_base' => round($salario_base, 2),
                    'plus_ajustado' => $ajusteNecesario,
                    'plus_actividad' => round($pluses['actividad'], 2),
                    'plus_asistencia' => round($pluses['asistencia'], 2),
                    'plus_transporte' => round($pluses['transporte'], 2),
                    'plus_dieta' => round($pluses['dieta'], 2),
                    'plus_turnicidad' => round($pluses['turnicidad'], 2),
                    'plus_productividad' => round($plus_productividad, 2),
                    'prorrateo' => round($pluses['prorrateo'], 2),
                    'horas_extra'       => $horas_extra,
                    'valor_hora_extra'  => $valor_hora_extra,
                    'total_devengado' => round($brutoFinal, 2),
                    'total_deducciones_ss' => round($ss, 2),
                    'irpf_mensual' => round($irpfMensual, 2),
                    'irpf_porcentaje' => $porcentajeIRPF,
                    'liquido' => round($netoFinal, 2),
                    'coste_empresa' => round($costeEmpresa, 2),
                    'bruto_anual_estimado' => round($brutoAnualEstimado, 2),
                    'base_irpf_previa' => round($baseIRPF, 2),
                    'cuota_irpf_anual_sin_minimo' => round($this->calcularCuotaIRPF($brutoAnualEstimado - $ssAnual), 2),
                    'cuota_minimo_personal' => $minimos,
                    'cuota_irpf_anual' => round($cuotaIRPFAnual, 2),
                    'fecha' => Carbon::createFromDate($anio, $mes, 1)->toDateString(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Nóminas generadas correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Error generando nóminas.', 'error' => $e->getMessage()], 500)
                : redirect()->back()->with('error', 'Error generando nóminas: ' . $e->getMessage());
        }
    }

    private function calcularBruto(float $salario_base, array $pluses): float
    {
        $bruto = $salario_base + array_sum($pluses);

        return $bruto;
    }

    private function calcularNeto(float $bruto, float $irpf, float $ss): float
    {
        return $bruto - ($bruto * ($ss / 100)) - $irpf;
    }



    private function ajustarPlusesHastaMinimoLiquido(
        array &$pluses,
        float $salario_base,
        float $liquidoObjetivo,
        float $irpfEstimado,
        float $porcentajeSS
    ): bool {
        $limites = [
            'actividad'  => 200,
            'asistencia' => 200,
            'transporte' => 200,
            'dieta'      => 200,
            'turnicidad' => 200,
            'prorrateo'  => 200,
        ];

        $maxIntentos = 1000;
        $intento     = 0;
        $ajustado    = false;

        while (++$intento <= $maxIntentos) {
            $bruto = $this->calcularBruto($salario_base, $pluses);
            $ss    = $bruto * ($porcentajeSS / 100);
            $neto  = $bruto - $ss - $irpfEstimado;
            $diferencia = $liquidoObjetivo - $neto;

            if (abs($diferencia) <= 0.5) {
                break;
            }

            if ($diferencia > 0) {
                // falta neto → sumar
                foreach ($limites as $clave => $max) {
                    if ($pluses[$clave] < $max) {
                        $pluses[$clave]++;
                        $ajustado = true;
                        break;
                    }
                }
            } else {
                // sobra neto → restar
                foreach ($pluses as $clave => $valor) {
                    if ($valor > 0) {
                        $pluses[$clave]--;
                        $ajustado = true;
                        break;
                    }
                }
            }
        }

        return $ajustado;
    }
    private function ajustarBrutoParaProductividad(float $brutoBase, float $netoBase, float $liquidoObjetivo, float $irpf, float $ss): float
    {
        $bruto = $brutoBase;

        while (true) {
            $neto = $this->calcularNeto($bruto, $irpf, $ss);
            if ($neto >= $liquidoObjetivo) break;
            $bruto += 1;
            if ($bruto > 10000) break;
        }

        return round($bruto - $brutoBase, 2);
    }

    private function obtenerTotalCotizacionTrabajador(): float
    {
        return TasaSeguridadSocial::where('destinatario', 'trabajador')->sum('porcentaje');
    }

    private function obtenerTotalCotizacionEmpresa(): float
    {
        return TasaSeguridadSocial::where('destinatario', 'empresa')->sum('porcentaje');
    }

    public function calcularMinimosDesdeModelo(Modelo145 $modelo): float
    {
        $minimos = 5550;

        if ($modelo->edad >= 65) $minimos += 1150;
        if ($modelo->edad >= 75) $minimos += 1400;

        $hijos = $modelo->hijos_a_cargo;
        if ($hijos >= 1) $minimos += 2400;
        if ($hijos >= 2) $minimos += 2700;
        if ($hijos >= 3) $minimos += 4000;
        if ($hijos >= 4) $minimos += 4500;

        $minimos += $modelo->hijos_menores_3 * 2800;

        if ($modelo->ascendientes_mayores_65) $minimos += 1150;
        if ($modelo->ascendientes_mayores_75) $minimos += 1400;

        $grado = $modelo->discapacidad_porcentaje;
        if ($grado >= 33 && $grado < 66) $minimos += 3000;
        if ($grado >= 66) $minimos += 9000;

        if ($modelo->discapacidad_familiares) $minimos += 3000;

        return $minimos;
    }

    public function calcularCuotaIRPF(float $base): float
    {
        $cuota = 0;
        $tramos = [
            [0, 12450, 0.19],
            [12450, 20200, 0.24],
            [20200, 35200, 0.30],
            [35200, 60000, 0.37],
            [60000, 300000, 0.45],
            [300000, null, 0.47],
        ];

        foreach ($tramos as [$desde, $hasta, $porcentaje]) {
            if ($base > $desde) {
                $limite = $hasta ?? $base;
                $importe = min($base, $limite) - $desde;
                $cuota += $importe * $porcentaje;
            }
        }

        return $cuota;
    }


    public function borrarTodas()
    {
        Nomina::truncate(); // Elimina todas las filas de la tabla

        return redirect()->back()->with('success', 'Todas las nóminas han sido eliminadas.');
    }
    public function show($id)
    {
        $nomina = Nomina::findOrFail($id);
        $empresas = Empresa::All();
        $aportacionesEmpresa = TasaSeguridadSocial::where('destinatario', 'empresa')->get();

        return view('nominas.show', compact('nomina', 'empresas', 'aportacionesEmpresa'));
    }
    // --------------------- SIMULACION NOMINA DESDE EL BRUTO
    public function formularioSimulacion()
    {
        return view('nominas.simulacion');
    }

    public function simular(Request $request)
    {
        $data = $request->all();

        $sueldoAnual = floatval($data['sueldo_bruto_anual']);
        $acumulado = floatval($data['acumulado_actual'] ?? 0);
        $mesActual = now()->month;

        // Seguridad Social aproximada (6,35 %)
        $ss = $sueldoAnual * 0.0635;

        // Calcular mínimos personales y familiares
        $minimos = 5550;
        $edad = intval($data['edad']);
        if ($edad >= 65) $minimos += 1150;
        if ($edad >= 75) $minimos += 1400;

        $minimos += min(intval($data['hijos_a_cargo']), 1) * 2400;
        if (intval($data['hijos_a_cargo']) >= 2) $minimos += 2700;
        if (intval($data['hijos_a_cargo']) >= 3) $minimos += 4000;
        if (intval($data['hijos_a_cargo']) >= 4) $minimos += 4500;

        $minimos += intval($data['hijos_menores_3']) * 2800;

        if ($request->boolean('ascendientes_mayores_65')) $minimos += 1150;
        if ($request->boolean('ascendientes_mayores_75')) $minimos += 1400;

        $discapacidad = intval($data['discapacidad_porcentaje']);
        if ($discapacidad >= 33 && $discapacidad < 66) $minimos += 3000;
        if ($discapacidad >= 66) $minimos += 9000;

        // Estimar el total ganado en el año
        $sueldoMensualBruto = $sueldoAnual / 12;
        $restoMeses = 12 - $mesActual;
        $estimadoRestoAno = $sueldoMensualBruto * $restoMeses;
        $estimacionAnual = $acumulado + $estimadoRestoAno;

        // Calcular SS e IRPF según estimación
        $ssEstimado = $estimacionAnual * 0.0635;
        $baseIrpf = max($estimacionAnual - $ssEstimado - $minimos, 0);

        $cuota = 0;
        $tramos = [
            [0, 12450, 0.19],
            [12450, 20200, 0.24],
            [20200, 35200, 0.30],
            [35200, 60000, 0.37],
            [60000, 300000, 0.45],
            [300000, null, 0.47],
        ];

        foreach ($tramos as [$desde, $hasta, $porcentaje]) {
            if ($baseIrpf > $desde) {
                $limite = $hasta ?? $baseIrpf;
                $importe = min($baseIrpf, $limite) - $desde;
                $cuota += $importe * $porcentaje;
            }
        }

        $retencionMensual = $cuota / 12;
        $ssMensual = $ss / 12;
        $netoMensual = $sueldoMensualBruto - $ssMensual - $retencionMensual;

        return response()->json([
            'bruto_mensual' => round($sueldoMensualBruto, 2),
            'ss_mensual' => round($ssMensual, 2),
            'retencion_mensual' => round($retencionMensual, 2),
            'neto_mensual' => round($netoMensual, 2),
            'mes_actual' => ucfirst(now()->locale('es')->monthName),
            'acumulado_bruto' => round($acumulado, 2),
        ]);
    }


    // --------------------- SIMULACION NOMINA DESDE EL NETO
    public function formularioInverso()
    {
        return view('nominas.simulacion_inversa');
    }

    public function simularDesdeNeto(Request $request)
    {
        $data = $request->all();

        $netoDeseado = floatval($data['neto_deseado']);
        $acumulado = floatval($data['acumulado_actual'] ?? 0);
        $mesActual = now()->month;

        // Datos personales
        $minimos = 5550;
        $edad = intval($data['edad']);
        if ($edad >= 65) $minimos += 1150;
        if ($edad >= 75) $minimos += 1400;

        $minimos += min(intval($data['hijos_a_cargo']), 1) * 2400;
        if (intval($data['hijos_a_cargo']) >= 2) $minimos += 2700;
        if (intval($data['hijos_a_cargo']) >= 3) $minimos += 4000;
        if (intval($data['hijos_a_cargo']) >= 4) $minimos += 4500;

        $minimos += intval($data['hijos_menores_3']) * 2800;

        if ($request->boolean('ascendientes_mayores_65')) $minimos += 1150;
        if ($request->boolean('ascendientes_mayores_75')) $minimos += 1400;

        $discapacidad = intval($data['discapacidad_porcentaje']);
        if ($discapacidad >= 33 && $discapacidad < 66) $minimos += 3000;
        if ($discapacidad >= 66) $minimos += 9000;

        // Simulación inversa
        $brutoAnualEstimado = 10000;
        $maxIntentos = 100;
        $errorAceptable = 1.00;

        while ($maxIntentos--) {
            $brutoMensual = $brutoAnualEstimado / 12;
            $estimadoBrutoRestante = $brutoMensual * (12 - $mesActual);
            $estimacionAnual = $acumulado + $estimadoBrutoRestante;

            $ss = $estimacionAnual * 0.0635;
            $baseIrpf = max($estimacionAnual - $ss - $minimos, 0);

            $cuota = 0;
            $tramos = [
                [0, 12450, 0.19],
                [12450, 20200, 0.24],
                [20200, 35200, 0.30],
                [35200, 60000, 0.37],
                [60000, 300000, 0.45],
                [300000, null, 0.47],
            ];

            foreach ($tramos as [$desde, $hasta, $porcentaje]) {
                if ($baseIrpf > $desde) {
                    $limite = $hasta ?? $baseIrpf;
                    $importe = min($baseIrpf, $limite) - $desde;
                    $cuota += $importe * $porcentaje;
                }
            }

            $netoMensualCalculado = ($brutoMensual - ($ss / 12) - ($cuota / 12));

            if (abs($netoMensualCalculado - $netoDeseado) <= $errorAceptable) {
                break;
            }

            $brutoAnualEstimado += ($netoDeseado - $netoMensualCalculado) * 20;
        }

        return response()->json([
            'bruto_anual' => round($brutoAnualEstimado, 2),
            'bruto_mensual' => round($brutoAnualEstimado / 12, 2),
            'ss_mensual' => round($ss / 12, 2),
            'retencion_mensual' => round($cuota / 12, 2),
            'neto_mensual' => round($netoMensualCalculado, 2),
            'mes_actual' => ucfirst(now()->locale('es')->monthName),
            'acumulado_bruto' => round($acumulado, 2),
        ]);
    }

    /**
     * Notifica a todos los usuarios de oficina y operarios que las nóminas de un mes están disponibles
     * Evita duplicados verificando si ya existe una alerta para ese mes/año
     */
    private function notificarNominasImportadas(string $mes, string $anio): void
    {
        try {
            $alertaService = app(AlertaService::class);
            $emisorId = auth()->id();

            $mensaje = "Las nóminas de {$mes} {$anio} ya están disponibles. Puedes solicitarla desde tu perfil.";

            // Buscar usuarios que ya tienen alerta de nómina para este mes/año
            $usuariosYaNotificados = \App\Models\Alerta::where('tipo', 'nomina')
                ->where('mensaje', $mensaje)
                ->pluck('destinatario_id')
                ->toArray();

            // Buscar usuarios con rol oficina u operario que estén activos y NO hayan sido notificados
            $usuarios = User::whereIn('rol', ['oficina', 'operario'])
                ->where('estado', 'activo')
                ->whereNotIn('id', $usuariosYaNotificados)
                ->get();

            if ($usuarios->isEmpty()) {
                \Log::info("ℹ️ Todos los usuarios ya tienen alerta de nóminas para {$mes} {$anio}");
                return;
            }

            foreach ($usuarios as $usuario) {
                $alertaService->crearAlerta(
                    emisorId: $emisorId,
                    destinatarioId: $usuario->id,
                    mensaje: $mensaje,
                    tipo: 'nomina'
                );
            }

            \Log::info("✅ Alertas de nóminas enviadas a " . $usuarios->count() . " usuarios (evitados " . count($usuariosYaNotificados) . " duplicados)");

        } catch (\Throwable $e) {
            \Log::error('❌ Error notificando nóminas importadas: ' . $e->getMessage());
        }
    }
}
