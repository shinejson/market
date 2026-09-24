<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGateway
{
    public function createIntent(Order $order, string $amount): PaymentIntentResult
    {
        $ref = 'mock_'.Str::uuid()->toString();

        return new PaymentIntentResult(
            gatewayRef: $ref,
            type: 'redirect',
            url: '/api/payments/mock/pay?ref='.$ref.'&order='.$order->id,
            meta: ['amount' => $amount],
        );
    }

    public function verifyAndParse(WebhookRequest $req): ?WebhookResult
    {
        $signature = (string) $req->header('X-Mock-Signature', '');
        $expected = hash_hmac('sha256', $req->rawBody, (string) config('app.key'));
        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = $req->payload;
        if (empty($payload['event_id']) || empty($payload['gateway_ref']) || empty($payload['status'])) {
            return null;
        }

        return new WebhookResult(
            eventId: (string) $payload['event_id'],
            gatewayRef: (string) $payload['gateway_ref'],
            status: (string) $payload['status'],
            payload: $payload,
        );
    }

    public function refund(PaymentTransaction $tx, string $amount): RefundResult
    {
        return new RefundResult(
            success: true,
            refundRef: 'refund_'.Str::uuid()->toString(),
            message: 'Mock refund of '.$amount,
        );
    }
}
