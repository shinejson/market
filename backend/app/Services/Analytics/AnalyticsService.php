<?php

namespace App\Services\Analytics;

use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\AiInsight;
use App\Models\AnalyticsEvent;
use App\Models\SellerOrder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function marts(?int $tenantId = null): array
    {
        $bypassed = TenantContext::isBypassed();
        TenantContext::bypass(true);
        try {
            $orders = SellerOrder::withoutGlobalScopes()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->whereNotIn('status', [SellerOrder::STATUS_CANCELLED]);

            $gmv = (string) (clone $orders)->sum('subtotal');
            $commission = (string) (clone $orders)->sum('commission');
            $count = (clone $orders)->count();

            $funnel = [
                'views' => AnalyticsEvent::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->where('type', 'product.viewed')->count(),
                'carts' => AnalyticsEvent::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->where('type', 'cart.updated')->count(),
                'checkouts' => AnalyticsEvent::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->where('type', 'order.placed')->count(),
                'paid' => AnalyticsEvent::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->where('type', 'order.paid')->count(),
            ];

            $daily = SellerOrder::withoutGlobalScopes()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->select(DB::raw('date(created_at) as day'), DB::raw('sum(subtotal) as gmv'), DB::raw('count(*) as orders'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->map(fn ($r) => ['day' => $r->day, 'gmv' => (string) $r->gmv, 'orders' => (int) $r->orders])
                ->all();

            $impressions = AdImpression::query()
                ->when($tenantId, function ($q) use ($tenantId) {
                    $q->whereIn('campaign_id', AdCampaign::withoutGlobalScopes()->where('tenant_id', $tenantId)->select('id'));
                })
                ->count();
            $clicks = AdClick::query()
                ->when($tenantId, function ($q) use ($tenantId) {
                    $q->whereIn('campaign_id', AdCampaign::withoutGlobalScopes()->where('tenant_id', $tenantId)->select('id'));
                })
                ->count();
            $adSpend = (string) AdClick::query()
                ->when($tenantId, function ($q) use ($tenantId) {
                    $q->whereIn('campaign_id', AdCampaign::withoutGlobalScopes()->where('tenant_id', $tenantId)->select('id'));
                })
                ->sum('cost');

            $takeRate = bccomp($gmv, '0', 2) === 0 ? '0.00' : bcmul(bcdiv($commission, $gmv, 4), '100', 2);

            return [
                'gmv' => $gmv,
                'commission' => $commission,
                'take_rate' => $takeRate,
                'orders' => $count,
                'funnel' => $funnel,
                'daily' => $daily,
                'ads' => [
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'spend' => $adSpend,
                    'ctr' => $impressions ? round($clicks / $impressions, 4) : 0,
                ],
            ];
        } finally {
            TenantContext::bypass($bypassed);
        }
    }

    public function narrate(?int $tenantId, string $scope): AiInsight
    {
        $marts = $this->marts($tenantId);
        $fingerprint = hash('sha256', json_encode($marts));
        $gmv = $marts['gmv'];
        $orders = $marts['orders'];
        $take = $marts['take_rate'];
        $paid = $marts['funnel']['paid'];
        $views = $marts['funnel']['views'];
        $conversion = $views > 0 ? round(($paid / $views) * 100, 1) : 0;
        $narrative = $scope === 'platform'
            ? "Platform GMV is {$gmv} across {$orders} seller orders (take rate {$take}%). Conversion from recorded views to paid is {$conversion}%."
            : "Your store GMV is {$gmv} across {$orders} orders. Recorded view-to-paid conversion is {$conversion}%. Ad spend is {$marts['ads']['spend']}.";

        return AiInsight::query()->create([
            'tenant_id' => $tenantId,
            'insight_type' => 'sales_summary',
            'payload' => [
                'narrative' => $narrative,
                'metrics' => $marts,
                'window' => '30d',
                'source' => 'oltp_rollups',
            ],
            'data_fingerprint' => $fingerprint,
            'generated_at' => now(),
        ]);
    }
}
