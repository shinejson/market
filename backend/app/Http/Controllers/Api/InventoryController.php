<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function show(ProductVariant $variant): JsonResponse
    {
        $variant->load('product');
        $this->authorize('view', $variant->product);
        $inventory = $variant->inventory;
        abort_unless($inventory, 404);

        return response()->json(['data' => $inventory]);
    }

    public function update(Request $request, ProductVariant $variant): JsonResponse
    {
        $variant->load('product');
        $this->authorize('update', $variant->product);
        $data = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'adjustment' => ['nullable', 'integer'],
        ]);

        $inventory = $variant->inventory;
        abort_unless($inventory, 404);

        if (isset($data['adjustment'])) {
            $inventory->quantity = max(0, (int) $inventory->quantity + (int) $data['adjustment']);
        }
        if (isset($data['quantity'])) {
            $inventory->quantity = $data['quantity'];
        }
        if (isset($data['low_stock_threshold'])) {
            $inventory->low_stock_threshold = $data['low_stock_threshold'];
        }
        $inventory->version = (int) $inventory->version + 1;
        $inventory->save();

        return response()->json(['data' => $inventory->fresh()]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $items = Inventory::query()
            ->with(['variant.product'])
            ->get()
            ->filter(fn (Inventory $inv) => $inv->isLowStock())
            ->values();

        return response()->json(['data' => $items]);
    }
}
