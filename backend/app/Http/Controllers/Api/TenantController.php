<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'business_details' => ['nullable', 'string'],
            'store_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $slug = Str::slug($data['name']);
        $base = $slug;
        $i = 1;
        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $tenant = Tenant::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'status' => Tenant::STATUS_PENDING,
            'owner_user_id' => $user->id,
            'country' => $data['country'] ?? null,
            'business_name' => $data['business_name'] ?? $data['name'],
            'business_details' => $data['business_details'] ?? null,
        ]);

        UserRole::query()->firstOrCreate([
            'user_id' => $user->id,
            'role' => 'tenant_owner',
            'tenant_id' => $tenant->id,
        ]);

        if (! empty($data['store_name'])) {
            Store::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['store_name'],
                'slug' => Str::slug($data['store_name']),
                'status' => Store::STATUS_DRAFT,
            ]);
        }

        return response()->json(['data' => $tenant->fresh('stores')], 201);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');
        $tenantId = $user->tenantId();
        abort_unless($tenantId, 404, 'No tenant associated.');

        $tenant = Tenant::query()->with('stores')->findOrFail($tenantId);
        $this->authorize('view', $tenant);

        return response()->json(['data' => $tenant]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');
        $tenant = Tenant::query()->findOrFail($user->tenantId());
        $this->authorize('update', $tenant);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
            'business_details' => ['nullable', 'string'],
        ]);

        $tenant->update($data);

        return response()->json(['data' => $tenant->fresh()]);
    }

    public function stores(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Store::class);
        $stores = Store::query()->orderBy('name')->paginate($request->integer('per_page', 15));

        return response()->json($this->paginate($stores));
    }

    public function storeStore(Request $request): JsonResponse
    {
        $this->authorize('create', Store::class);
        $user = $request->user()->load('roles');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'size:3'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_days' => ['nullable', 'integer', 'min:0'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        $store = Store::query()->create([
            ...$data,
            'slug' => $slug,
            'tenant_id' => $user->tenantId(),
            'status' => Store::STATUS_DRAFT,
        ]);

        return response()->json(['data' => $store], 201);
    }

    public function updateStore(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('stores', 'slug')->ignore($store->id)->where('tenant_id', $store->tenant_id)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'suspended'])],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_inclusive' => ['sometimes', 'boolean'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_days' => ['nullable', 'integer', 'min:0'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);
        $store->update($data);

        return response()->json(['data' => $store->fresh()]);
    }

    public function destroyStore(Store $store): JsonResponse
    {
        $this->authorize('delete', $store);
        $store->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    protected function paginate($paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
