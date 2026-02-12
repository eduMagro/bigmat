<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RecepcionSolicitudCompraController extends Controller
{
    public function index()
    {
        $recepcionadas = [];
        $syncError = null;

        try {
            $recepcionadas = $this->fetchRecepcionadas(80);
        } catch (Throwable $e) {
            $syncError = $this->friendlyExceptionMessage($e, 'No se pudo sincronizar la lista inicial con HPR.');
            $this->logHprException('sync-inicial-recepcionadas', $e, [
                'limit' => 80,
            ]);
        }

        return view('recepcion_solicitudes.index', [
            'recepcionadas' => $recepcionadas,
            'syncError' => $syncError,
        ]);
    }

    public function buscar(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:255',
        ]);

        $codigo = trim((string) $validated['codigo']);
        if ($codigo === '') {
            return response()->json([
                'success' => false,
                'message' => 'Código QR vacío.',
            ], 422);
        }

        try {
            $response = $this->callHpr('post', '/info', [
                'token' => $codigo,
            ]);

            return $this->proxyJson($response, 'No se pudo consultar la solicitud en HPR.');
        } catch (Throwable $e) {
            $this->logHprException('buscar-solicitud', $e, [
                'codigo' => $this->maskSensitiveValue($codigo),
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->friendlyExceptionMessage($e, 'Error de conexión al consultar la solicitud.'),
            ], 503);
        }
    }

    public function recepcionar(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:255',
        ]);

        $codigo = trim((string) $validated['codigo']);
        if ($codigo === '') {
            return response()->json([
                'success' => false,
                'message' => 'Código QR vacío.',
            ], 422);
        }

        $usuario = auth()->user();
        $recepcionadoPor = trim((string) ($usuario?->nombre_completo ?? $usuario?->name ?? 'Recepción BIGMAT'));

        try {
            $response = $this->callHpr('post', '/recepcionar', [
                'token' => $codigo,
                'recepcionado_por' => $recepcionadoPor,
            ]);

            return $this->proxyJson($response, 'No se pudo marcar la solicitud como recepcionada.');
        } catch (Throwable $e) {
            $this->logHprException('recepcionar-solicitud', $e, [
                'codigo' => $this->maskSensitiveValue($codigo),
                'recepcionado_por' => $recepcionadoPor,
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->friendlyExceptionMessage($e, 'Error de conexión al recepcionar la solicitud.'),
            ], 503);
        }
    }

    public function recepcionadas(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $limit = (int) ($validated['limit'] ?? 80);

        try {
            $response = $this->callHpr('get', '/recepcionadas', ['limit' => $limit]);

            return $this->proxyJson($response, 'No se pudo obtener el listado de recepcionadas.');
        } catch (Throwable $e) {
            $this->logHprException('listar-recepcionadas', $e, [
                'limit' => $limit,
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->friendlyExceptionMessage($e, 'Error de conexión al cargar recepcionadas.'),
            ], 503);
        }
    }

    private function fetchRecepcionadas(int $limit): array
    {
        $response = $this->callHpr('get', '/recepcionadas', ['limit' => $limit]);

        if (!$response->successful()) {
            throw new RuntimeException('HPR respondió con estado HTTP ' . $response->status() . ' al listar recepcionadas.');
        }

        $data = $response->json('data');

        if (!is_array($data)) {
            throw new RuntimeException('HPR devolvió un formato inválido para la lista de recepcionadas.');
        }

        return $data;
    }

    private function proxyJson(Response $response, string $fallbackMessage)
    {
        $json = $response->json();
        $payload = is_array($json) ? $json : [];

        if (!array_key_exists('success', $payload)) {
            $payload['success'] = $response->successful();
        }

        if (!$response->successful() && !isset($payload['message'])) {
            $payload['message'] = $payload['error'] ?? $fallbackMessage;
        }

        return response()->json($payload, $response->status());
    }

    private function callHpr(string $method, string $endpoint, array $payload = []): Response
    {
        $baseUrl = rtrim((string) config('solicitudes_compra.hpr.api_base_url'), '/');
        $token = trim((string) config('solicitudes_compra.hpr.api_token'));

        if ($baseUrl === '') {
            throw new RuntimeException('HPR_SOLICITUDES_API_BASE_URL no está configurada.');
        }

        if ($token === '') {
            throw new RuntimeException('SOLICITUDES_COMPRA_API_TOKEN no está configurada en BIGMAT.');
        }

        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $normalizedMethod = strtolower($method);
        $start = microtime(true);

        $logContext = [
            'method' => strtoupper($normalizedMethod),
            'endpoint' => $endpoint,
            'url' => $url,
            'payload' => $this->maskSensitivePayload($payload),
        ];

        Log::info('HPR request iniciada', $logContext);

        $request = Http::acceptJson()
            ->withToken($token)
            ->connectTimeout(8)
            ->timeout(20);

        try {
            $response = match ($normalizedMethod) {
                'post' => $request->asJson()->post($url, $payload),
                'get' => $request->get($url, $payload),
                default => throw new RuntimeException('Método HTTP no soportado: ' . $normalizedMethod),
            };
        } catch (Throwable $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            Log::error('HPR request falló por excepción', array_merge($logContext, [
                'duration_ms' => $durationMs,
                'exception' => $e,
            ]));
            throw $e;
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        if ($response->successful()) {
            Log::info('HPR request completada', array_merge($logContext, [
                'status' => $response->status(),
                'duration_ms' => $durationMs,
            ]));
        } else {
            Log::warning('HPR request respondió con error', array_merge($logContext, [
                'status' => $response->status(),
                'duration_ms' => $durationMs,
                'error' => $this->extractResponseError($response),
            ]));
        }

        return $response;
    }

    private function friendlyExceptionMessage(Throwable $e, string $default): string
    {
        $message = trim((string) $e->getMessage());

        if ($message === '') {
            return $default;
        }

        if (Str::contains($message, ['HPR_SOLICITUDES_API_BASE_URL', 'SOLICITUDES_COMPRA_API_TOKEN'])) {
            return 'Falta configuración de integración con HPR. Revisa URL y token en .env.';
        }

        if (Str::contains($message, ['timed out', 'cURL error 28'])) {
            return 'HPR tardó demasiado en responder. Inténtalo de nuevo en unos minutos.';
        }

        if (Str::contains($message, ['Connection refused', 'Could not resolve host', 'Failed to connect', 'cURL error 6', 'cURL error 7'])) {
            return 'No se pudo conectar con HPR. Revisa red/VPN y la URL configurada.';
        }

        if (preg_match('/\bHTTP\b\D*(\d{3})/i', $message, $matches)) {
            $status = (int) $matches[1];
            return $this->friendlyStatusMessage($status, $default);
        }

        return app()->isProduction() ? $default : $message;
    }

    private function friendlyStatusMessage(int $status, string $default): string
    {
        return match (true) {
            $status === 401 || $status === 403 => 'HPR rechazó la autenticación. Revisa el token configurado.',
            $status === 404 => 'No se encontró el endpoint en HPR. Revisa HPR_SOLICITUDES_API_BASE_URL.',
            $status === 422 => 'HPR rechazó los datos enviados. Revisa el código QR.',
            $status >= 500 => 'HPR está temporalmente no disponible. Inténtalo de nuevo en unos minutos.',
            default => $default,
        };
    }

    private function maskSensitivePayload(array $payload): array
    {
        $masked = $payload;

        foreach (['token', 'codigo'] as $key) {
            if (isset($masked[$key]) && is_string($masked[$key])) {
                $masked[$key] = $this->maskSensitiveValue($masked[$key]);
            }
        }

        return $masked;
    }

    private function maskSensitiveValue(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);

        if ($length === 0) {
            return '';
        }

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . str_repeat('*', max(1, $length - 8)) . substr($value, -4);
    }

    private function extractResponseError(Response $response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            $candidate = $json['message'] ?? $json['error'] ?? null;
            if (is_scalar($candidate)) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '') {
                    return Str::limit($candidate, 300);
                }
            }
        }

        $body = trim((string) $response->body());
        return $body === '' ? 'Sin contenido de error.' : Str::limit($body, 300);
    }

    private function logHprException(string $operation, Throwable $e, array $context = []): void
    {
        Log::error('RecepcionSolicitudCompraController: fallo en ' . $operation, array_merge($context, [
            'user_id' => auth()->id(),
            'exception' => $e,
        ]));
    }
}
