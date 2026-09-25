<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerSettlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SellerPublicApiController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $q = Product::query()->with(['variants.inventory', 'images'])->orderByDesc('id');
        if ($request->filled('cursor')) {
            $q->where('id', '<', $request->integer('cursor'));
        }
        $items = $q->limit(50)->get();

        return response()->json([
            'data' => $items,
            'meta' => ['next_cursor' => optional($items->last())->id, 'version' => 'v1'],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $q = SellerOrder::query()->with('items')->orderByDesc('id');
        if ($request->filled('cursor')) {
            $q->where('id', '<', $request->integer('cursor'));
        }
        $items = $q->limit(50)->get();

        return response()->json([
            'data' => $items,
            'meta' => ['next_cursor' => optional($items->last())->id, 'version' => 'v1'],
        ]);
    }

    public function fulfill(Request $request, SellerOrder $order): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:processing,shipped,delivered']]);
        if (! $order->canTransitionTo($data['status'])) {
            throw ValidationException::withMessages(['status' => 'Invalid transition.']);
        }
        $order->update(['status' => $data['status']]);

        return response()->json(['data' => $order->fresh('items')]);
    }

    public function inventory(Request $request, int $variant): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $inv = Inventory::query()->where('variant_id', $variant)->firstOrFail();
        $inv->update(['quantity' => $data['quantity']]);

        return response()->json(['data' => $inv->fresh()]);
    }

    public function settlements(): JsonResponse
    {
        $items = SellerSettlement::query()->orderByDesc('id')->limit(50)->get();

        return response()->json(['data' => $items, 'meta' => ['version' => 'v1']]);
    }
}
