<?php

return [
    'commission_rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.05),
    'payment_gateway' => env('PAYMENT_GATEWAY', 'mock'),
    'currency' => env('MARKETPLACE_CURRENCY', 'USD'),
    'payment_expiry_minutes' => (int) env('PAYMENT_EXPIRY_MINUTES', 30),
    'idempotency_ttl_hours' => 24,
    'default_per_page' => 15,
    'max_per_page' => 100,
];
