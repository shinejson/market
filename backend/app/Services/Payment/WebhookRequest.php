<?php

namespace App\Services\Payment;

class WebhookRequest
{
    public function __construct(
        public readonly array $payload,
        public readonly array $headers,
        public readonly string $rawBody,
    ) {}

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtolower($key);

        foreach ($this->headers as $name => $value) {
            if (strtolower($name) === $normalized) {
                return is_array($value) ? ($value[0] ?? $default) : $value;
            }
        }

        return $default;
    }
}
