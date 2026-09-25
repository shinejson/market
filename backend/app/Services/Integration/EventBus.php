<?php

namespace App\Services\Integration;

use App\Models\AnalyticsEvent;
use App\Models\DomainEvent;
use Illuminate\Support\Str;

class EventBus
{
    public function __construct(
        protected WebhookDispatcher $webhooks,
    ) {}

    public function emit(?int $tenantId, string $type, array $payload, ?string $userHash = null): DomainEvent
    {
        $event = DomainEvent::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => (string) Str::uuid(),
            'type' => $type,
            'payload' => $payload,
            'dispatched_at' => now(),
        ]);

        AnalyticsEvent::query()->create([
            'tenant_id' => $tenantId,
            'event_id' => $event->event_id,
            'type' => $type,
            'user_hash' => $userHash,
            'payload' => $this->pseudonymize($payload),
        ]);

        if ($tenantId) {
            $this->webhooks->dispatch($tenantId, $event);
        }

        return $event;
    }

    protected function pseudonymize(array $payload): array
    {
        unset($payload['email'], $payload['phone'], $payload['full_name'], $payload['address']);

        return $payload;
    }
}
