<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        $orders = Order::query()
            ->with(['sellerOrders.items', 'payments'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $order->load(['sellerOrders.items', 'sellerOrders.store', 'payments', 'shippingAddress']);

        return response()->json(['data' => $order]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);
        $this->checkout->releaseReservation($order);

        return response()->json(['data' => $order->fresh(['sellerOrders'])]);
    }
}
