<?php

namespace App\Services\Payment;

class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $refundRef,
        public readonly string $message = '',
    ) {}
}
