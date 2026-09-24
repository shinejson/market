<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $carts) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->get($request->user());

        return response()->json(['data' => $this->carts->groupedPayload($cart)]);
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'qty' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->carts->addItem($request->user(), (int) $data['variant_id'], (int) $data['qty']);

        return response()->json(['data' => $this->carts->groupedPayload($cart)], 201);
    }

    public function update(Request $request, int $item): JsonResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:0'],
        ]);
        $cart = $this->carts->updateItem($request->user(), $item, (int) $data['qty']);

        return response()->json(['data' => $this->carts->groupedPayload($cart)]);
    }

    public function destroy(Request $request, int $item): JsonResponse
    {
        $cart = $this->carts->removeItem($request->user(), $item);

        return response()->json(['data' => $this->carts->groupedPayload($cart)]);
    }
}
