<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function tenants(Request $request): JsonResponse
    {
        TenantContext::bypass(true);
        $q = Tenant::query()->with('owner');
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

    public function updateTenant(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
        ]);
        $tenant->update($data);
        if ($data['status'] === Tenant::STATUS_ACTIVE) {
            Store::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('status', Store::STATUS_DRAFT)
                ->update(['status' => Store::STATUS_ACTIVE]);
        }

        return response()->json(['data' => $tenant->fresh()]);
    }

    public function orders(Request $request): JsonResponse
    {
        $q = Order::query()->with(['user', 'sellerOrders']);
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

    public function metrics(): JsonResponse
    {
        TenantContext::bypass(true);
        try {
            return response()->json([
                'data' => [
                    'tenants' => Tenant::query()->count(),
                    'active_stores' => Store::query()->where('status', Store::STATUS_ACTIVE)->count(),
                    'customers' => User::query()->whereHas('roles', fn ($q) => $q->where('role', 'customer'))->count(),
                    'products' => Product::query()->count(),
                    'orders' => Order::query()->count(),
                    'gmv' => (string) Order::query()->whereIn('status', [Order::STATUS_PAID, Order::STATUS_FULFILLED, Order::STATUS_COMPLETED, Order::STATUS_PARTIALLY_FULFILLED])->sum('grand_total'),
                    'commission' => (string) SellerOrder::withoutGlobalScopes()->sum('commission'),
                    'payments_succeeded' => PaymentTransaction::query()->where('status', PaymentTransaction::STATUS_SUCCEEDED)->count(),
                ],
            ]);
        } finally {
            TenantContext::bypass(false);
        }
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $q = AuditLog::query()->with('actor')->orderByDesc('id');
        if ($request->filled('tenant_id')) {
            $q->where('tenant_id', $request->integer('tenant_id'));
        }
        $page = $q->paginate($request->integer('per_page', 30));

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
}
