<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\SellerOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $today = now()->startOfDay();
        $salesToday = SellerOrder::query()
            ->where('created_at', '>=', $today)
            ->whereNotIn('status', [SellerOrder::STATUS_CANCELLED])
            ->sum('subtotal');

        $openOrders = SellerOrder::query()
            ->whereIn('status', [
                SellerOrder::STATUS_AWAITING_FULFILLMENT,
                SellerOrder::STATUS_PROCESSING,
            ])
            ->count();

        $lowStock = Inventory::query()
            ->get()
            ->filter(fn (Inventory $i) => $i->isLowStock())
            ->count();

        $recent = SellerOrder::query()
            ->with(['store', 'order.user'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $chart = SellerOrder::query()
            ->select(DB::raw("date(created_at) as day"), DB::raw('sum(subtotal) as total'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json([
            'data' => [
                'sales_today' => (string) $salesToday,
                'open_orders' => $openOrders,
                'low_stock' => $lowStock,
                'recent_orders' => $recent,
                'sales_chart' => $chart,
            ],
        ]);
    }
}
