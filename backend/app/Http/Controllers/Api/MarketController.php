<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        TenantContext::bypass(true);
        try {
            $q = Product::query()
                ->with(['images', 'store', 'category', 'variants.inventory'])
                ->where('status', Product::STATUS_ACTIVE)
                ->whereHas('store', fn ($s) => $s->where('status', Store::STATUS_ACTIVE));

            if ($request->filled('q')) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('description', 'like', $term);
                });
            }
            if ($request->filled('category_id')) {
                $q->where('category_id', $request->integer('category_id'));
            }
            if ($request->filled('store_id')) {
                $q->where('store_id', $request->integer('store_id'));
            }
            if ($request->filled('min_price')) {
                $q->where('price', '>=', $request->input('min_price'));
            }
            if ($request->filled('max_price')) {
                $q->where('price', '<=', $request->input('max_price'));
            }

            $sort = $request->string('sort', 'newest');
            match ((string) $sort) {
                'price_asc' => $q->orderBy('price'),
                'price_desc' => $q->orderByDesc('price'),
                'name' => $q->orderBy('name'),
                default => $q->orderByDesc('id'),
            };

            $page = $q->paginate($request->integer('per_page', 12));

            return response()->json([
                'data' => collect($page->items())->map(fn (Product $p) => $this->productCard($p))->all(),
                'meta' => [
                    'page' => $page->currentPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'last_page' => $page->lastPage(),
                ],
            ]);
        } finally {
            TenantContext::bypass(false);
        }
    }

    public function product(string $slug): JsonResponse
    {
        TenantContext::bypass(true);
        try {
            $product = Product::query()
                ->with(['images', 'store', 'category', 'variants.inventory'])
                ->where('slug', $slug)
                ->where('status', Product::STATUS_ACTIVE)
                ->firstOrFail();

            return response()->json(['data' => $this->productCard($product, true)]);
        } finally {
            TenantContext::bypass(false);
        }
    }

    public function stores(Request $request): JsonResponse
    {
        TenantContext::bypass(true);
        try {
            $q = Store::query()->where('status', Store::STATUS_ACTIVE);
            if ($request->filled('q')) {
                $q->where('name', 'like', '%'.$request->string('q').'%');
            }
            $page = $q->orderBy('name')->paginate($request->integer('per_page', 12));

            return response()->json([
                'data' => $page->items(),
                'meta' => [
                    'page' => $page->currentPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'last_page' => $page->lastPage(),
                ],
            ]);
        } finally {
            TenantContext::bypass(false);
        }
    }

    public function store(string $slug): JsonResponse
    {
        TenantContext::bypass(true);
        try {
            $store = Store::query()
                ->where('slug', $slug)
                ->where('status', Store::STATUS_ACTIVE)
                ->firstOrFail();

            $products = Product::query()
                ->with(['images', 'variants.inventory'])
                ->where('store_id', $store->id)
                ->where('status', Product::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->limit(24)
                ->get()
                ->map(fn (Product $p) => $this->productCard($p));

            return response()->json([
                'data' => [
                    'store' => $store,
                    'products' => $products,
                ],
            ]);
        } finally {
            TenantContext::bypass(false);
        }
    }

    public function categories(): JsonResponse
    {
        TenantContext::bypass(true);
        try {
            $cats = Category::query()->with('children')->whereNull('parent_id')->orderBy('position')->get();

            return response()->json(['data' => $cats]);
        } finally {
            TenantContext::bypass(false);
        }
    }

    protected function productCard(Product $p, bool $detailed = false): array
    {
        $payload = [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'price' => (string) $p->price,
            'brand' => $p->brand,
            'status' => $p->status,
            'image' => $p->primaryImage()?->url,
            'images' => $p->images->map(fn ($i) => ['id' => $i->id, 'url' => $i->url, 'is_primary' => $i->is_primary])->all(),
            'store' => $p->store ? [
                'id' => $p->store->id,
                'name' => $p->store->name,
                'slug' => $p->store->slug,
            ] : null,
            'category' => $p->category ? [
                'id' => $p->category->id,
                'name' => $p->category->name,
                'slug' => $p->category->slug,
            ] : null,
            'variants' => $p->variants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'options' => $v->options,
                'price' => $v->effectivePrice(),
                'available' => $v->availableQty(),
                'status' => $v->status,
            ])->all(),
        ];
        if ($detailed) {
            $payload['description'] = $p->description;
        }

        return $payload;
    }
}
