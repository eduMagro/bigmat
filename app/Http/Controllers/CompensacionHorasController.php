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
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Compensación de horas contra la bolsa del trabajador.
 *
 * El trabajador solicita para un día un número de horas con signo (negativo = se las toma
 * libres, positivo = se le reconocen). Oficina o el responsable aprueban o deniegan.
 * Una compensación aprobada cuenta en la bolsa por sus horas y sustituye al fichaje de ese día.
 */
class CompensacionHorasController extends Controller
{
    /**
     * Saldo de la bolsa del usuario (propio, o de otro si quien consulta puede gestionarlo).
     */
    public function saldo(Request $request)
    {
        $userId = (int) $request->input('user_id', auth()->id());

        if ($userId !== auth()->id() && !$this->puedeGestionar()) {
            return response()->json(['error' => 'No tienes permiso para consultar esta bolsa.'], 403);
        }

        return response()->json(array_merge(
            ComputoHorasService::saldoDisponible($userId),
            ['horas_max' => CompensacionHoras::HORAS_MAX]
        ));
    }

    /**
     * El trabajador solicita una compensación de horas para un día.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'horas' => ['required', 'integer', 'not_in:0', 'min:-' . CompensacionHoras::HORAS_MAX, 'max:' . CompensacionHoras::HORAS_MAX],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $fecha = Carbon::parse($validated['fecha'])->startOfDay();

        if ($fecha->lt(Carbon::today())) {
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
            $texto = $yaExiste->estado === CompensacionHoras::ESTADO_PENDIENTE ? 'una solicitud pendiente' : 'una compensación aprobada';
            return response()->json(['error' => "Ya tienes {$texto} para ese día ({$yaExiste->etiquetaHoras()})."], 422);
        }

        // La bolsa no puede quedar en negativo: se cuentan también las solicitudes pendientes
        $horas = (int) $validated['horas'];
        if ($horas < 0) {
            $disponible = ComputoHorasService::saldoDisponible($user->id)['disponible'];
            if ($disponible + $horas < 0) {
                $maximo = max(0, (int) floor($disponible));
                return response()->json([
                    'error' => $maximo > 0
                        ? "Tu bolsa no puede quedar en negativo. Como máximo puedes pedir {$maximo} h (tienes " . CompensacionHoras::formatearHoras((int) floor($disponible)) . ' disponibles, contando tus solicitudes pendientes).'
                        : 'Tu bolsa no puede quedar en negativo. No tienes horas disponibles para compensar.',
                ], 422);
            }
        }

        try {
            $compensacion = CompensacionHoras::create([
                'user_id' => $user->id,
                'fecha' => $fecha->toDateString(),
                'horas' => (int) $validated['horas'],
                'estado' => CompensacionHoras::ESTADO_PENDIENTE,
                'comentario' => $validated['comentario'] ?? null,
            ]);

            $this->avisarResponsables(
                $user,
                "{$user->name} ha solicitado una compensación de horas de {$compensacion->etiquetaHoras()} para el {$fecha->format('d/m/Y')}."
            );

            return response()->json([
                'success' => 'Solicitud de compensación registrada correctamente.',
                'compensacion' => $this->serializar($compensacion),
                'saldo' => ComputoHorasService::saldoDisponible($user->id),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Error al registrar la solicitud de compensación de horas.', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'payload' => $validated,
            ]);

            return response()->json(['error' => 'No se pudo registrar la solicitud. Inténtalo de nuevo más tarde.'], 500);
        }
    }

    /**
     * Oficina/responsable asigna directamente una compensación aprobada a un trabajador.
     * Si ese día ya había una solicitud pendiente o aprobada, se sustituye.
     */
    public function asignarDirecto(Request $request)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['error' => 'No tienes permiso para asignar compensaciones.'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'fecha' => ['required', 'date'],
            'horas' => ['required', 'integer', 'not_in:0', 'min:-' . CompensacionHoras::HORAS_MAX, 'max:' . CompensacionHoras::HORAS_MAX],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        $fecha = Carbon::parse($validated['fecha'])->startOfDay();

        if ($fecha->lt(ComputoHorasService::fechaInicioBolsa())) {
            return response()->json(['error' => 'La bolsa de horas empieza el ' . ComputoHorasService::fechaInicioBolsa()->format('d/m/Y') . '.'], 422);
        }

        $horas = (int) $validated['horas'];
        if ($horas < 0) {
            // Saldo aprobado sin contar lo que ya hubiera aprobado ese mismo día (se va a sustituir)
            $saldo = ComputoHorasService::bolsaHorasUsuario((int) $validated['user_id'])['saldo'];
            $aprobadaEseDia = CompensacionHoras::aprobadas()
                ->where('user_id', $validated['user_id'])
                ->whereDate('fecha', $fecha->toDateString())
                ->value('horas');
            $saldoSinEseDia = $saldo - (int) ($aprobadaEseDia ?? 0);
            if ($saldoSinEseDia + $horas < 0) {
                return response()->json([
                    'error' => 'La bolsa del trabajador no puede quedar en negativo. Saldo disponible: ' . CompensacionHoras::formatearHoras((int) floor($saldoSinEseDia)) . '.',
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
            'horas' => (int) $validated['horas'],
            'estado' => CompensacionHoras::ESTADO_APROBADA,
            'comentario' => $validated['comentario'] ?? null,
            'resuelto_por' => auth()->id(),
            'resuelto_at' => now(),
        ]);

        $this->avisarTrabajador(
            $compensacion,
            "Se te ha asignado una compensación de horas de {$compensacion->etiquetaHoras()} el {$fecha->format('d/m/Y')}."
        );

        return response()->json([
            'success' => true,
            'message' => "Compensación de {$compensacion->etiquetaHoras()} asignada.",
            'compensacion' => $this->serializar($compensacion),
            'saldo' => ComputoHorasService::saldoDisponible((int) $validated['user_id']),
        ]);
    }

    public function aprobar($id)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para aprobar compensaciones.'], 403);
        }

        $compensacion = CompensacionHoras::with('user')->findOrFail($id);

        if ($compensacion->estado !== CompensacionHoras::ESTADO_PENDIENTE) {
            return response()->json(['success' => false, 'error' => 'La solicitud ya está ' . $compensacion->estado . '.'], 400);
        }

        if ($compensacion->horas < 0) {
            $saldo = ComputoHorasService::bolsaHorasUsuario($compensacion->user_id)['saldo'];
            if ($saldo + $compensacion->horas < 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede aprobar: la bolsa quedaría en negativo. Saldo actual ' . CompensacionHoras::formatearHoras((int) floor($saldo)) . ', solicita ' . $compensacion->etiquetaHoras() . '.',
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
            "Tu compensación de horas de {$compensacion->etiquetaHoras()} del {$compensacion->fecha->format('d/m/Y')} ha sido aprobada."
        );

        return response()->json([
            'success' => true,
            'message' => "Compensación de {$compensacion->etiquetaHoras()} aprobada.",
            'compensacion' => $this->serializar($compensacion),
        ]);
    }

    public function denegar($id)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para denegar compensaciones.'], 403);
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
            "Tu solicitud de compensación de horas de {$compensacion->etiquetaHoras()} del {$compensacion->fecha->format('d/m/Y')} ha sido denegada."
        );

        return response()->json([
            'success' => true,
            'message' => 'Solicitud denegada y alerta enviada.',
            'compensacion' => $this->serializar($compensacion),
        ]);
    }

    /**
     * Eliminar una compensación: el trabajador solo las suyas pendientes;
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
            'message' => 'Compensación eliminada.',
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
            'horas' => $c->horas,
            'etiqueta' => $c->etiquetaHoras(),
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
            Log::warning('Fallo creando la alerta de compensación de horas.', [
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
            Log::warning('Fallo creando la alerta al trabajador por compensación de horas.', [
                'error' => $e->getMessage(),
                'compensacion_id' => $compensacion->id,
            ]);
        }
    }
}
