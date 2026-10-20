<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\AlertaLeida;
use App\Models\CompensacionHoras;
use App\Models\Departamento;
use App\Models\User;
use App\Services\ComputoHorasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Solicitudes de horas contra la bolsa del trabajador, en minutos (pasos de 5).
 *
 * El trabajador solicita para un día un número de minutos con signo:
 *  - positivo = horas extra de un día ya trabajado (hoy o pasado), que al aprobarse suman a la bolsa;
 *  - negativo = compensación: tiempo libre de un día (hoy o futuro), que al aprobarse resta.
 * El responsable de departamento u oficina comprueban el fichaje y aprueban o deniegan.
 * Solo lo aprobado mueve la bolsa, que no puede quedar en negativo.
 */
class CompensacionHorasController extends Controller
{
    /**
     * Saldo de la bolsa del usuario (propio, o de otro si quien consulta puede gestionarlo).
     * Con ?fecha= devuelve además el fichaje de ese día para orientar una solicitud de extras.
     */
    public function saldo(Request $request)
    {
        $userId = (int) $request->input('user_id', auth()->id());

        if ($userId !== auth()->id() && !$this->puedeGestionar()) {
            return response()->json(['error' => 'No tienes permiso para consultar esta bolsa.'], 403);
        }

        $extra = [
            'minutos_max' => CompensacionHoras::MINUTOS_MAX,
            'paso' => CompensacionHoras::PASO_MINUTOS,
            'hoy' => Carbon::today()->toDateString(),
        ];

        if ($request->filled('fecha')) {
            try {
                $extra['fichaje'] = ComputoHorasService::fichajeDelDia($userId, $request->input('fecha'));
            } catch (Throwable $e) {
                $extra['fichaje'] = null;
            }
        }

        return response()->json(array_merge(ComputoHorasService::saldoDisponible($userId), $extra));
    }

    /**
     * El trabajador solicita tiempo para un día: extras (+) de un día ya trabajado o una
     * compensación (-) para hoy o un día futuro.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->reglasMinutos() + [
            'fecha' => ['required', 'date'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $fecha = Carbon::parse($validated['fecha'])->startOfDay();
        $minutos = (int) $validated['minutos'];

        if ($minutos > 0 && $fecha->gt(Carbon::today())) {
            return response()->json(['error' => 'Las horas extra se solicitan una vez hechas: elige el día de hoy o un día ya trabajado.'], 422);
        }

        if ($minutos < 0 && $fecha->lt(Carbon::today())) {
            return response()->json(['error' => 'No puedes solicitar compensación para un día ya pasado. Habla con tu responsable.'], 422);
        }

        if ($fecha->lt(ComputoHorasService::fechaInicioBolsa())) {
            return response()->json(['error' => 'La bolsa de horas empieza el ' . ComputoHorasService::fechaInicioBolsa()->format('d/m/Y') . '.'], 422);
        }

        $yaExiste = CompensacionHoras::where('user_id', $user->id)
            ->whereDate('fecha', $fecha->toDateString())
            ->whereIn('estado', [CompensacionHoras::ESTADO_PENDIENTE, CompensacionHoras::ESTADO_APROBADA])
            ->first();

        if ($yaExiste) {
            $texto = $yaExiste->estado === CompensacionHoras::ESTADO_PENDIENTE ? 'una solicitud pendiente' : 'una solicitud aprobada';
            return response()->json(['error' => "Ya tienes {$texto} para ese día ({$yaExiste->etiquetaCorta()})."], 422);
        }

        // La bolsa no puede quedar en negativo: se cuentan también las compensaciones pendientes
        if ($minutos < 0) {
            $disponible = ComputoHorasService::saldoDisponible($user->id)['disponible_min'];
            if ($disponible + $minutos < 0) {
                return response()->json([
                    'error' => $disponible > 0
                        ? 'Tu bolsa no puede quedar en negativo. Como máximo puedes pedir ' . CompensacionHoras::formatearMinutos($disponible, false) . ' (contando tus solicitudes pendientes).'
                        : 'Tu bolsa no puede quedar en negativo. No tienes tiempo disponible para compensar.',
                ], 422);
            }
        }

        try {
            $compensacion = CompensacionHoras::create([
                'user_id' => $user->id,
                'fecha' => $fecha->toDateString(),
                'minutos' => $minutos,
                'estado' => CompensacionHoras::ESTADO_PENDIENTE,
                'comentario' => $validated['comentario'] ?? null,
            ]);

            $this->avisarResponsables(
                $user,
                $compensacion->esExtra()
                    ? "{$user->name} solicita que se le reconozcan {$compensacion->etiqueta()} extra del {$fecha->format('d/m/Y')}. Revisa su fichaje y acepta o deniega la solicitud."
                    : "{$user->name} ha solicitado una compensación de horas de {$compensacion->etiqueta()} para el {$fecha->format('d/m/Y')}."
            );

            return response()->json([
                'success' => $compensacion->esExtra()
                    ? 'Solicitud de horas extra enviada a tu responsable. El tiempo pasará a tu bolsa cuando la apruebe.'
                    : 'Solicitud de compensación registrada correctamente.',
                'compensacion' => $this->serializar($compensacion),
                'saldo' => ComputoHorasService::saldoDisponible($user->id),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Error al registrar la solicitud de horas (extra/compensación).', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'payload' => $validated,
            ]);

            return response()->json(['error' => 'No se pudo registrar la solicitud. Inténtalo de nuevo más tarde.'], 500);
        }
    }

    /**
     * Oficina/responsable reconoce horas extra (+, de un día ya trabajado) o asigna una
     * compensación (-) directamente aprobada a un trabajador.
     * Si ese día ya había una solicitud pendiente o aprobada, se sustituye.
     */
    public function asignarDirecto(Request $request)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['error' => 'No tienes permiso para asignar horas.'], 403);
        }

        $validated = $request->validate($this->reglasMinutos() + [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'fecha' => ['required', 'date'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $fecha = Carbon::parse($validated['fecha'])->startOfDay();
        $minutos = (int) $validated['minutos'];

        if ($fecha->lt(ComputoHorasService::fechaInicioBolsa())) {
            return response()->json(['error' => 'La bolsa de horas empieza el ' . ComputoHorasService::fechaInicioBolsa()->format('d/m/Y') . '.'], 422);
        }

        if ($minutos > 0 && $fecha->gt(Carbon::today())) {
            return response()->json(['error' => 'Las horas extra se reconocen una vez hechas: elige el día de hoy o un día ya trabajado.'], 422);
        }

        if ($minutos < 0) {
            // Saldo aprobado sin contar lo que ya hubiera aprobado ese mismo día (se va a sustituir)
            $saldo = ComputoHorasService::bolsaHorasUsuario((int) $validated['user_id'])['saldo_min'];
            $aprobadaEseDia = CompensacionHoras::aprobadas()
                ->where('user_id', $validated['user_id'])
                ->whereDate('fecha', $fecha->toDateString())
                ->value('minutos');
            $saldoSinEseDia = $saldo - (int) ($aprobadaEseDia ?? 0);
            if ($saldoSinEseDia + $minutos < 0) {
                return response()->json([
                    'error' => 'La bolsa del trabajador no puede quedar en negativo. Saldo disponible: ' . CompensacionHoras::formatearMinutos($saldoSinEseDia) . '.',
                ], 422);
            }
        }

        CompensacionHoras::where('user_id', $validated['user_id'])
            ->whereDate('fecha', $fecha->toDateString())
            ->whereIn('estado', [CompensacionHoras::ESTADO_PENDIENTE, CompensacionHoras::ESTADO_APROBADA])
            ->delete();

        $compensacion = CompensacionHoras::create([
            'user_id' => $validated['user_id'],
            'fecha' => $fecha->toDateString(),
            'minutos' => $minutos,
            'estado' => CompensacionHoras::ESTADO_APROBADA,
            'comentario' => $validated['comentario'] ?? null,
            'resuelto_por' => auth()->id(),
            'resuelto_at' => now(),
        ]);

        $this->avisarTrabajador(
            $compensacion,
            $compensacion->esExtra()
                ? "Se te han reconocido {$compensacion->etiqueta()} extra del {$fecha->format('d/m/Y')}. Ya cuentan en tu bolsa de horas."
                : "Se te ha asignado una compensación de horas de {$compensacion->etiqueta()} el {$fecha->format('d/m/Y')}."
        );

        return response()->json([
            'success' => true,
            'message' => $compensacion->esExtra()
                ? "Horas extra de {$compensacion->etiqueta()} reconocidas."
                : "Compensación de {$compensacion->etiqueta()} asignada.",
            'compensacion' => $this->serializar($compensacion),
            'saldo' => ComputoHorasService::saldoDisponible((int) $validated['user_id']),
        ]);
    }

    public function aprobar($id)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para aprobar solicitudes de horas.'], 403);
        }

        $compensacion = CompensacionHoras::with('user')->findOrFail($id);

        if ($compensacion->estado !== CompensacionHoras::ESTADO_PENDIENTE) {
            return response()->json(['success' => false, 'error' => 'La solicitud ya está ' . $compensacion->estado . '.'], 400);
        }

        if ($compensacion->minutos < 0) {
            $saldo = ComputoHorasService::bolsaHorasUsuario($compensacion->user_id)['saldo_min'];
            if ($saldo + $compensacion->minutos < 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede aprobar: la bolsa quedaría en negativo. Saldo actual ' . CompensacionHoras::formatearMinutos($saldo) . ', solicita ' . $compensacion->etiqueta() . '.',
                ], 400);
            }
        }

        $compensacion->update([
            'estado' => CompensacionHoras::ESTADO_APROBADA,
            'resuelto_por' => auth()->id(),
            'resuelto_at' => now(),
        ]);

        $this->avisarTrabajador(
            $compensacion,
            $compensacion->esExtra()
                ? "Tus {$compensacion->etiqueta()} extra del {$compensacion->fecha->format('d/m/Y')} han sido aprobadas y ya cuentan en tu bolsa de horas."
                : "Tu compensación de horas de {$compensacion->etiqueta()} del {$compensacion->fecha->format('d/m/Y')} ha sido aprobada."
        );

        return response()->json([
            'success' => true,
            'message' => $compensacion->esExtra()
                ? "Horas extra de {$compensacion->etiqueta()} aprobadas."
                : "Compensación de {$compensacion->etiqueta()} aprobada.",
            'compensacion' => $this->serializar($compensacion),
        ]);
    }

    public function denegar($id)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para denegar solicitudes de horas.'], 403);
        }

        $compensacion = CompensacionHoras::with('user')->findOrFail($id);

        if ($compensacion->estado !== CompensacionHoras::ESTADO_PENDIENTE) {
            return response()->json(['success' => false, 'error' => 'La solicitud ya está ' . $compensacion->estado . '.'], 400);
        }

        $compensacion->update([
            'estado' => CompensacionHoras::ESTADO_DENEGADA,
            'resuelto_por' => auth()->id(),
            'resuelto_at' => now(),
        ]);

        $this->avisarTrabajador(
            $compensacion,
            $compensacion->esExtra()
                ? "Tu solicitud de {$compensacion->etiqueta()} extra del {$compensacion->fecha->format('d/m/Y')} ha sido denegada."
                : "Tu solicitud de compensación de horas de {$compensacion->etiqueta()} del {$compensacion->fecha->format('d/m/Y')} ha sido denegada."
        );

        return response()->json([
            'success' => true,
            'message' => 'Solicitud denegada y alerta enviada.',
            'compensacion' => $this->serializar($compensacion),
        ]);
    }

    /**
     * Eliminar una solicitud: el trabajador solo las suyas pendientes;
     * oficina/responsable cualquiera (pendiente o aprobada).
     */
    public function eliminar($id)
    {
        $compensacion = CompensacionHoras::findOrFail($id);
        $esPropia = $compensacion->user_id === auth()->id();

        if ($this->puedeGestionar()) {
            // puede borrar cualquiera
        } elseif (!$esPropia || $compensacion->estado !== CompensacionHoras::ESTADO_PENDIENTE) {
            return response()->json(['success' => false, 'error' => 'Solo puedes eliminar tus solicitudes pendientes.'], 403);
        }

        $compensacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud eliminada.',
            'saldo' => ComputoHorasService::saldoDisponible($compensacion->user_id),
        ]);
    }

    /**
     * Solicitudes pendientes del usuario autenticado.
     */
    public function misPendientes()
    {
        $pendientes = CompensacionHoras::pendientes()
            ->where('user_id', auth()->id())
            ->orderBy('fecha')
            ->get()
            ->map(fn($c) => $this->serializar($c));

        return response()->json($pendientes);
    }

    // ------------------------------------------------------------------

    /**
     * Reglas de validación del campo 'minutos': entero con signo, distinto de 0, en pasos de
     * PASO_MINUTOS y hasta MINUTOS_MAX en valor absoluto.
     */
    private function reglasMinutos(): array
    {
        $paso = CompensacionHoras::PASO_MINUTOS;

        return [
            'minutos' => [
                'required', 'integer', 'not_in:0',
                'min:-' . CompensacionHoras::MINUTOS_MAX, 'max:' . CompensacionHoras::MINUTOS_MAX,
                function ($attr, $value, $fail) use ($paso) {
                    if ((int) $value % $paso !== 0) {
                        $fail("El tiempo se solicita en pasos de {$paso} minutos.");
                    }
                },
            ],
        ];
    }

    private function puedeGestionar(): bool
    {
        $u = auth()->user();

        return $u->esOficina() || $u->esResponsableDepartamento();
    }

    private function serializar(CompensacionHoras $c): array
    {
        return [
            'id' => $c->id,
            'user_id' => $c->user_id,
            'fecha' => $c->fecha->toDateString(),
            'minutos' => $c->minutos,
            'tipo' => $c->tipo(),
            'etiqueta' => $c->etiqueta(),
            'etiqueta_corta' => $c->etiquetaCorta(),
            'estado' => $c->estado,
            'comentario' => $c->comentario,
            'created_at' => $c->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Alerta a RRHH y a los responsables de los departamentos del trabajador
     * (mismo circuito que las solicitudes de vacaciones).
     */
    private function avisarResponsables(User $user, string $mensaje): void
    {
        try {
            $destinatarios = collect();

            $rrhh = User::where('email', 'josemanuel.amuedo@pacoreyes.com')->first();
            if ($rrhh) {
                $destinatarios->push($rrhh->id);
            }

            $departamentos = $user->departamentos()->pluck('departamentos.id');
            if ($departamentos->isNotEmpty()) {
                $destinatarios = $destinatarios->merge(
                    Departamento::whereIn('id', $departamentos)
                        ->whereNotNull('responsable_id')
                        ->pluck('responsable_id')
                );
            }

            foreach ($destinatarios->unique()->filter(fn($id) => $id !== $user->id) as $destinatarioId) {
                $alerta = Alerta::create([
                    'user_id_1' => $user->id,
                    'destinatario_id' => $destinatarioId,
                    'mensaje' => $mensaje,
                    'tipo' => 'compensacion_horas',
                ]);
                AlertaLeida::create([
                    'alerta_id' => $alerta->id,
                    'user_id' => $destinatarioId,
                    'leida_en' => null,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Fallo creando la alerta de solicitud de horas.', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Alerta al trabajador (asignación directa, aprobación o denegación). Se crea también el
     * registro de lectura pendiente para que le cuente como alerta sin leer.
     */
    private function avisarTrabajador(CompensacionHoras $compensacion, string $mensaje): void
    {
        try {
            $alerta = Alerta::create([
                'user_id_1' => auth()->id(),
                'destinatario_id' => $compensacion->user_id,
                'mensaje' => $mensaje,
                'tipo' => 'compensacion_horas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            AlertaLeida::create([
                'alerta_id' => $alerta->id,
                'user_id' => $compensacion->user_id,
                'leida_en' => null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Fallo creando la alerta al trabajador por solicitud de horas.', [
                'error' => $e->getMessage(),
                'compensacion_id' => $compensacion->id,
            ]);
        }
    }
}
