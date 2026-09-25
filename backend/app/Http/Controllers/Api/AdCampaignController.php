<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdBalance;
use App\Models\AdCampaign;
use App\Models\AdTarget;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdCampaignController extends Controller
{
    public function index(): JsonResponse
    {
        $campaigns = AdCampaign::query()->with('targets.product')->orderByDesc('id')->get();
        $balance = AdBalance::query()->first();

        return response()->json([
            'data' => [
                'balance' => $balance?->balance ?? '0.00',
                'campaigns' => $campaigns,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenantId();
        $data = $request->validate([
            'store_id' => ['required', Rule::exists('stores', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:120'],
            'daily_budget' => ['required', 'numeric', 'min:1'],
            'total_budget' => ['required', 'numeric', 'min:1'],
            'bid_cpc' => ['required', 'numeric', 'min:0.05'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer'],
        ]);

        $campaign = AdCampaign::query()->create([
            'store_id' => $data['store_id'],
            'name' => $data['name'],
            'daily_budget' => $data['daily_budget'],
            'total_budget' => $data['total_budget'],
            'bid_cpc' => $data['bid_cpc'],
            'status' => AdCampaign::STATUS_DRAFT,
            'start_date' => now()->toDateString(),
        ]);

        foreach ($data['product_ids'] as $pid) {
            $product = Product::query()->whereKey($pid)->where('store_id', $data['store_id'])->first();
            if ($product) {
                AdTarget::query()->create([
                    'campaign_id' => $campaign->id,
                    'product_id' => $product->id,
                    'match_type' => 'exact',
                ]);
            }
        }

        return response()->json(['data' => $campaign->load('targets')], 201);
    }

    public function update(Request $request, AdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'active', 'paused'])],
            'bid_cpc' => ['nullable', 'numeric', 'min:0.05'],
            'daily_budget' => ['nullable', 'numeric', 'min:1'],
            'total_budget' => ['nullable', 'numeric', 'min:1'],
        ]);
        $campaign->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['data' => $campaign->fresh('targets')]);
    }

    public function fund(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);
        $balance = AdBalance::query()->firstOrCreate(
            ['tenant_id' => $request->user()->tenantId()],
            ['balance' => 0],
        );
        $balance->balance = bcadd((string) $balance->balance, (string) $data['amount'], 2);
        $balance->save();

        return response()->json(['data' => $balance]);
    }

    public function stores(): JsonResponse
    {
        return response()->json(['data' => Store::query()->get(['id', 'name'])]);
    }
}
