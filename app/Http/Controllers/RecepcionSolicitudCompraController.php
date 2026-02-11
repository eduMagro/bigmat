<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
            $syncError = 'No se pudo sincronizar la lista inicial con HPR.';
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
            return [];
        }

        $data = $response->json('data');

        return is_array($data) ? $data : [];
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

        $request = Http::acceptJson()
            ->withToken($token)
            ->connectTimeout(8)
            ->timeout(20);

        $method = strtolower($method);

        return match ($method) {
            'post' => $request->asJson()->post($url, $payload),
            'get' => $request->get($url, $payload),
            default => throw new RuntimeException('Método HTTP no soportado: ' . $method),
        };
    }

    private function friendlyExceptionMessage(Throwable $e, string $default): string
    {
        $message = trim((string) $e->getMessage());

        if ($message === '') {
            return $default;
        }

        if (Str::contains($message, ['cURL error', 'Connection refused', 'timed out'])) {
            return $default;
        }

        return $message;
    }
}

