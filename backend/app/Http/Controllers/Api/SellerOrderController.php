<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SellerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SellerOrder::class);
        $q = SellerOrder::query()->with(['items', 'store', 'order.user']);
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        $page = $q->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function show(SellerOrder $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['data' => $order->load(['items', 'store', 'order.user', 'settlement'])]);
    }

    public function updateStatus(Request $request, SellerOrder $order): JsonResponse
    {
        $this->authorize('update', $order);
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(SellerOrder::TRANSITIONS))],
        ]);
        if (! $order->canTransitionTo($data['status'])) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$order->status} to {$data['status']}.",
            ]);
        }
        $order->update(['status' => $data['status']]);
        $this->syncMasterOrder($order);

        return response()->json(['data' => $order->fresh()]);
    }

    protected function syncMasterOrder(SellerOrder $sellerOrder): void
    {
        $master = Order::query()->with(['sellerOrders' => fn ($q) => $q->withoutGlobalScopes()])->find($sellerOrder->order_id);
        if (! $master) {
            return;
        }
        $statuses = $master->sellerOrders()->withoutGlobalScopes()->pluck('status');
        if ($statuses->every(fn ($s) => in_array($s, [SellerOrder::STATUS_DELIVERED, SellerOrder::STATUS_COMPLETED], true))) {
            $master->update(['status' => Order::STATUS_FULFILLED]);
        } elseif ($statuses->contains(SellerOrder::STATUS_SHIPPED) || $statuses->contains(SellerOrder::STATUS_PROCESSING)) {
            $master->update(['status' => Order::STATUS_PARTIALLY_FULFILLED]);
        }
    }
}
