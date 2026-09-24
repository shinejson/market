<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getOrCreate(User $user): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    public function get(User $user): Cart
    {
        $cart = $this->getOrCreate($user);
        $cart->load(['items.store', 'items.variant.product.images', 'items.variant.inventory']);

        return $cart;
    }

    public function addItem(User $user, int $variantId, int $qty): Cart
    {
        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be at least 1.']);
        }

        TenantContext::bypass(true);
        try {
            $variant = ProductVariant::query()
                ->with(['product.store', 'inventory'])
                ->findOrFail($variantId);
        } finally {
            TenantContext::bypass(false);
        }

        if ($variant->status !== ProductVariant::STATUS_ACTIVE || $variant->product->status !== 'active') {
            throw ValidationException::withMessages(['variant_id' => 'Product is not available.']);
        }

        if ($variant->availableQty() < $qty) {
            throw ValidationException::withMessages(['qty' => 'Insufficient inventory. Available: '.$variant->availableQty()]);
        }

        $cart = $this->getOrCreate($user);

        $item = $cart->items()->where('variant_id', $variantId)->first();
        if ($item) {
            $newQty = $item->qty + $qty;
            if ($variant->availableQty() < $newQty) {
                throw ValidationException::withMessages(['qty' => 'Insufficient inventory.']);
            }
            $item->update([
                'qty' => $newQty,
                'unit_price' => $variant->effectivePrice(),
            ]);
        } else {
            $cart->items()->create([
                'user_id' => $user->id,
                'store_id' => $variant->product->store_id,
                'variant_id' => $variant->id,
                'qty' => $qty,
                'unit_price' => $variant->effectivePrice(),
            ]);
        }

        return $this->get($user);
    }

    public function updateItem(User $user, int $itemId, int $qty): Cart
    {
        $cart = $this->getOrCreate($user);
        $item = $cart->items()->whereKey($itemId)->firstOrFail();

        if ($qty < 1) {
            $item->delete();

            return $this->get($user);
        }

        TenantContext::bypass(true);
        try {
            $variant = ProductVariant::query()->with('inventory')->findOrFail($item->variant_id);
        } finally {
            TenantContext::bypass(false);
        }

        if ($variant->availableQty() < $qty) {
            throw ValidationException::withMessages(['qty' => 'Insufficient inventory.']);
        }

        $item->update(['qty' => $qty, 'unit_price' => $variant->effectivePrice()]);

        return $this->get($user);
    }

    public function removeItem(User $user, int $itemId): Cart
    {
        $cart = $this->getOrCreate($user);
        $cart->items()->whereKey($itemId)->delete();

        return $this->get($user);
    }

    public function clear(User $user): void
    {
        $cart = $this->getOrCreate($user);
        $cart->items()->delete();
    }

    public function groupedPayload(Cart $cart): array
    {
        $groups = [];
        $subtotal = '0.00';
        $delivery = '0.00';

        foreach ($cart->items as $item) {
            $storeId = $item->store_id;
            if (! isset($groups[$storeId])) {
                $groups[$storeId] = [
                    'store' => [
                        'id' => $item->store->id,
                        'name' => $item->store->name,
                        'slug' => $item->store->slug,
                        'delivery_fee' => $item->store->delivery_fee,
                    ],
                    'items' => [],
                    'subtotal' => '0.00',
                ];
                $delivery = bcadd($delivery, (string) $item->store->delivery_fee, 2);
            }

            $line = bcmul((string) $item->unit_price, (string) $item->qty, 2);
            $groups[$storeId]['subtotal'] = bcadd($groups[$storeId]['subtotal'], $line, 2);
            $groups[$storeId]['items'][] = [
                'id' => $item->id,
                'variant_id' => $item->variant_id,
                'sku' => $item->variant->sku,
                'product_name' => $item->variant->product->name,
                'options' => $item->variant->options,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'line_total' => $line,
                'image' => $item->variant->product->primaryImage()?->url,
                'available' => $item->variant->availableQty(),
            ];
            $subtotal = bcadd($subtotal, $line, 2);
        }

        $tax = '0.00';
        $grand = bcadd(bcadd($subtotal, $delivery, 2), $tax, 2);

        return [
            'id' => $cart->id,
            'groups' => array_values($groups),
            'totals' => [
                'subtotal' => $subtotal,
                'delivery_total' => $delivery,
                'tax_total' => $tax,
                'grand_total' => $grand,
                'currency' => config('markethub.currency'),
            ],
        ];
    }
}
