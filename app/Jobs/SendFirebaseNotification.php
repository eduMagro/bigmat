<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $type,
        public array $recipients,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(FirebaseNotificationService $service): void
    {
        $result = match ($this->type) {
            'user' => $this->sendToUser($service),
            'users' => $service->sendToUsers($this->recipients, $this->title, $this->body, $this->data),
            'tokens' => $service->sendToTokens($this->recipients, $this->title, $this->body, $this->data),
            'role' => $service->sendToRole($this->recipients[0], $this->title, $this->body, $this->data),
            'departamento' => $service->sendToDepartamento($this->recipients[0], $this->title, $this->body, $this->data),
            default => ['success' => false, 'message' => 'Tipo de notificación no válido'],
        };

        Log::info('Firebase: Notificación enviada', [
            'type' => $this->type,
            'title' => $this->title,
            'result' => $result,
        ]);
    }

    private function sendToUser(FirebaseNotificationService $service): array
    {
        $user = User::find($this->recipients[0]);

        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }

        return $service->sendToUser($user, $this->title, $this->body, $this->data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Firebase: Error al enviar notificación', [
            'type' => $this->type,
            'title' => $this->title,
            'error' => $exception->getMessage(),
        ]);
    }
}
