<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiCategorization;
use App\Models\AiGeneration;
use App\Models\AiUsage;
use App\Models\Category;
use App\Models\Product;
use App\Models\TenantAiSetting;
use App\Services\Ai\AIGateway;
use App\Services\Ai\AiRequest;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(
        protected AIGateway $gateway,
        protected AnalyticsService $analytics,
    ) {}

    public function settings(Request $request): JsonResponse
    {
        $settings = TenantAiSetting::query()->firstOrCreate(
            ['tenant_id' => $request->user()->tenantId()],
            ['tone' => 'warm', 'length' => 'medium', 'language' => 'en', 'monthly_token_budget' => 50000],
        );

        return response()->json(['data' => $settings]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tone' => ['nullable', 'in:warm,luxury,playful'],
            'length' => ['nullable', 'in:short,medium,long'],
            'banned_words' => ['nullable', 'array'],
            'opted_out' => ['nullable', 'boolean'],
        ]);
        $settings = TenantAiSetting::query()->firstOrCreate(['tenant_id' => $request->user()->tenantId()]);
        $settings->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['data' => $settings->fresh()]);
    }

    public function describe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);
        $product = Product::query()->findOrFail($data['product_id']);
        $tenantId = (int) $request->user()->tenantId();
        $result = $this->gateway->complete($tenantId, new AiRequest(
            feature: 'description',
            prompt: $product->name.' '.$product->brand.' '.$product->description,
            attributes: [
                'name' => $product->name,
                'brand' => $product->brand,
                'category' => $product->category?->name,
                'price' => (string) $product->price,
            ],
        ));
        $gen = AiGeneration::query()->create([
            'feature' => 'description',
            'product_id' => $product->id,
            'prompt_hash' => hash('sha256', $product->name.'|'.$product->brand),
            'output' => $result->text,
            'review_status' => AiGeneration::STATUS_DRAFT,
        ]);

        return response()->json(['data' => $gen], 201);
    }

    public function categorize(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer']]);
        $product = Product::query()->findOrFail($data['product_id']);
        $cats = Category::query()->get(['id', 'name'])->toArray();
        $result = $this->gateway->classify((int) $request->user()->tenantId(), new AiRequest(
            feature: 'categorize',
            prompt: $product->name.' '.$product->description,
            attributes: ['name' => $product->name, 'brand' => $product->brand],
            categories: $cats,
        ));
        $row = AiCategorization::query()->create([
            'product_id' => $product->id,
            'suggested_category_id' => $result->suggestedCategoryId,
            'confidence' => number_format($result->confidence, 3, '.', ''),
        ]);

        return response()->json(['data' => $row->load('suggestedCategory')], 201);
    }

    public function reviewGeneration(Request $request, AiGeneration $generation): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'output' => ['nullable', 'string'],
        ]);
        $generation->update([
            'review_status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'output' => $data['output'] ?? $generation->output,
        ]);
        if ($data['status'] === 'approved' && $generation->product) {
            $generation->product->update(['description' => $generation->output]);
        }

        return response()->json(['data' => $generation->fresh()]);
    }

    public function acceptCategory(Request $request, AiCategorization $categorization): JsonResponse
    {
        $data = $request->validate([
            'accepted' => ['required', 'boolean'],
            'category_id' => ['nullable', 'integer'],
        ]);
        $categorization->update([
            'accepted' => $data['accepted'],
            'overridden_by' => $request->user()->id,
            'suggested_category_id' => $data['accepted']
                ? ($categorization->suggested_category_id)
                : ($data['category_id'] ?? $categorization->suggested_category_id),
        ]);
        if ($data['accepted'] && $categorization->suggested_category_id) {
            $categorization->product?->update(['category_id' => $categorization->suggested_category_id]);
        } elseif (! $data['accepted'] && ! empty($data['category_id'])) {
            $categorization->product?->update(['category_id' => $data['category_id']]);
        }

        return response()->json(['data' => $categorization->fresh('suggestedCategory')]);
    }

    public function generations(): JsonResponse
    {
        return response()->json(['data' => AiGeneration::query()->with('product')->orderByDesc('id')->limit(50)->get()]);
    }

    public function insights(Request $request): JsonResponse
    {
        $insight = $this->analytics->narrate($request->user()->tenantId(), 'tenant');

        return response()->json(['data' => $insight]);
    }

    public function usage(): JsonResponse
    {
        $used = (int) AiUsage::query()->where('created_at', '>=', now()->startOfMonth())->sum(\Illuminate\Support\Facades\DB::raw('tokens_in + tokens_out'));
        $settings = TenantAiSetting::query()->first();

        return response()->json(['data' => [
            'used' => $used,
            'budget' => $settings?->monthly_token_budget ?? 50000,
        ]]);
    }
}
