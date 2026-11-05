<?php

namespace App\Services\Push;

/**
 * Un push listo para FCM: título y cuerpo visibles y `data` para que la app
 * navegue (FCM solo admite valores string en data). El mismo mensaje sirve
 * para Android e iOS; payload() lo pone en el formato de la API HTTP v1.
 */
final class FcmMessage
{
    /** @param  array<string, scalar|null>  $data */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    /** Copia con más datos (el canal añade type y notification_id). */
    public function withData(array $data): self
    {
        return new self($this->title, $this->body, $data + $this->data);
    }

    /** Cuerpo de messages:send para un token. */
    public function payload(string $token): array
    {
        return ['message' => [
            'token' => $token,
            'notification' => [
                'title' => $this->title,
                'body' => $this->body,
            ],
            'data' => $this->stringData(),
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => (string) config('services.fcm.android_channel', 'general'),
                    'sound' => 'default',
                ],
            ],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default']],
            ],
        ]];
    }

    /** @return array<string, string> */
    public function stringData(): array
    {
        return collect($this->data)
            ->filter(fn ($value) => is_scalar($value) || $value === null)
            ->map(fn ($value) => match (true) {
                is_bool($value) => $value ? '1' : '0',
                $value === null => '',
                default => (string) $value,
            })
            ->all();
    }
}
