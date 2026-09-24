<?php

namespace App\Services;

use App\Models\Address;
use App\Models\CheckoutIdempotency;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use App\Models\SellerOrder;
use App\Models\SellerSettlement;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected CartService $carts,
        protected PaymentGateway $gateway,
    ) {}

    public function quote(User $user): array
    {
        $cart = $this->carts->get($user);
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }
        $this->revalidatePrices($cart);

        return $this->carts->groupedPayload($cart);
    }

    public function checkout(User $user, int $shippingAddressId, string $idempotencyKey): array
    {
        $existing = CheckoutIdempotency::query()
            ->where('key', $idempotencyKey)
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return $existing->response;
        }

        $address = Address::query()
            ->where('user_id', $user->id)
            ->whereKey($shippingAddressId)
            ->firstOrFail();

        $payload = DB::transaction(function () use ($user, $address, $idempotencyKey) {
            $cart = $this->carts->get($user);
            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
            }

            $this->revalidatePrices($cart);
            $quote = $this->carts->groupedPayload($cart);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'subtotal' => $quote['totals']['subtotal'],
                'delivery_total' => $quote['totals']['delivery_total'],
                'tax_total' => $quote['totals']['tax_total'],
                'grand_total' => $quote['totals']['grand_total'],
                'currency' => $quote['totals']['currency'],
                'status' => Order::STATUS_PENDING_PAYMENT,
                'shipping_address_id' => $address->id,
                'placed_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $rate = (string) config('markethub.commission_rate');

            foreach ($quote['groups'] as $group) {
                $subtotal = $group['subtotal'];
                $delivery = (string) $group['store']['delivery_fee'];
                $commission = bcmul($subtotal, $rate, 2);
                $net = bcsub(bcadd($subtotal, $delivery, 2), $commission, 2);

                $storeId = $group['store']['id'];
                $firstItem = $cart->items->firstWhere('store_id', $storeId);
                $tenantId = $firstItem?->variant?->tenant_id
                    ?? ProductVariant::withoutGlobalScopes()->find($group['items'][0]['variant_id'])?->tenant_id;

                $sellerOrder = SellerOrder::withoutGlobalScopes()->create([
                    'order_id' => $order->id,
                    'tenant_id' => $tenantId,
                    'store_id' => $storeId,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $delivery,
                    'commission' => $commission,
                    'net_settlement' => $net,
                    'status' => SellerOrder::STATUS_AWAITING_FULFILLMENT,
                ]);

                foreach ($group['items'] as $line) {
                    $this->reserveInventory($line['variant_id'], $line['qty']);

                    OrderItem::query()->create([
                        'seller_order_id' => $sellerOrder->id,
                        'variant_id' => $line['variant_id'],
                        'product_name' => $line['product_name'],
                        'sku' => $line['sku'],
                        'unit_price' => $line['unit_price'],
                        'qty' => $line['qty'],
                        'line_tax' => '0.00',
                        'options' => $line['options'],
                    ]);
                }

                SellerSettlement::query()->create([
                    'seller_order_id' => $sellerOrder->id,
                    'gross' => $subtotal,
                    'commission' => $commission,
                    'delivery_fee' => $delivery,
                    'refund_amount' => '0.00',
                    'net' => $net,
                    'status' => SellerSettlement::STATUS_PENDING,
                ]);
            }

            $intent = $this->gateway->createIntent($order, (string) $order->grand_total);

            PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'gateway' => config('markethub.payment_gateway'),
                'gateway_ref' => $intent->gatewayRef,
                'amount' => $order->grand_total,
                'status' => PaymentTransaction::STATUS_INITIATED,
                'idempotency_key' => $idempotencyKey,
                'meta' => $intent->meta,
            ]);

            $this->carts->clear($user);

            $order->load('sellerOrders');

            return [
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'grand_total' => (string) $order->grand_total,
                    'currency' => $order->currency,
                ],
                'seller_orders' => $order->sellerOrders->map(fn ($so) => [
                    'id' => $so->id,
                    'store_id' => $so->store_id,
                    'subtotal' => (string) $so->subtotal,
                    'status' => $so->status,
                ])->all(),
                'payment' => [
                    'type' => $intent->type,
                    'url' => $intent->url,
                    'gateway_ref' => $intent->gatewayRef,
                ],
            ];
        });

        CheckoutIdempotency::query()->create([
            'key' => $idempotencyKey,
            'user_id' => $user->id,
            'response' => $payload,
            'status_code' => 201,
            'expires_at' => now()->addHours((int) config('markethub.idempotency_ttl_hours')),
        ]);

        return $payload;
    }

    protected function revalidatePrices($cart): void
    {
        TenantContext::bypass(true);
        try {
            foreach ($cart->items as $item) {
                $variant = ProductVariant::query()->with(['product', 'inventory'])->find($item->variant_id);
                if (! $variant || $variant->status !== ProductVariant::STATUS_ACTIVE || $variant->product->status !== 'active') {
                    throw ValidationException::withMessages(['cart' => 'A product in your cart is no longer available.']);
                }
                if ($variant->availableQty() < $item->qty) {
                    throw ValidationException::withMessages(['qty' => "Insufficient stock for {$variant->product->name}."]);
                }
                $current = $variant->effectivePrice();
                if (bccomp((string) $current, (string) $item->unit_price, 2) !== 0) {
                    $item->update(['unit_price' => $current]);
                    $item->unit_price = $current;
                }
            }
        } finally {
            TenantContext::bypass(false);
        }
    }

    protected function reserveInventory(int $variantId, int $qty): void
    {
        $inventory = Inventory::withoutGlobalScopes()
            ->where('variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            throw ValidationException::withMessages(['inventory' => 'Inventory record missing.']);
        }

        $available = (int) $inventory->quantity - (int) $inventory->reserved;
        if ($available < $qty) {
            throw ValidationException::withMessages(['qty' => 'Insufficient inventory at checkout.']);
        }

        $inventory->reserved = (int) $inventory->reserved + $qty;
        $inventory->version = (int) $inventory->version + 1;
        $inventory->save();
    }

    public function markPaid(Order $order): void
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => Order::STATUS_PAID]);
            foreach ($order->sellerOrders()->withoutGlobalScopes()->get() as $sellerOrder) {
                foreach ($sellerOrder->items as $item) {
                    $inventory = Inventory::withoutGlobalScopes()
                        ->where('variant_id', $item->variant_id)
                        ->lockForUpdate()
                        ->first();
                    if ($inventory) {
                        $inventory->quantity = max(0, (int) $inventory->quantity - (int) $item->qty);
                        $inventory->reserved = max(0, (int) $inventory->reserved - (int) $item->qty);
                        $inventory->version = (int) $inventory->version + 1;
                        $inventory->save();
                    }
                }
            }
        });
    }

    public function releaseReservation(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->sellerOrders()->withoutGlobalScopes()->get() as $sellerOrder) {
                foreach ($sellerOrder->items as $item) {
                    $inventory = Inventory::withoutGlobalScopes()
                        ->where('variant_id', $item->variant_id)
                        ->lockForUpdate()
                        ->first();
                    if ($inventory) {
                        $inventory->reserved = max(0, (int) $inventory->reserved - (int) $item->qty);
                        $inventory->version = (int) $inventory->version + 1;
                        $inventory->save();
                    }
                }
                $sellerOrder->update(['status' => SellerOrder::STATUS_CANCELLED]);
            }
            $order->update(['status' => Order::STATUS_CANCELLED]);
        });
    }
}
