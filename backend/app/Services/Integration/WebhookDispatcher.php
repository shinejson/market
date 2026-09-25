<?php

namespace App\Services\Integration;

use App\Models\DomainEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    public const CATALOG = [
        'order.placed',
        'order.paid',
        'order.shipped',
        'order.delivered',
        'order.cancelled',
        'order.refunded',
        'product.low_stock',
        'settlement.created',
        'payout.paid',
        'review.published',
        'subscription.changed',
        'ad.campaign.exhausted',
        'api_version.deprecated',
        'webhook.challenge',
    ];

    public function dispatch(int $tenantId, DomainEvent $event): void
    {
        $bypassed = TenantContext::isBypassed();
        TenantContext::bypass(true);
        try {
            $endpoints = WebhookEndpoint::query()
                ->where('tenant_id', $tenantId)
                ->where('status', WebhookEndpoint::STATUS_ACTIVE)
                ->get();
        } finally {
            TenantContext::bypass($bypassed);
        }

        foreach ($endpoints as $endpoint) {
            if (! $endpoint->listensFor($event->type)) {
                continue;
            }
            $this->attempt($endpoint, $event->event_id, $event->type, $event->payload);
        }
    }

    public function attempt(WebhookEndpoint $endpoint, string $eventId, string $type, array $payload): WebhookDelivery
    {
        $body = json_encode([
            'id' => $eventId,
            'type' => $type,
            'created_at' => now()->toIso8601String(),
            'data' => $payload,
        ], JSON_UNESCAPED_SLASHES);

        $delivery = WebhookDelivery::query()->create([
            'endpoint_id' => $endpoint->id,
            'event_id' => $eventId,
            'event_type' => $type,
            'attempt' => 1,
            'status' => 'pending',
            'payload_hash' => hash('sha256', $body),
            'payload' => json_decode($body, true),
        ]);

        if (! $this->urlAllowed($endpoint->url)) {
            $delivery->update(['status' => 'failed', 'response_code' => 0]);
            $this->bumpFailure($endpoint);

            return $delivery->fresh();
        }

        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts.'.'.$body, $endpoint->secret_hash);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-MarketHub-Signature' => 't='.$ts.',v1='.$sig,
                    'X-MarketHub-Event-Id' => $eventId,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $ok = $response->successful();
            $delivery->update([
                'status' => $ok ? 'delivered' : 'failed',
                'response_code' => $response->status(),
                'next_retry_at' => $ok ? null : now()->addMinutes(1),
            ]);
            if ($ok) {
                $endpoint->update(['fail_count' => 0]);
            } else {
                $this->bumpFailure($endpoint);
            }
        } catch (\Throwable $e) {
            Log::warning('webhook.dispatch.failed', ['endpoint' => $endpoint->id, 'error' => $e->getMessage()]);
            $delivery->update([
                'status' => 'failed',
                'response_code' => 0,
                'next_retry_at' => now()->addMinutes(1),
            ]);
            $this->bumpFailure($endpoint);
        }

        return $delivery->fresh();
    }

    public function challenge(WebhookEndpoint $endpoint, string $plainSecret): bool
    {
        if (! $this->urlAllowed($endpoint->url)) {
            return false;
        }
        $challenge = bin2hex(random_bytes(8));
        $ts = (string) time();
        $body = json_encode(['challenge' => $challenge]);
        $sig = hash_hmac('sha256', $ts.'.'.$body, hash('sha256', $plainSecret));

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-MarketHub-Signature' => 't='.$ts.',v1='.$sig,
                    'X-MarketHub-Challenge' => $challenge,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            if (! $response->successful()) {
                return false;
            }
            $echo = $response->header('X-MarketHub-Challenge') ?: ($response->json('challenge') ?? null);

            return $echo === $challenge || $response->status() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function bumpFailure(WebhookEndpoint $endpoint): void
    {
        $count = (int) $endpoint->fail_count + 1;
        $attrs = ['fail_count' => $count];
        if ($count >= 8) {
            $attrs['status'] = WebhookEndpoint::STATUS_DISABLED;
        }
        $endpoint->update($attrs);
    }

    protected function urlAllowed(string $url): bool
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' && ! app()->environment('local', 'testing')) {
            return false;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return app()->environment('local', 'testing');
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return app()->environment('local', 'testing');
        }

        return true;
    }
}
