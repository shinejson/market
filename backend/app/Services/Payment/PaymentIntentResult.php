<?php

namespace App\Services\Payment;

class PaymentIntentResult
{
    public function __construct(
        public readonly string $gatewayRef,
        public readonly string $type,
        public readonly ?string $url,
        public readonly array $meta = [],
    ) {}
}
