<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AiUsage;
use App\Models\TenantDomain;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\Domains\DomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPhase3Controller extends Controller
{
    public function domains(): JsonResponse
    {
        return response()->json(['data' => TenantDomain::withoutGlobalScopes()->orderByDesc('id')->get()]);
    }

    public function forceRemoveDomain(TenantDomain $domain): JsonResponse
    {
        $domain->update(['status' => TenantDomain::STATUS_REMOVED]);

        return response()->json(['data' => $domain->fresh()]);
    }

    public function forceVerifyDomain(DomainService $domains, TenantDomain $domain): JsonResponse
    {
        return response()->json(['data' => $domains->verify($domain, true)]);
    }

    public function ads(): JsonResponse
    {
        return response()->json([
            'data' => AdCampaign::withoutGlobalScopes()->with('targets')->orderByDesc('id')->limit(100)->get(),
        ]);
    }

    public function aiCosts(): JsonResponse
    {
        $rows = AiUsage::withoutGlobalScopes()
            ->selectRaw('tenant_id, sum(tokens_in + tokens_out) as tokens, sum(cost_estimate) as cost')
            ->groupBy('tenant_id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function webhookHealth(): JsonResponse
    {
        return response()->json(['data' => [
            'endpoints' => WebhookEndpoint::withoutGlobalScopes()->count(),
            'active' => WebhookEndpoint::withoutGlobalScopes()->where('status', 'active')->count(),
            'failed_24h' => WebhookDelivery::query()->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count(),
        ]]);
    }
}
