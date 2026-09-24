<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\WebhookEvent;
use App\Services\CheckoutService;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected CheckoutService $checkout,
    ) {}

    public function intent(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()->where('user_id', $request->user()->id)->findOrFail($orderId);
        $existing = $order->payments()->where('status', PaymentTransaction::STATUS_INITIATED)->latest()->first();
        if ($existing) {
            $intent = $this->gateway->createIntent($order, (string) $order->grand_total);

            return response()->json([
                'data' => [
                    'type' => $intent->type,
                    'url' => $intent->url,
                    'gateway_ref' => $existing->gateway_ref,
                ],
            ]);
        }
        $intent = $this->gateway->createIntent($order, (string) $order->grand_total);
        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'gateway' => config('markethub.payment_gateway'),
            'gateway_ref' => $intent->gatewayRef,
            'amount' => $order->grand_total,
            'status' => PaymentTransaction::STATUS_INITIATED,
        ]);

        return response()->json([
            'data' => [
                'type' => $intent->type,
                'url' => $intent->url,
                'gateway_ref' => $intent->gatewayRef,
            ],
        ]);
    }

    public function webhook(Request $request, string $gateway): JsonResponse
    {
        $result = $this->gateway->verifyAndParse(new WebhookRequest(
            payload: $request->all(),
            headers: $request->headers->all(),
            rawBody: $request->getContent(),
        ));

        if (! $result) {
            return response()->json([
                'error' => ['code' => 'invalid_webhook', 'message' => 'Signature verification failed.', 'fields' => null],
            ], 400);
        }

        $event = WebhookEvent::query()->firstOrCreate(
            ['gateway' => $gateway, 'event_id' => $result->eventId],
            ['payload' => $result->payload],
        );

        if ($event->processed_at) {
            return response()->json(['data' => ['ok' => true, 'idempotent' => true]]);
        }

        $tx = PaymentTransaction::query()->where('gateway_ref', $result->gatewayRef)->first();
        if ($tx) {
            if ($result->status === 'succeeded') {
                $tx->update([
                    'status' => PaymentTransaction::STATUS_SUCCEEDED,
                    'raw_webhook_id' => $result->eventId,
                ]);
                $this->checkout->markPaid($tx->order);
            } elseif ($result->status === 'failed') {
                $tx->update(['status' => PaymentTransaction::STATUS_FAILED, 'raw_webhook_id' => $result->eventId]);
                $this->checkout->releaseReservation($tx->order);
            }
        }

        $event->update(['processed_at' => now()]);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function mockPay(Request $request): JsonResponse
    {
        $ref = $request->string('ref');
        $orderId = $request->integer('order');
        $tx = PaymentTransaction::query()->where('gateway_ref', $ref)->where('order_id', $orderId)->firstOrFail();

        if ($tx->status === PaymentTransaction::STATUS_SUCCEEDED) {
            return response()->json(['data' => ['status' => 'already_paid', 'order_id' => $tx->order_id]]);
        }

        $payload = [
            'event_id' => 'evt_'.Str::uuid()->toString(),
            'gateway_ref' => $ref,
            'status' => 'succeeded',
            'order_id' => $orderId,
        ];
        $raw = json_encode($payload);
        $signature = hash_hmac('sha256', $raw, (string) config('app.key'));

        $result = $this->gateway->verifyAndParse(new WebhookRequest(
            payload: $payload,
            headers: ['X-Mock-Signature' => $signature],
            rawBody: $raw,
        ));

        if ($result) {
            $event = WebhookEvent::query()->firstOrCreate(
                ['gateway' => 'mock', 'event_id' => $result->eventId],
                ['payload' => $result->payload],
            );
            if (! $event->processed_at) {
                $tx->update(['status' => PaymentTransaction::STATUS_SUCCEEDED, 'raw_webhook_id' => $result->eventId]);
                $this->checkout->markPaid($tx->order);
                $event->update(['processed_at' => now()]);
            }
        }

        return response()->json(['data' => ['status' => 'paid', 'order_id' => $tx->order_id]]);
    }
}
