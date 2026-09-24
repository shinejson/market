<?php

namespace App\Services\Payment;

class WebhookResult
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $gatewayRef,
        public readonly string $status,
        public readonly array $payload = [],
    ) {}
}
