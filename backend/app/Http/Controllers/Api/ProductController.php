<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        $q = Product::query()->with(['images', 'variants.inventory', 'category', 'store']);
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('store_id')) {
            $q->where('store_id', $request->integer('store_id'));
        }
        if ($request->filled('q')) {
            $q->where('name', 'like', '%'.$request->string('q').'%');
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

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Product::class);
        $tenantId = $request->user()->tenantId();
        $data = $request->validate([
            'store_id' => ['required', Rule::exists('stores', 'id')->where('tenant_id', $tenantId)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_class' => ['nullable', 'string', 'max:32'],
            'brand' => ['nullable', 'string', 'max:255'],
            'has_variants' => ['sometimes', 'boolean'],
            'sku' => ['nullable', 'string', 'max:64'],
            'quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $product = Product::query()->create([
            'store_id' => $data['store_id'],
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? Product::STATUS_DRAFT,
            'price' => $data['price'],
            'tax_class' => $data['tax_class'] ?? null,
            'brand' => $data['brand'] ?? null,
            'has_variants' => $data['has_variants'] ?? false,
        ]);

        $sku = $data['sku'] ?? strtoupper(Str::slug($data['name'], '-')).'-'.strtoupper(Str::random(4));
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
            'sku' => $sku,
            'options' => ['default' => true],
            'status' => ProductVariant::STATUS_ACTIVE,
        ]);
        Inventory::query()->create([
            'tenant_id' => $product->tenant_id,
            'variant_id' => $variant->id,
            'quantity' => $data['quantity'] ?? 0,
            'reserved' => 0,
            'low_stock_threshold' => 5,
        ]);

        return response()->json(['data' => $product->load(['variants.inventory', 'images'])], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return response()->json(['data' => $product->load(['images', 'variants.inventory', 'category', 'store'])]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'tax_class' => ['nullable', 'string'],
            'brand' => ['nullable', 'string'],
            'has_variants' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
        $product->update($data);

        return response()->json(['data' => $product->fresh(['images', 'variants.inventory'])]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:64'],
            'options' => ['nullable', 'array'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'tenant_id' => $product->tenant_id,
            'sku' => $data['sku'],
            'options' => $data['options'] ?? [],
            'price_override' => $data['price_override'] ?? null,
            'weight' => $data['weight'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'status' => $data['status'] ?? ProductVariant::STATUS_ACTIVE,
        ]);
        Inventory::query()->create([
            'tenant_id' => $product->tenant_id,
            'variant_id' => $variant->id,
            'quantity' => $data['quantity'] ?? 0,
            'reserved' => 0,
        ]);
        $product->update(['has_variants' => true]);

        return response()->json(['data' => $variant->load('inventory')], 201);
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $data = $request->validate([
            'sku' => ['sometimes', 'string', 'max:64'],
            'options' => ['nullable', 'array'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
        $variant->update($data);

        return response()->json(['data' => $variant->fresh('inventory')]);
    }

    public function destroyVariant(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $variant->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function storeImage(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);
        $path = $request->file('image')->store('tenants/'.$product->tenant_id.'/products/'.$product->id, 'public');
        if ($request->boolean('is_primary')) {
            $product->images()->update(['is_primary' => false]);
        }
        $image = ProductImage::query()->create([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
            'path' => $path,
            'position' => (int) $product->images()->max('position') + 1,
            'is_primary' => $request->boolean('is_primary') || $product->images()->count() === 0,
        ]);

        return response()->json(['data' => $image], 201);
    }

    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        $this->authorize('update', $product);
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $image->delete();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function reorderImages(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);
        foreach ($data['order'] as $pos => $id) {
            ProductImage::query()->where('product_id', $product->id)->whereKey($id)->update(['position' => $pos]);
        }

        return response()->json(['data' => $product->fresh('images')->images]);
    }
}
